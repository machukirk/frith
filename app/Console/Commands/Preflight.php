<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Models\User;
use App\Models\WaitlistSignup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Checks the things that break signups quietly.
 *
 * Every failure below has the same shape: the site looks fine, the form says
 * "check your email", and no email ever arrives. Nobody complains, because the
 * people affected have no way to tell you. So it gets checked deliberately.
 */
class Preflight extends Command
{
    protected $signature = 'frith:preflight';

    protected $description = 'Check this environment is ready to take real signups';

    private int $failures = 0;

    private int $warnings = 0;

    private bool $databaseUp = false;

    public function handle(): int
    {
        $this->newLine();
        $this->line('  <fg=gray>Environment:</> '.app()->environment().'   <fg=gray>URL:</> '.config('app.url'));
        $this->newLine();

        $this->checkAppKey();
        $this->checkAppUrl();
        $this->checkDatabase();
        $this->checkMail();
        $this->checkQueue();
        $this->checkStorage();
        $this->checkContent();
        $this->checkAccounts();
        $this->checkCaches();

        $this->newLine();

        if ($this->failures > 0) {
            $this->error("  {$this->failures} problem(s) would stop people signing up. Fix before launch.");

            return self::FAILURE;
        }

        if ($this->warnings > 0) {
            $this->warn("  Ready, with {$this->warnings} thing(s) worth a look.");

            return self::SUCCESS;
        }

        $this->info('  All good.');

        return self::SUCCESS;
    }

    private function checkAppKey(): void
    {
        $key = config('app.key');

        $key
            ? $this->ok('APP_KEY is set', 'Never change it — it signs every confirmation link')
            : $this->bad('APP_KEY is empty', 'Run: php artisan key:generate');
    }

    private function checkAppUrl(): void
    {
        $url = (string) config('app.url');

        $isLocalUrl = Str::contains($url, ['localhost', '127.0.0.1', '.ddev.site', '.test']);

        // A local URL is correct locally. Anywhere else it means every link in
        // every confirmation email points at a machine nobody can reach.
        if ($isLocalUrl && ! app()->environment('local')) {
            $this->bad(
                "APP_URL is {$url}",
                'Confirmation emails are built by the queue worker, which has no request to read the domain from. Every link would point here.',
            );

            return;
        }

        if (app()->isProduction() && ! Str::startsWith($url, 'https://')) {
            $this->bad("APP_URL is not https ({$url})", 'Signed links break on the redirect to https');

            return;
        }

        $this->ok("APP_URL is {$url}");
    }

    private function checkDatabase(): void
    {
        try {
            DB::connection()->getPdo();
            $this->databaseUp = true;
            $this->ok('Database reachable');
        } catch (\Throwable $e) {
            // Everything below this reads from the database. Report the cause
            // once and carry on, rather than throwing a stack trace over the
            // rest of the checks the operator still needs to see.
            $this->bad('Cannot connect to the database', $e->getMessage());
        }
    }

    private function checkMail(): void
    {
        $mailer = config('mail.default');
        $from = config('mail.from.address');

        if (in_array($mailer, ['log', 'array', null], true)) {
            $this->bad("Mail driver is '{$mailer}'", 'Nothing would actually be sent. Set MAIL_MAILER and its credentials.');
        } else {
            $this->ok("Mail driver is '{$mailer}'");
        }

        if (! $from || Str::contains((string) $from, 'example')) {
            $this->bad('MAIL_FROM_ADDRESS is not set to a real address', 'Set it to hello@frith.community');
        } else {
            $this->ok("Sending as {$from}");
        }

        if (app()->isProduction() && ! config('frith.company.postal_address')) {
            $this->note('No postal address in the email footer', 'Expected on bulk mail by the big inbox providers. Set frith.company.postal_address before the launch broadcast.');
        }
    }

    private function checkQueue(): void
    {
        $connection = config('queue.default');

        if ($connection === 'sync') {
            $this->note('Queue is running synchronously', 'Emails send inside the web request. Works, but a mail outage becomes a failed signup.');

            return;
        }

        $this->ok("Queue connection is '{$connection}'");

        if ($connection !== 'database' || ! $this->databaseUp) {
            return;
        }

        // A worker that isn't running looks exactly like everything being fine,
        // right up until someone checks why nobody has confirmed.
        $pending = DB::table('jobs')->count();
        $stuck = DB::table('jobs')->where('created_at', '<', now()->subMinutes(10)->getTimestamp())->count();
        $failed = DB::table('failed_jobs')->count();

        if ($stuck > 0) {
            $this->bad("{$stuck} job(s) queued for over 10 minutes", 'The queue worker is not running. Nobody is receiving a confirmation email.');
        } elseif ($pending > 0) {
            $this->ok("{$pending} job(s) queued and moving");
        } else {
            $this->ok('No stuck jobs');
        }

        if ($failed > 0) {
            $this->note("{$failed} failed job(s)", 'Inspect with: php artisan queue:failed');
        }
    }

    private function checkStorage(): void
    {
        is_link(public_path('storage'))
            ? $this->ok('Storage is linked')
            : $this->note('public/storage is not linked', 'Uploaded hero images would 404. Run: php artisan storage:link');
    }

    private function checkContent(): void
    {
        if (! $this->databaseUp) {
            return;
        }

        $page = Page::query()->where('slug', 'coming-soon')->first();

        $page
            ? $this->ok('Coming soon content is in the database', 'Editable at /admin')
            : $this->note('No content row yet', 'The page falls back to config/frith.php, so it still renders. Run: php artisan db:seed');
    }

    private function checkAccounts(): void
    {
        if (! $this->databaseUp) {
            return;
        }

        $owners = User::query()->where('role', 'owner')->count();
        $signups = WaitlistSignup::query()->count();

        $owners > 0
            ? $this->ok("{$owners} owner account(s)")
            : $this->note('No owner account', 'Nobody can see the waiting list. Run: php artisan frith:admin');

        if ($signups > 0) {
            $confirmed = WaitlistSignup::query()->mailable()->count();
            $this->ok("{$signups} signup(s), {$confirmed} confirmed");
        }
    }

    private function checkCaches(): void
    {
        if (! app()->isProduction()) {
            return;
        }

        // Keyed by the artisan command, which is singular even though the
        // cache file for routes is not.
        foreach (['config' => 'config.php', 'route' => 'routes-v7.php'] as $command => $file) {
            file_exists(base_path("bootstrap/cache/{$file}"))
                ? $this->ok(ucfirst($command).' cached')
                : $this->note(ucfirst($command).' not cached', "Run: php artisan {$command}:cache");
        }
    }

    private function ok(string $message, ?string $note = null): void
    {
        $this->line("  <fg=green>✓</> {$message}".($note ? "  <fg=gray>{$note}</>" : ''));
    }

    private function bad(string $message, ?string $fix = null): void
    {
        $this->failures++;
        $this->line("  <fg=red>✕</> <options=bold>{$message}</>");
        if ($fix) {
            $this->line("    <fg=gray>{$fix}</>");
        }
    }

    private function note(string $message, ?string $fix = null): void
    {
        $this->warnings++;
        $this->line("  <fg=yellow>!</> {$message}");
        if ($fix) {
            $this->line("    <fg=gray>{$fix}</>");
        }
    }
}
