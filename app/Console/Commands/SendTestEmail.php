<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Sends a real email to prove the mail path works.
 *
 * It renders the actual mail layout rather than plain text, so it also proves
 * the from address passes domain verification and the logo resolves — the two
 * things that are specific to this app rather than to the mailer.
 *
 * Once registration sends a Founder email this should send that instead, since
 * the most useful test is the message people actually receive.
 */
class SendTestEmail extends Command
{
    protected $signature = 'frith:test-email {email : Where to send it}';

    protected $description = 'Send a real email to check the mail path';

    public function handle(): int
    {
        $email = $this->argument('email');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("'{$email}' is not an email address.");

            return self::FAILURE;
        }

        $this->line('  Mailer:  '.config('mail.default'));
        $this->line('  From:    '.config('mail.from.address'));
        $this->line('  APP_URL: '.config('app.url'));
        $this->newLine();

        try {
            // sendNow, not queue: the point is to find out here and now whether
            // it works, not to hand it to a worker and hope.
            Mail::send('emails.test', [], function ($message) use ($email) {
                $message->to($email)->subject('Frith — mail path check');
            });
        } catch (\Throwable $e) {
            $this->error('  Failed: '.$e::class);
            $this->line('  '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("  Sent to {$email}.");

        return self::SUCCESS;
    }
}
