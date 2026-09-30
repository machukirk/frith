<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A message from the help page, on its way to the team.
 *
 * Sent rather than queued: somebody who has just written about a safeguarding
 * worry should not have it sitting in a jobs table if the worker is down, and
 * the form tells them it has been sent.
 *
 * replyTo is the person who wrote it, so hitting reply in the inbox does the
 * obvious thing.
 */
class ContactMessage extends Mailable
{
    use Queueable;
    use SerializesModels;

    /** @param array{name: string, email: string, topic: string, message: string} $fields */
    public function __construct(public array $fields) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Frith — '.$this->fields['topic'],
            replyTo: [$this->fields['email']],
        );
    }

    public function content(): Content
    {
        return new Content(text: 'emails.contact-message');
    }
}
