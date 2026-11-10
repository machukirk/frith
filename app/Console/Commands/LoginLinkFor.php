<?php

namespace App\Console\Commands;

use App\Models\Registration;
use App\Support\LoginLink;
use Illuminate\Console\Command;

/**
 * Prints a working login link for one family, without sending anything.
 *
 * Registration never asks for a password, so the emailed link is the only way
 * in. That is right for families and awkward everywhere else: on staging mail
 * goes to a log file, and in support somebody occasionally cannot receive our
 * mail at all. Both cases need a link in a hand rather than in an inbox.
 *
 * The link is the same one-use, one-hour token the email carries, so printing
 * one invalidates any link already outstanding for that family. That is the
 * point: there is never more than one way in at a time.
 */
class LoginLinkFor extends Command
{
    protected $signature = 'frith:login-link {email : The address they registered with}';

    protected $description = 'Print a one-use login link for a family, without emailing it';

    public function handle(): int
    {
        $email = Registration::normaliseEmail($this->argument('email'));

        $registration = Registration::query()->where('email', $email)->first();

        if ($registration === null) {
            $this->error('No registration for '.$email.'.');

            return self::FAILURE;
        }

        if ($registration->hasLeft()) {
            $this->error('That family has asked to leave. Issuing a link would let them back in.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->line('  <fg=green>'.LoginLink::issue($registration).'</>');
        $this->newLine();
        $this->line('  One use, and it expires in '.LoginLink::LIFETIME_MINUTES.' minutes.');
        $this->line('  Any link already sent to them has just stopped working.');
        $this->newLine();

        return self::SUCCESS;
    }
}
