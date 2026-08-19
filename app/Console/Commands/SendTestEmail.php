<?php

namespace App\Console\Commands;

use App\Mail\FounderWelcome;
use App\Models\Registration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Sends a real email to prove the mail path works.
 *
 * It sends the real Founder email, because the most useful check is the exact
 * message people receive: it proves the from address passes domain
 * verification, the logo resolves over the public URL, and the signed links
 * are built against the right domain. Nothing is written to the database —
 * the registration behind it exists only for the length of the command.
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
            // sendNow rather than send: the mailable is queued by default,
            // and handing it to a worker would prove nothing here.
            Mail::to($email)->sendNow(new FounderWelcome($this->sample()));
        } catch (\Throwable $e) {
            $this->error('  Failed: '.$e::class);
            $this->line('  '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("  Sent to {$email}.");
        $this->line('  <fg=gray>It is the real Founder email. The links in it are signed and will not resolve to anybody.</>');

        return self::SUCCESS;
    }

    /**
     * A Founder who does not exist and never will. Unsaved, so running this
     * against production cannot leave a stray row or burn a Founder number.
     */
    private function sample(): Registration
    {
        return new Registration([
            'public_id' => (string) Str::ulid(),
            'first_name' => 'Test',
            'email' => 'test@frith.community',
            'founder_number' => 0,
            'completed_at' => now(),
        ]);
    }
}
