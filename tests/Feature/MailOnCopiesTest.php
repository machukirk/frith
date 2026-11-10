<?php

namespace Tests\Feature;

use App\Mail\FounderWelcome;
use App\Models\Registration;
use App\Providers\AppServiceProvider;
use App\Support\SearchVisibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The guard that stops a copy of the site emailing a real family.
 *
 * Staging runs against a copy of the database. Give it a working mail service
 * with no guard and the first test registration writes to somebody who never
 * asked to hear from a staging server.
 */
class MailOnCopiesTest extends TestCase
{
    use RefreshDatabase;

    private function family(): Registration
    {
        return Registration::query()->create([
            'email' => 'a-real-family@example.com',
            'first_name' => 'Sarah',
            'postcode_outcode' => 'SS9',
            'consent_version' => config('frith.consent.version'),
            'consent_text' => config('frith.consent.text'),
            'consented_at' => now(),
        ]);
    }

    #[Test]
    public function whether_this_is_the_real_site_is_decided_by_host_alone(): void
    {
        config(['frith.site.canonical_host' => 'frith.community']);

        $real = Request::create('https://frith.community/');
        $copy = Request::create('https://phplaravel-1192847-6702594.cloudwaysapps.com/');

        $this->assertTrue(SearchVisibility::isRealSite($real));
        $this->assertFalse(SearchVisibility::isRealSite($copy));

        // A staging site is usually stood up by copying production's .env, so
        // the flag that lets somebody preview a page must not be able to make
        // a copy behave as the original.
        config(['frith.site.indexable' => true]);
        $this->assertFalse(SearchVisibility::isRealSite($copy));
        $this->assertTrue(SearchVisibility::indexable($copy));
    }

    /** The addresses a message actually went to, through the real mailer. */
    private function sendAndReadRecipients(Registration $family): array
    {
        config(['mail.default' => 'array']);

        // The real mailer, not Mail::fake(): the fake replaces the mailer and
        // loses the alwaysTo setting that is the whole point of this guard.
        Mail::to($family->email)->sendNow(new FounderWelcome($family));

        $messages = Mail::mailer('array')->getSymfonyTransport()->messages();

        return collect($messages)
            ->flatMap(fn ($m) => collect($m->getOriginalMessage()->getTo())->map->getAddress())
            ->all();
    }

    #[Test]
    public function a_copy_of_the_site_sends_every_email_to_one_address(): void
    {
        config([
            'frith.site.canonical_host' => 'frith.community',
            'frith.mail.catch' => 'matt@example.com',
        ]);

        $this->app->instance('request', Request::create('https://staging.example.com/'));
        (new AppServiceProvider($this->app))->boot();

        $to = $this->sendAndReadRecipients($this->family());

        // Addressed to the family, delivered to the one address instead.
        $this->assertContains('matt@example.com', $to);
        $this->assertNotContains('a-real-family@example.com', $to);
    }

    #[Test]
    public function the_real_site_emails_the_family_it_was_addressed_to(): void
    {
        config([
            'frith.site.canonical_host' => 'frith.community',
            'frith.mail.catch' => 'matt@example.com',
        ]);

        $this->app->instance('request', Request::create('https://frith.community/'));
        (new AppServiceProvider($this->app))->boot();

        $to = $this->sendAndReadRecipients($this->family());

        $this->assertContains('a-real-family@example.com', $to);
        $this->assertNotContains('matt@example.com', $to);
    }

    #[Test]
    public function a_login_link_can_be_issued_without_sending_anything(): void
    {
        // Registration never asks for a password, so the emailed link is the
        // only way in. On a copy of the site that mail goes to a log file, and
        // in support somebody occasionally cannot receive our mail at all.
        $family = $this->family();

        Mail::fake();

        $this->artisan('frith:login-link', ['email' => 'a-real-family@example.com'])
            ->assertSuccessful();

        Mail::assertNothingSent();

        $family->refresh();
        $this->assertNotNull($family->login_token);
        $this->assertTrue($family->login_token_expires_at->isFuture());
    }

    #[Test]
    public function a_link_is_refused_for_an_address_we_do_not_hold(): void
    {
        $this->artisan('frith:login-link', ['email' => 'nobody@example.com'])
            ->assertFailed();
    }

    #[Test]
    public function a_link_is_refused_for_a_family_who_has_left(): void
    {
        $family = $this->family();
        $family->forceFill(['deletion_requested_at' => now()])->save();

        // Issuing one would let them back into an account they have closed.
        $this->artisan('frith:login-link', ['email' => 'a-real-family@example.com'])
            ->assertFailed();
    }

    #[Test]
    public function a_signed_link_built_off_a_request_still_comes_out_as_https(): void
    {
        // The Founder email's confirm link is signed inside a queue worker,
        // which has no request to infer the scheme from. This used to be keyed
        // on the production environment name, so every signed link a staging
        // worker produced came out http:// and broke its own signature.
        config(['app.url' => 'https://phplaravel-1192847-6702594.cloudwaysapps.com']);

        (new AppServiceProvider($this->app))->boot();

        $family = $this->family();

        $this->assertStringStartsWith('https://', URL::signedRoute(
            'register.verify',
            ['registration' => $family->public_id],
        ));
    }
}
