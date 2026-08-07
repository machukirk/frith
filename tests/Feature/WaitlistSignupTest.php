<?php

namespace Tests\Feature;

use App\Mail\AlreadyOnTheList;
use App\Mail\ConfirmWaitlistSignup;
use App\Models\WaitlistSignup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WaitlistSignupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    #[Test]
    public function the_coming_soon_page_renders_the_signup_form(): void
    {
        $this->get(route('coming-soon'))
            ->assertOk()
            ->assertSee('Let’s find your village', false)
            ->assertSee('Tell me when Frith launches')
            ->assertSee('name="email"', false);
    }

    #[Test]
    public function a_signup_is_stored_with_the_evidence_of_consent(): void
    {
        $this->post(route('waitlist.store'), ['email' => 'sam@example.com'])
            ->assertRedirect(route('coming-soon'))
            ->assertSessionHas('waitlist.status', 'pending-confirmation');

        $signup = WaitlistSignup::sole();

        $this->assertSame('sam@example.com', $signup->email);
        $this->assertSame(config('frith.consent.version'), $signup->consent_version);
        // The wording is snapshotted, not looked up, so editing the config
        // later can never rewrite what someone actually agreed to.
        $this->assertSame(config('frith.consent.text'), $signup->consent_text);
        $this->assertNotNull($signup->consented_at);
        $this->assertNotNull($signup->consent_ip);
        $this->assertNotNull($signup->public_id);

        // Double opt-in: not on the list until they prove the address is theirs.
        $this->assertNull($signup->confirmed_at);
        $this->assertNotNull($signup->confirmation_sent_at);

        Mail::assertQueued(ConfirmWaitlistSignup::class, fn ($mail) => $mail->hasTo('sam@example.com'));
    }

    #[Test]
    public function email_addresses_are_normalised_so_one_person_is_one_row(): void
    {
        $this->post(route('waitlist.store'), ['email' => '  SAM@Example.COM ']);

        $this->assertSame('sam@example.com', WaitlistSignup::sole()->email);
    }

    #[Test]
    public function an_invalid_email_is_rejected_with_the_shape_of_the_right_answer(): void
    {
        $this->post(route('waitlist.store'), ['email' => 'not-an-email'])
            ->assertSessionHasErrors(['email' => 'That email does not look right. It should look like name@example.com.']);

        $this->assertDatabaseCount('waitlist_signups', 0);
        Mail::assertNothingQueued();
    }

    #[Test]
    public function a_missing_email_is_rejected(): void
    {
        $this->post(route('waitlist.store'), ['email' => ''])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('waitlist_signups', 0);
    }

    #[Test]
    public function the_response_is_identical_whether_or_not_the_address_is_already_on_the_list(): void
    {
        // Membership of a waitlist for parents of disabled children is itself
        // sensitive. An endpoint that answers differently for a known address
        // answers "is this family on the list?" for anyone who asks.
        WaitlistSignup::factory()->confirmed()->create(['email' => 'known@example.com']);

        $new = $this->post(route('waitlist.store'), ['email' => 'brand-new@example.com']);
        $existing = $this->post(route('waitlist.store'), ['email' => 'known@example.com']);

        $this->assertSame($new->getStatusCode(), $existing->getStatusCode());
        $this->assertSame($new->headers->get('Location'), $existing->headers->get('Location'));
        $this->assertSame(
            session()->get('waitlist.status'),
            'pending-confirmation',
        );
    }

    #[Test]
    public function an_already_confirmed_address_is_told_so_in_the_mailbox_not_on_the_page(): void
    {
        WaitlistSignup::factory()->confirmed()->create([
            'email' => 'known@example.com',
            'confirmation_sent_at' => now()->subDay(),
        ]);

        $this->post(route('waitlist.store'), ['email' => 'known@example.com']);

        $this->assertDatabaseCount('waitlist_signups', 1);
        Mail::assertQueued(AlreadyOnTheList::class);
        Mail::assertNotQueued(ConfirmWaitlistSignup::class);
    }

    #[Test]
    public function a_repeat_signup_inside_the_cooldown_does_not_send_a_second_email(): void
    {
        WaitlistSignup::factory()->create([
            'email' => 'sam@example.com',
            'confirmation_sent_at' => now()->subMinutes(2),
        ]);

        $this->post(route('waitlist.store'), ['email' => 'sam@example.com'])
            ->assertSessionHas('waitlist.status', 'pending-confirmation');

        Mail::assertNothingQueued();
    }

    #[Test]
    public function a_repeat_signup_after_the_cooldown_sends_the_confirmation_again(): void
    {
        $cooldown = (int) config('frith.waitlist.resend_cooldown_minutes');

        WaitlistSignup::factory()->create([
            'email' => 'sam@example.com',
            'confirmation_sent_at' => now()->subMinutes($cooldown + 1),
        ]);

        $this->post(route('waitlist.store'), ['email' => 'sam@example.com']);

        Mail::assertQueued(ConfirmWaitlistSignup::class);
    }

    #[Test]
    public function signing_up_again_after_unsubscribing_records_fresh_consent(): void
    {
        $signup = WaitlistSignup::factory()->unsubscribed()->create([
            'email' => 'sam@example.com',
            'consented_at' => now()->subMonth(),
            'consent_version' => 'ancient.v0',
        ]);

        $this->post(route('waitlist.store'), ['email' => 'sam@example.com']);

        $signup->refresh();

        $this->assertNull($signup->unsubscribed_at);
        $this->assertNull($signup->confirmed_at, 'coming back should require confirming again');
        $this->assertSame(config('frith.consent.version'), $signup->consent_version);
        $this->assertTrue($signup->consented_at->isToday());

        Mail::assertQueued(ConfirmWaitlistSignup::class);
    }

    #[Test]
    public function the_signup_endpoint_is_rate_limited_per_ip(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $this->post(route('waitlist.store'), ['email' => "person{$i}@example.com"])
                ->assertSessionHasNoErrors();
        }

        $this->post(route('waitlist.store'), ['email' => 'eleventh@example.com'])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('waitlist_signups', 10);
    }

    #[Test]
    public function a_tripped_honeypot_looks_exactly_like_success_and_stores_nothing(): void
    {
        config()->set('honeypot.enabled', true);
        config()->set('honeypot.randomize_name_field_name', false);

        $this->post(route('waitlist.store'), [
            'email' => 'bot@example.com',
            'my_name' => 'I am a bot filling in every field',
        ])->assertRedirect(route('coming-soon'))
            ->assertSessionHas('waitlist.status', 'pending-confirmation');

        $this->assertDatabaseCount('waitlist_signups', 0);
        Mail::assertNothingQueued();
    }
}
