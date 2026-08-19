<?php

namespace App\Console\Commands;

use App\Jobs\SyncSignupToMailerLite;
use App\Models\Registration;
use App\Services\MailerLite;
use Illuminate\Console\Command;

/**
 * Pushes everyone who should be in MailerLite into MailerLite.
 *
 * Needed twice: the first time the API key is set, when a backlog of confirmed
 * signups already exists, and any time the mirror drifts — a long outage, a
 * failed batch of jobs, or a group that was recreated by hand.
 *
 * Safe to run repeatedly. The API upserts by email.
 */
class BackfillMailerLite extends Command
{
    protected $signature = 'frith:mailerlite-backfill {--dry-run : Show what would be sent without sending it}';

    protected $description = 'Sync every verified registration to MailerLite';

    public function handle(MailerLite $mailerLite): int
    {
        if (! $mailerLite->enabled()) {
            $this->error('MAILERLITE_API_KEY is not set.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        // Verified only — the same rule the service applies.
        $subscribe = Registration::query()->whereNotNull('email_verified_at')->whereNull('unsubscribed_at');
        $unsubscribe = Registration::query()->whereNotNull('unsubscribed_at');

        $this->line("  Verified and subscribed: {$subscribe->count()}");
        $this->line("  Unsubscribed: {$unsubscribe->count()}");

        if ($dryRun) {
            $this->newLine();
            $this->info('  Dry run — nothing sent.');

            return self::SUCCESS;
        }

        $queued = 0;

        // Queued rather than sent inline: a few thousand synchronous API calls
        // would sit here for a long time and lose everything if it were killed.
        $subscribe->chunkById(200, function ($rows) use (&$queued) {
            foreach ($rows as $registration) {
                SyncSignupToMailerLite::dispatch($registration);
                $queued++;
            }
        });

        $unsubscribe->chunkById(200, function ($rows) use (&$queued) {
            foreach ($rows as $registration) {
                SyncSignupToMailerLite::dispatch($registration, subscribed: false);
                $queued++;
            }
        });

        $this->newLine();
        $this->info("  Queued {$queued} sync job(s). Make sure a queue worker is running.");

        return self::SUCCESS;
    }
}
