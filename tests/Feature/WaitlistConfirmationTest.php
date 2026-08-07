<?php

namespace Tests\Feature;

use App\Models\WaitlistSignup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WaitlistConfirmationTest extends TestCase
{
    use RefreshDatabase;

    private function confirmUrl(WaitlistSignup $signup, ?\DateTimeInterface $expiry = null): string
    {
        return URL::temporarySignedRoute(
            'waitlist.confirm',
            $expiry ?? now()->addDays(14),
            ['signup' => $signup->public_id],
        );
    }

    private function unsubscribeUrl(WaitlistSignup $signup): string
    {
        return URL::signedRoute('waitlist.unsubscribe', ['signup' => $signup->public_id]);
    }

    #[Test]
    public function a_signed_link_confirms_the_signup(): void
    {
        $signup = WaitlistSignup::factory()->create();

        $this->get($this->confirmUrl($signup))
            ->assertOk()
            ->assertSee('You’re on the list.', false);

        $this->assertNotNull($signup->refresh()->confirmed_at);
    }

    #[Test]
    public function confirming_twice_is_harmless_and_keeps_the_original_timestamp(): void
    {
        $signup = WaitlistSignup::factory()->create();

        $this->get($this->confirmUrl($signup))->assertOk();
        $first = $signup->refresh()->confirmed_at;

        $this->travel(1)->hours();
        $this->get($this->confirmUrl($signup))->assertOk();

        $this->assertTrue($first->equalTo($signup->refresh()->confirmed_at));
    }

    #[Test]
    public function a_tampered_link_is_refused(): void
    {
        $signup = WaitlistSignup::factory()->create();
        $other = WaitlistSignup::factory()->create();

        // Swap the id but keep someone else's signature.
        $tampered = str_replace($signup->public_id, $other->public_id, $this->confirmUrl($signup));

        $this->get($tampered)->assertForbidden();

        $this->assertNull($other->refresh()->confirmed_at);
        $this->assertNull($signup->refresh()->confirmed_at);
    }

    #[Test]
    public function an_expired_link_is_refused(): void
    {
        $signup = WaitlistSignup::factory()->create();
        $url = $this->confirmUrl($signup, now()->addDays(14));

        $this->travel(15)->days();

        $this->get($url)->assertForbidden();
        $this->assertNull($signup->refresh()->confirmed_at);
    }

    #[Test]
    public function an_unsigned_confirm_url_is_refused(): void
    {
        $signup = WaitlistSignup::factory()->create();

        $this->get(route('waitlist.confirm', ['signup' => $signup->public_id]))
            ->assertForbidden();
    }

    #[Test]
    public function the_row_id_never_appears_in_a_link(): void
    {
        // Sequential ids would leak how big the list is to anyone who gets an email.
        $signup = WaitlistSignup::factory()->create();

        $this->assertStringNotContainsString("/{$signup->id}/", $this->confirmUrl($signup));
        $this->assertStringContainsString($signup->public_id, $this->confirmUrl($signup));
    }

    #[Test]
    public function a_signed_get_link_unsubscribes(): void
    {
        $signup = WaitlistSignup::factory()->confirmed()->create();

        $this->get($this->unsubscribeUrl($signup))
            ->assertOk()
            ->assertSee('You’re off the list.', false);

        $this->assertNotNull($signup->refresh()->unsubscribed_at);
    }

    #[Test]
    public function one_click_unsubscribe_works_as_a_post_without_a_csrf_token(): void
    {
        // RFC 8058: Gmail and Outlook POST to the List-Unsubscribe URL. They
        // have no session, so the signature is what authenticates the request.
        $signup = WaitlistSignup::factory()->confirmed()->create();

        $this->post($this->unsubscribeUrl($signup))->assertOk();

        $this->assertNotNull($signup->refresh()->unsubscribed_at);
    }

    #[Test]
    public function an_unsubscribe_link_never_expires(): void
    {
        $signup = WaitlistSignup::factory()->confirmed()->create();
        $url = $this->unsubscribeUrl($signup);

        $this->travel(3)->years();

        $this->get($url)->assertOk();
        $this->assertNotNull($signup->refresh()->unsubscribed_at);
    }

    #[Test]
    public function the_unsubscribed_page_offers_a_one_tap_way_back(): void
    {
        // Corporate mail filters follow every link in a message, so someone can
        // be unsubscribed without ever tapping it themselves.
        $signup = WaitlistSignup::factory()->confirmed()->create();

        $this->get($this->unsubscribeUrl($signup))
            ->assertOk()
            ->assertSee('Actually, keep me on the list');

        $this->post(URL::signedRoute('waitlist.resubscribe', ['signup' => $signup->public_id]))
            ->assertRedirect(route('coming-soon'))
            ->assertSessionHas('waitlist.status', 'confirmed');

        $signup->refresh();
        $this->assertNull($signup->unsubscribed_at);
        $this->assertNotNull($signup->confirmed_at);
    }

    #[Test]
    public function an_unknown_signup_is_a_404_not_a_leak(): void
    {
        $signup = WaitlistSignup::factory()->create();
        $url = $this->confirmUrl($signup);
        $signup->delete();

        $this->get($url)->assertNotFound();
    }
}
