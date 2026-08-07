<?php

namespace App\Console\Commands;

use App\Mail\ConfirmWaitlistSignup;
use App\Models\WaitlistSignup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Sends a real email to prove the mail path works.
 *
 * Deliberately sends the actual confirmation mailable rather than a "hello
 * world", because the things most likely to be wrong are specific to it: the
 * from address failing domain verification, the logo URL 404ing, or the signed
 * link being built against the wrong APP_URL.
 *
 * Nothing is written to the database — the signup it renders is in memory only.
 */
class SendTestEmail extends Command
{
    protected $signature = 'frith:test-email {email : Where to send it}';

    protected $description = 'Send a real confirmation email to check the mail path';

    public function handle(): int
    {
        $email = $this->argument('email');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("'{$email}' is not an email address.");

            return self::FAILURE;
        }

        $this->line('  Mailer:    '.config('mail.default'));
        $this->line('  From:      '.config('mail.from.address'));
        $this->line('  APP_URL:   '.config('app.url'));
        $this->newLine();

        $signup = new WaitlistSignup([
            'email' => $email,
            'source' => 'test-email',
            'consent_version' => config('frith.consent.version'),
            'consent_text' => config('frith.consent.text'),
            'consented_at' => now(),
        ]);
        $signup->public_id = (string) Str::ulid();

        try {
            // sendNow, not queue: the point is to find out here and now whether
            // it works, not to hand it to a worker and hope.
            Mail::to($email)->sendNow(new ConfirmWaitlistSignup($signup));
        } catch (\Throwable $e) {
            $this->error('  Failed: '.$e::class);
            $this->line('  '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("  Sent to {$email}.");
        $this->line('  The confirm link in it points at a signup that was never saved, so it will 404 — that is expected.');

        return self::SUCCESS;
    }
}
