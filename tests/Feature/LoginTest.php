<?php

namespace Tests\Feature;

use App\Mail\LoginLink as LoginLinkMail;
use App\Models\Registration;
use App\Support\LoginLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private function founder(array $overrides = []): Registration
    {
        return Registration::factory()->create(array_merge([
            'email' => 'sarah@example.com',
            'first_name' => 'Sarah',
        ], $overrides));
    }

    #[Test]
    public function the_emailed_link_is_the_front_door(): void
    {
        // Registration never asks for a password, so this is how almost
        // everybody gets back in.
        Mail::fake();
        $founder = $this->founder();

        $this->post(route('login.send-link'), ['email' => 'sarah@example.com'])
            ->assertRedirect(route('link-sent'));

        Mail::assertSent(LoginLinkMail::class, fn ($mail) => $mail->hasTo('sarah@example.com'));

        $this->assertNotNull($founder->refresh()->login_token);
        $this->assertTrue($founder->login_token_expires_at->isFuture());
    }

    #[Test]
    public function the_token_is_never_stored_in_a_form_anybody_could_use(): void
    {
        // Whoever can read this table could otherwise log in as anybody in it,
        // and this table holds what families have told us about their children.
        $founder = $this->founder();
        $url = LoginLink::issue($founder);

        preg_match('#/login/link/[^/]+/([^/?]+)#', $url, $matches);
        $plain = $matches[1];

        $this->assertNotSame($plain, $founder->refresh()->login_token);
        $this->assertSame(hash('sha256', $plain), $founder->login_token);
    }

    #[Test]
    public function following_the_link_logs_them_in(): void
    {
        $founder = $this->founder();

        $this->get(LoginLink::issue($founder))->assertRedirect(route('verified'));

        $this->assertTrue(auth('founder')->check());
        $this->assertSame($founder->id, auth('founder')->id());
    }

    #[Test]
    public function following_the_link_also_verifies_them(): void
    {
        // Reading the inbox is the thing verification proves, and they have
        // just done it.
        $founder = $this->founder(['email_verified_at' => null]);

        $this->get(LoginLink::issue($founder));

        $this->assertNotNull($founder->refresh()->email_verified_at);
    }

    #[Test]
    public function a_link_works_once(): void
    {
        // A link that works twice is a link somebody forwarded.
        $founder = $this->founder();
        $url = LoginLink::issue($founder);

        $this->get($url);
        $this->post(route('logout'));

        $this->get($url)->assertRedirect(route('login'));
        $this->assertFalse(auth('founder')->check());
    }

    #[Test]
    public function a_link_stops_working_after_an_hour(): void
    {
        $founder = $this->founder();
        $url = LoginLink::issue($founder);

        $this->travel(LoginLink::LIFETIME_MINUTES + 1)->minutes();

        $this->get($url)->assertRedirect(route('login'));
        $this->assertFalse(auth('founder')->check());
    }

    #[Test]
    public function somebody_elses_token_is_no_good(): void
    {
        $founder = $this->founder();
        $other = $this->founder(['email' => 'other@example.com']);

        $url = LoginLink::issue($founder);
        $tampered = str_replace($founder->public_id, $other->public_id, $url);

        $this->get($tampered)->assertRedirect(route('login'));
        $this->assertFalse(auth('founder')->check());
    }

    #[Test]
    public function asking_for_a_link_never_says_whether_an_address_is_registered(): void
    {
        // Being on a list of families raising children with SEND is itself
        // sensitive. The answer is the same either way.
        Mail::fake();

        $known = $this->post(route('login.send-link'), ['email' => 'sarah@example.com']);
        $this->founder();
        $unknown = $this->post(route('login.send-link'), ['email' => 'nobody@example.com']);

        $this->assertSame($known->status(), $unknown->status());
        $this->assertSame($known->headers->get('Location'), $unknown->headers->get('Location'));
    }

    #[Test]
    public function a_password_works_for_anybody_who_has_set_one(): void
    {
        $founder = $this->founder(['password' => Hash::make('a-really-long-passphrase')]);

        $this->post(route('login.attempt'), [
            'email' => 'sarah@example.com',
            'password' => 'a-really-long-passphrase',
        ])->assertRedirect(route('verified'));

        $this->assertSame($founder->id, auth('founder')->id());
    }

    #[Test]
    public function somebody_with_no_password_is_told_so_rather_than_told_they_are_wrong(): void
    {
        // "Wrong password" sends somebody hunting for a password they never
        // had. Registration does not ask for one.
        $this->founder();

        $this->post(route('login.attempt'), ['email' => 'sarah@example.com', 'password' => 'anything'])
            ->assertSessionHasErrors(['password' => 'You have not set a password. Ask us to email you a link instead — the button is just below.']);

        $this->assertFalse(auth('founder')->check());
    }

    #[Test]
    public function a_wrong_password_does_not_say_which_half_was_wrong(): void
    {
        $this->founder(['password' => Hash::make('a-really-long-passphrase')]);

        $this->post(route('login.attempt'), ['email' => 'sarah@example.com', 'password' => 'not-it'])
            ->assertSessionHasErrors(['email' => 'That email address and password do not go together.']);

        $this->post(route('login.attempt'), ['email' => 'nobody@example.com', 'password' => 'not-it'])
            ->assertSessionHasErrors(['email' => 'That email address and password do not go together.']);
    }

    #[Test]
    public function a_founder_cannot_reach_the_admin_panel(): void
    {
        // Two guards on purpose. A family is not staff.
        $founder = $this->founder();
        $this->get(LoginLink::issue($founder));

        $this->assertTrue(auth('founder')->check());
        $this->get('/admin')->assertRedirectContains('/admin/login');
    }

    #[Test]
    public function logged_out_is_sent_to_the_sites_login_not_the_staff_one(): void
    {
        $this->get(route('verified'))->assertRedirect(route('login'));
    }

    #[Test]
    public function logging_out_ends_the_session(): void
    {
        $founder = $this->founder();
        $this->get(LoginLink::issue($founder));

        $this->post(route('logout'))->assertRedirect(route('home'));

        $this->assertFalse(auth('founder')->check());
    }

    #[Test]
    public function somebody_already_logged_in_is_not_shown_the_login_page(): void
    {
        $founder = $this->founder();
        $this->get(LoginLink::issue($founder));

        $this->get(route('login'))->assertRedirect();
    }
}
