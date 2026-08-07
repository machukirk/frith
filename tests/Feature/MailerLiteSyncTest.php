<?php

namespace Tests\Feature;

use App\Jobs\SyncSignupToMailerLite;
use App\Models\WaitlistSignup;
use App\Services\MailerLite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
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
        // The site has to work before MailerLite is set up, and after it's
        // switched off again.
        config()->set('services.mailerlite.key', null);
        Http::fake();

        $signup = WaitlistSignup::factory()->confirmed()->create();
        app(MailerLite::class)->subscribe($signup);

        Http::assertNothingSent();
    }

    #[Test]
    public function confirming_queues_a_sync(): void
    {
        Bus::fake();
        $signup = WaitlistSignup::factory()->create();

        $this->get(URL::temporarySignedRoute('waitlist.confirm', now()->addDays(14), ['signup' => $signup->public_id]))
            ->assertOk();

        Bus::assertDispatched(SyncSignupToMailerLite::class, fn ($job) => $job->signup->is($signup) && $job->subscribed);
    }

    #[Test]
    public function signing_up_alone_does_not_queue_a_sync(): void
    {
        // An unconfirmed address hasn't been proved to belong to the person who
        // typed it. It must never reach the thing that does the sending.
        Bus::fake();

        $this->post(route('waitlist.store'), ['email' => 'sam@example.com'])
            ->assertSessionHasNoErrors();

        Bus::assertNotDispatched(SyncSignupToMailerLite::class);
    }

    #[Test]
    public function unsubscribing_queues_a_removal(): void
    {
        Bus::fake();
        $signup = WaitlistSignup::factory()->confirmed()->create();

        $this->get(URL::signedRoute('waitlist.unsubscribe', ['signup' => $signup->public_id]))
            ->assertOk();

        Bus::assertDispatched(SyncSignupToMailerLite::class, fn ($job) => ! $job->subscribed);
    }

    #[Test]
    public function a_confirmed_signup_is_posted_to_the_api_with_the_group(): void
    {
        Http::fake(['connect.mailerlite.com/*' => Http::response(['data' => ['id' => '1']], 200)]);

        $signup = WaitlistSignup::factory()->confirmed()->create(['email' => 'sam@example.com']);
        app(MailerLite::class)->subscribe($signup);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://connect.mailerlite.com/api/subscribers'
                && $request['email'] === 'sam@example.com'
                && $request['status'] === 'active'
                && $request['groups'] === ['12345']
                && $request->hasHeader('Authorization', 'Bearer test-key');
        });
    }

    #[Test]
    public function an_unconfirmed_signup_is_never_posted(): void
    {
        Http::fake();

        app(MailerLite::class)->subscribe(WaitlistSignup::factory()->create());

        Http::assertNothingSent();
    }

    #[Test]
    public function unsubscribing_marks_rather_than_deletes(): void
    {
        // A deleted subscriber can be re-added by a later import. An
        // unsubscribed one is a standing instruction not to.
        Http::fake(['connect.mailerlite.com/*' => Http::response([], 200)]);

        app(MailerLite::class)->unsubscribe(
            WaitlistSignup::factory()->unsubscribed()->create(['email' => 'sam@example.com'])
        );

        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request['status'] === 'unsubscribed'
            && $request['email'] === 'sam@example.com');
    }

    #[Test]
    public function the_job_rereads_state_before_sending(): void
    {
        // Someone can confirm and then immediately unsubscribe. Whichever job
        // runs last must reflect where they actually ended up.
        Http::fake(['connect.mailerlite.com/*' => Http::response([], 200)]);

        $signup = WaitlistSignup::factory()->confirmed()->create();
        $job = new SyncSignupToMailerLite($signup, subscribed: true);

        $signup->forceFill(['unsubscribed_at' => now()])->save();

        $job->handle(app(MailerLite::class));

        Http::assertSent(fn ($request) => $request['status'] === 'unsubscribed');
    }

    #[Test]
    public function a_mailerlite_outage_never_breaks_a_signup(): void
    {
        // The database is the source of truth. MailerLite being down is a
        // mirror-lag problem, not a signup problem.
        Http::fake(['connect.mailerlite.com/*' => Http::response('upstream error', 500)]);

        $signup = WaitlistSignup::factory()->create();

        $this->get(URL::temporarySignedRoute('waitlist.confirm', now()->addDays(14), ['signup' => $signup->public_id]))
            ->assertOk();

        $this->assertNotNull($signup->refresh()->confirmed_at);
    }
}
