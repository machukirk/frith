<?php

namespace Tests\Feature;

use App\Jobs\SyncSignupToMailerLite;
use App\Mail\FounderWelcome;
use App\Models\Registration;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class FounderEmailTest extends TestCase
{
    use RefreshDatabase;

    private function register(string $email = 'sam@example.com'): Registration
    {
        $this->post(route('register.step.store', 'you'), [
            'first_name' => 'Sam',
            'email' => $email,
            'postcode_outcode' => 'SS9',
        ]);

        return Registration::query()->where('email', $email)->sole();
    }

    private function verifyUrl(Registration $r): string
    {
        return URL::signedRoute('register.verify', ['registration' => $r->public_id]);
    }

    private function unsubscribeUrl(Registration $r): string
    {
        return URL::signedRoute('register.unsubscribe', ['registration' => $r->public_id]);
    }

    #[Test]
    public function registering_queues_the_founder_email(): void
    {
        Mail::fake();

        $registration = $this->register();

        Mail::assertQueued(FounderWelcome::class, fn ($mail) => $mail->hasTo('sam@example.com'));
        $this->assertNotNull($registration->verification_sent_at);
    }

    #[Test]
    public function it_is_sent_from_the_very_first_screen_not_the_last(): void
    {
        // Somebody who answers one screen and stops still hears from us. That
        // is the entire reason the form saves as it goes.
        Mail::fake();

        $registration = $this->register();

        $this->assertNull($registration->completed_at, 'they have not finished');
        Mail::assertQueued(FounderWelcome::class);
    }

    #[Test]
    public function it_is_only_ever_sent_once(): void
    {
        Mail::fake();

        $this->register();
        $this->flushSession();
        $this->register();

        Mail::assertQueuedCount(1);
    }

    #[Test]
    public function it_is_delayed_so_it_does_not_arrive_mid_form(): void
    {
        // Asserted against the real queue rather than a fake, because both
        // Mail::fake() and Queue::fake() drop the delay on the floor — and the
        // delay is the whole point. If this ever became zero, somebody would
        // get a "thanks for joining" email while still on screen two.
        config(['queue.default' => 'database']);

        $this->register();

        $job = DB::table('jobs')->sole();
        $available = Carbon::createFromTimestamp($job->available_at);

        $this->assertSame(
            config('frith.registration.welcome_delay_minutes'),
            (int) round(now()->diffInMinutes($available)),
        );
    }

    #[Test]
    public function the_email_renders_with_the_founder_number_and_both_links(): void
    {
        $registration = Registration::factory()->create(['first_name' => 'Sam', 'founder_number' => 47]);

        $mail = new FounderWelcome($registration);
        $html = $mail->render();

        $this->assertStringContainsString('Frith Founder', $html);
        $this->assertStringContainsString('47', $html);
        $this->assertStringContainsString('Thank you, Sam.', $html);
        $this->assertStringContainsString('Confirm my email', $html);
        $this->assertStringContainsString($registration->public_id, $html);
        // No client renders SVG, so the logo has to be the PNG export.
        $this->assertStringNotContainsString('.svg', $html);
    }

    #[Test]
    public function the_email_carries_a_plain_text_alternative(): void
    {
        // A message with no text part is marked down by spam filters, and it
        // is what a screen reader set to prefer plain text actually reads.
        $registration = Registration::factory()->create(['first_name' => 'Sam', 'founder_number' => 47]);

        $body = $this->sendAndCapture($registration)->getTextBody();

        $this->assertStringContainsString('FRITH FOUNDER #47', $body);
        $this->assertStringContainsString('Thank you, Sam.', $body);
        $this->assertStringContainsString($registration->public_id, $body);
        $this->assertStringContainsString('Unsubscribe:', $body);
    }

    #[Test]
    public function the_subject_names_the_founder_number(): void
    {
        $registration = Registration::factory()->create(['founder_number' => 47]);

        $this->assertSame('You’re Frith Founder #47', (new FounderWelcome($registration))->envelope()->subject);
    }

    #[Test]
    public function somebody_who_stopped_part_way_is_invited_back(): void
    {
        $partial = Registration::factory()->partial()->create();
        $finished = Registration::factory()->create();

        $this->assertStringContainsString('pick up where you left off', strtolower((new FounderWelcome($partial))->render()));
        $this->assertStringNotContainsString('pick up where you left off', strtolower((new FounderWelcome($finished))->render()));
    }

    #[Test]
    public function the_email_carries_one_click_unsubscribe_headers(): void
    {
        $headers = (new FounderWelcome(Registration::factory()->create()))->headers()->text;

        $this->assertStringStartsWith('<http', $headers['List-Unsubscribe']);
        $this->assertStringContainsString('signature=', $headers['List-Unsubscribe']);
        $this->assertSame('List-Unsubscribe=One-Click', $headers['List-Unsubscribe-Post']);
    }

    #[Test]
    public function the_link_verifies_the_address_and_syncs_to_mailerlite(): void
    {
        // Verification is the gate on the sync — this is the moment an address
        // becomes one we are allowed to send a launch email to.
        Bus::fake();
        $registration = Registration::factory()->create();

        $this->get($this->verifyUrl($registration))
            ->assertOk()
            ->assertSee('That’s everything', false);

        $this->assertNotNull($registration->refresh()->email_verified_at);
        Bus::assertDispatched(SyncSignupToMailerLite::class, fn ($job) => $job->subscribed);
    }

    #[Test]
    public function verifying_twice_keeps_the_original_timestamp(): void
    {
        $registration = Registration::factory()->create();

        $this->get($this->verifyUrl($registration));
        $first = $registration->refresh()->email_verified_at;

        $this->travel(1)->hours();
        $this->get($this->verifyUrl($registration));

        $this->assertTrue($first->equalTo($registration->refresh()->email_verified_at));
    }

    #[Test]
    public function a_tampered_or_unsigned_link_is_refused(): void
    {
        $registration = Registration::factory()->create();
        $other = Registration::factory()->create();

        $tampered = str_replace($registration->public_id, $other->public_id, $this->verifyUrl($registration));

        $this->get($tampered)->assertForbidden();
        $this->get(route('register.verify', ['registration' => $registration->public_id]))->assertForbidden();

        $this->assertNull($registration->refresh()->email_verified_at);
        $this->assertNull($other->refresh()->email_verified_at);
    }

    #[Test]
    public function unsubscribing_works_by_link_and_by_one_click_post(): void
    {
        $byLink = Registration::factory()->verified()->create();
        $byPost = Registration::factory()->verified()->create();

        $this->get($this->unsubscribeUrl($byLink))->assertOk()->assertSee('You’re off the list.', false);
        // RFC 8058: Gmail and Outlook POST, with no session and no CSRF token.
        $this->post($this->unsubscribeUrl($byPost))->assertOk();

        $this->assertNotNull($byLink->refresh()->unsubscribed_at);
        $this->assertNotNull($byPost->refresh()->unsubscribed_at);
    }

    #[Test]
    public function an_unsubscribe_link_never_expires(): void
    {
        $registration = Registration::factory()->verified()->create();
        $url = $this->unsubscribeUrl($registration);

        $this->travel(3)->years();

        $this->get($url)->assertOk();
        $this->assertNotNull($registration->refresh()->unsubscribed_at);
    }

    #[Test]
    public function the_unsubscribed_page_offers_a_one_tap_way_back(): void
    {
        // Mail filters follow every link in a message, so somebody can be
        // unsubscribed without ever tapping it themselves.
        $registration = Registration::factory()->verified()->create();

        $this->get($this->unsubscribeUrl($registration))
            ->assertOk()
            ->assertSee('Actually, keep me on the list');

        $this->post(URL::signedRoute('register.resubscribe', ['registration' => $registration->public_id]))
            ->assertRedirect(route('register.verified.done'));

        $this->assertNull($registration->refresh()->unsubscribed_at);
    }

    #[Test]
    public function verifying_undoes_an_unsubscribe(): void
    {
        // Clicking confirm is a clear signal they want to hear from us, so it
        // should not leave them silently opted out.
        $registration = Registration::factory()->create(['unsubscribed_at' => now()]);

        $this->get($this->verifyUrl($registration))->assertOk();

        $registration->refresh();

        $this->assertNotNull($registration->email_verified_at);
        $this->assertNull($registration->unsubscribed_at);
    }

    #[Test]
    public function the_landing_pages_are_never_indexed(): void
    {
        $registration = Registration::factory()->create();

        $this->get($this->verifyUrl($registration))
            ->assertOk()
            ->assertSee('content="noindex, nofollow"', false);
    }

    /** Sends through the array transport so both MIME parts can be inspected. */
    private function sendAndCapture(Registration $registration): Email
    {
        $transport = Mail::mailer('array')->getSymfonyTransport();
        $transport->flush();

        Mail::mailer('array')->to($registration->email)->send(new FounderWelcome($registration));

        return $transport->messages()->first()->getOriginalMessage();
    }
}
