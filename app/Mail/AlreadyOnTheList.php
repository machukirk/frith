<?php

namespace App\Mail;

use App\Models\WaitlistSignup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * Sent when someone signs up with an address that is already confirmed.
 *
 * The page tells everyone to check their email, because saying anything else
 * would reveal who is on the list. This is the message that makes that promise
 * true for people who are already on it.
 */
class AlreadyOnTheList extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public WaitlistSignup $signup) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'You’re already on the Frith waiting list');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.already-on-the-list',
            with: ['unsubscribeUrl' => $this->unsubscribeUrl()],
        );
    }

    public function headers(): Headers
    {
        return new Headers(text: [
            'List-Unsubscribe' => '<'.$this->unsubscribeUrl().'>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ]);
    }

    private function unsubscribeUrl(): string
    {
        return URL::signedRoute('waitlist.unsubscribe', ['signup' => $this->signup->public_id]);
    }
}
