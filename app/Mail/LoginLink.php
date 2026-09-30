<?php

namespace App\Mail;

use App\Models\Registration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * "Here is your way in."
 *
 * Sent, not queued: somebody is sitting on the "check your email" screen
 * waiting for it, and a link that arrives when the queue worker next runs is
 * a link that arrives too late to be useful.
 */
class LoginLink extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Registration $registration,
        public string $url,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your link to log in to Frith');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.login-link',
            text: 'emails.login-link-text',
            with: ['minutes' => \App\Support\LoginLink::LIFETIME_MINUTES],
        );
    }
}
