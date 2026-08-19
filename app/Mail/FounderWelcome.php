<?php

namespace App\Mail;

use App\Models\Registration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * The one email a Founder gets when they register.
 *
 * It does two jobs so nobody has to read two messages: it tells them they are
 * registered and which Founder they are, and it carries the link that proves
 * the address is theirs. Nothing is gated on that link — they are registered
 * either way — but until it is clicked they are not on the list the launch
 * email actually goes to.
 *
 * Sent with a delay on purpose. Somebody halfway through the form does not need
 * their phone buzzing at them, and by the time it lands most people have either
 * finished or stopped, so one message reads correctly for both.
 */
class FounderWelcome extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Registration $registration) {}

    public function envelope(): Envelope
    {
        $number = $this->registration->founder_number;

        return new Envelope(
            subject: $number ? "You’re Frith Founder #{$number}" : 'You’re a Frith Founder',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.founder-welcome',
            text: 'emails.founder-welcome-text',
            with: [
                'verifyUrl' => URL::signedRoute(
                    'register.verify',
                    ['registration' => $this->registration->public_id],
                ),
                'unsubscribeUrl' => $this->unsubscribeUrl(),
                'finishUrl' => route('register.start'),
            ],
        );
    }

    /**
     * Lets a mail client show its own unsubscribe control, so leaving never
     * depends on finding a link in the body. Gmail and Outlook both expect
     * these, and they protect deliverability at launch.
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
        // No expiry. It goes in every email, and a way out that stops working
        // is not a way out.
        return URL::signedRoute('register.unsubscribe', ['registration' => $this->registration->public_id]);
    }
}
