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

/**
 * The ways in, and the one way back out.
 *
 * Registration never asks for a password, so the emailed link is the front
 * door of this whole design. All three of these were broken and none was
 * covered.
 */
class WayInTest extends TestCase
{
    use RefreshDatabase;

    private function family(array $overrides = []): Registration
    {
        return Registration::query()->create(array_merge([
            'email' => 'sarah@example.com',
            'first_name' => 'Sarah',
            'display_name' => 'Sarah',
            'postcode_outcode' => 'SS9',
            'email_verified_at' => now(),
            'completed_at' => now(),
            'consent_version' => config('frith.consent.version'),
            'consent_text' => config('frith.consent.text'),
            'consented_at' => now(),
        ], $overrides));
    }

    #[Test]
    public function asking_for_a_link_works_on_the_first_press(): void
    {
        // The link button used to sit in a form of its own carrying a hidden
        // old('email'), which is empty until something has already failed. So
        // the first press posted a blank address, every time, and the main way
        // into Frith told the family it needed an address they had just typed.
        Mail::fake();
        $family = $this->family();

        $this->post(route('login.attempt'), [
            'action' => 'link',
            'email' => 'sarah@example.com',
        ])->assertRedirect(route('link-sent'));

        Mail::assertSent(LoginLinkMail::class);
        $this->assertNotNull($family->fresh()->login_token);
    }

    #[Test]
    public function asking_for_a_link_needs_no_password(): void
    {
        // The password field shares the form now, so it must not be required
        // when the link button is the one pressed.
        Mail::fake();
        $this->family();

        $this->post(route('login.attempt'), [
            'action' => 'link',
            'email' => 'sarah@example.com',
        ])->assertSessionHasNoErrors();
    }

    #[Test]
    public function the_password_button_still_wants_a_password(): void
    {
        $this->family(['password' => Hash::make('a-long-enough-passphrase-11')]);

        $this->post(route('login.attempt'), [
            'action' => 'password',
            'email' => 'sarah@example.com',
        ])->assertSessionHasErrors('password');

        $this->post(route('login.attempt'), [
            'action' => 'password',
            'email' => 'sarah@example.com',
            'password' => 'a-long-enough-passphrase-11',
        ])->assertRedirect(route('verified'));

        $this->assertAuthenticated('founder');
    }

    #[Test]
    public function the_login_screen_carries_one_form_and_one_email_field(): void
    {
        $html = $this->get(route('login'))->assertOk()->getContent();

        // Two forms meant two email fields, and the second one was hidden and
        // empty. One form, one field, two buttons.
        $this->assertSame(1, substr_count($html, 'name="email"'));
        $this->assertStringContainsString('value="link"', $html);
        $this->assertStringContainsString('value="password"', $html);
    }

    // --- Leaving ------------------------------------------------------------

    #[Test]
    public function a_family_who_has_left_cannot_sign_back_in_with_a_password(): void
    {
        // Their password and their token survive until the purge runs a month
        // later. Without this they could carry on using the site, which is not
        // what the leaving screen told them would happen.
        $family = $this->family([
            'password' => Hash::make('a-long-enough-passphrase-11'),
            'deletion_requested_at' => now(),
        ]);

        $this->post(route('login.attempt'), [
            'action' => 'password',
            'email' => 'sarah@example.com',
            'password' => 'a-long-enough-passphrase-11',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('founder');
    }

    #[Test]
    public function a_family_who_has_left_is_sent_no_link_and_told_nothing(): void
    {
        Mail::fake();
        $this->family(['deletion_requested_at' => now()]);

        // The same screen either way: whether an address is on Frith is not
        // something this form should answer.
        $this->post(route('login.attempt'), [
            'action' => 'link',
            'email' => 'sarah@example.com',
        ])->assertRedirect(route('link-sent'));

        Mail::assertNothingSent();
    }

    #[Test]
    public function a_link_emailed_before_leaving_stops_working_after(): void
    {
        $family = $this->family();
        $url = LoginLink::issue($family);

        $family->forceFill(['deletion_requested_at' => now()])->save();

        $this->get($url)->assertRedirect(route('login'));
        $this->assertGuest('founder');
    }

    // --- The end of registration --------------------------------------------

    #[Test]
    public function the_last_screen_of_registration_is_not_a_dead_end(): void
    {
        // The village is behind the founder guard and finishing the form does
        // not sign anybody in, so this button used to send a family who had
        // just registered to a login screen they had no password for.
        $family = $this->family(['email_verified_at' => null]);

        $this->withSession(['registration.id' => $family->id]);

        $response = $this->get(route('register.welcome'))->assertOk();

        $response->assertDontSee(route('village'), false);
        $response->assertSee('sarah@example.com');
        $response->assertSee('you are registered either way');
    }

    #[Test]
    public function a_family_who_is_signed_in_gets_the_button(): void
    {
        $family = $this->family();

        $this->actingAs($family, 'founder')
            ->withSession(['registration.id' => $family->id])
            ->get(route('register.welcome'))
            ->assertOk()
            ->assertSee(route('village'), false);
    }
}
