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

class ConfirmWaitlistSignup extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public WaitlistSignup $signup) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'One tap to join the Frith waiting list');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.confirm-signup',
            with: [
                'confirmUrl' => URL::temporarySignedRoute(
                    'waitlist.confirm',
                    now()->addDays((int) config('frith.waitlist.confirmation_link_days')),
                    ['signup' => $this->signup->public_id],
                ),
                // "I didn't sign up" — the way out for someone whose address was
                // typed into the form by a stranger. Never expires.
                'unsubscribeUrl' => $this->unsubscribeUrl(),
                'days' => (int) config('frith.waitlist.confirmation_link_days'),
            ],
        );
    }

    /**
     * Lets a mail client show its own unsubscribe control, so leaving never
     * depends on finding a link in the body. Gmail and Outlook both expect
     * these on bulk mail and it protects deliverability at launch.
     */
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
