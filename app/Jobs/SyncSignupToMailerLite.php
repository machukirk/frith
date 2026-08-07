<?php

namespace App\Jobs;

use App\Models\WaitlistSignup;
use App\Services\MailerLite;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Mirrors one signup's state into MailerLite.
 *
 * Queued and retried, because a MailerLite outage must never be something a
 * family signing up can see. The worst case is that the mirror lags and
 * `frith:mailerlite-backfill` catches it up.
 */
class SyncSignupToMailerLite implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900, 3600];

    public function __construct(
        public WaitlistSignup $signup,
        public bool $subscribed = true,
    ) {}

    public function handle(MailerLite $mailerLite): void
    {
        if (! $mailerLite->enabled()) {
            return;
        }

        // Re-read rather than trusting the flag: by the time this runs the
        // person may have confirmed and then changed their mind.
        $this->signup->refresh();

        try {
            $this->subscribed && ! $this->signup->hasUnsubscribed()
                ? $mailerLite->subscribe($this->signup)
                : $mailerLite->unsubscribe($this->signup);
        } catch (\Throwable $e) {
            report($e);

            // On a sync queue this runs inside the visitor's own request, so
            // throwing would turn a MailerLite outage into a failed
            // confirmation. Keeping the mirror in step is never worth that.
            // On a real queue it rethrows so the retries above apply.
            if (config('queue.default') === 'sync') {
                return;
            }

            throw $e;
        }
    }
}
