<?php

namespace Tests\Feature;

use App\Models\Registration;
use App\Services\MailerLite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MailerLiteSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.mailerlite.key', 'test-key');
        config()->set('services.mailerlite.group_id', '12345');
    }

    #[Test]
    public function nothing_is_sent_when_no_api_key_is_configured(): void
    {
        // The site has to work before MailerLite is set up, and after it is
        // switched off again.
        config()->set('services.mailerlite.key', null);
        Http::fake();

        app(MailerLite::class)->subscribe(Registration::factory()->verified()->create());

        Http::assertNothingSent();
    }

    #[Test]
    public function a_verified_registration_is_posted_to_the_api_with_the_group(): void
    {
        Http::fake(['connect.mailerlite.com/*' => Http::response(['data' => ['id' => '1']], 200)]);

        app(MailerLite::class)->subscribe(
            Registration::factory()->verified()->create(['email' => 'sam@example.com'])
        );

        Http::assertSent(function ($request) {
            return $request->url() === 'https://connect.mailerlite.com/api/subscribers'
                && $request['email'] === 'sam@example.com'
                && $request['status'] === 'active'
                && $request['groups'] === ['12345']
                && $request->hasHeader('Authorization', 'Bearer test-key');
        });
    }

    #[Test]
    public function an_unverified_registration_is_never_posted(): void
    {
        // Registering does not prove the address belongs to whoever typed it.
        // Unverified addresses must never reach the thing that does the sending.
        Http::fake();

        app(MailerLite::class)->subscribe(Registration::factory()->create());

        Http::assertNothingSent();
    }

    #[Test]
    public function someone_who_unsubscribed_is_never_re_added(): void
    {
        Http::fake();

        app(MailerLite::class)->subscribe(Registration::factory()->unsubscribed()->create());

        Http::assertNothingSent();
    }

    #[Test]
    public function unsubscribing_marks_rather_than_deletes(): void
    {
        // A deleted subscriber can be re-added by a later import. An
        // unsubscribed one is a standing instruction not to.
        Http::fake(['connect.mailerlite.com/*' => Http::response([], 200)]);

        app(MailerLite::class)->unsubscribe(
            Registration::factory()->unsubscribed()->create(['email' => 'sam@example.com'])
        );

        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request['status'] === 'unsubscribed'
            && $request['email'] === 'sam@example.com');
    }

    #[Test]
    public function registering_alone_does_not_sync_anybody(): void
    {
        // Nothing dispatches a sync yet, because nothing verifies an address
        // yet. This is the guard that stops that changing by accident.
        Http::fake();

        $this->post(route('register.step.store', 'you'), [
            'first_name' => 'Sam',
            'email' => 'sam@example.com',
            'postcode_outcode' => 'SS9',
        ])->assertSessionHasNoErrors();

        $this->post(route('register.step.store', 'areas'), ['support_areas' => ['identity-belonging']]);

        Http::assertNothingSent();
    }
}
