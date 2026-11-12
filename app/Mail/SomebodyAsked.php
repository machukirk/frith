<?php

namespace App\Mail;

use App\Models\Registration;
use App\Support\FamilyCard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * "A family has asked to connect."
 *
 * The one email the free plan sends about somebody else, and the reason the
 * whole notification design hangs together: a request nobody is told about
 * sits unseen for weeks, and the family who asked reads that as being
 * ignored.
 *
 * Everything it says about the asker comes from App\Support\FamilyCard, which
 * is the single place that decides what crosses between two families before
 * they have agreed to talk. An email that assembled its own version of that
 * would be a second answer to the same question, in a channel nobody is
 * looking at, which is exactly how a promise like this gets broken quietly.
 *
 * Queued, unlike the login link. Nobody is sitting on a screen waiting for
 * it, and the design says in as many words that there is no rush.
 */
class SomebodyAsked extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Registration $recipient,
        public Registration $asker,
    ) {}

    public function envelope(): Envelope
    {
        // Not the asker's name. A subject line is readable from a lock screen
        // by whoever picks the phone up, and who is asking is the part of
        // this that belongs inside.
        return new Envelope(subject: 'A family has asked to connect');
    }

    public function content(): Content
    {
        $card = FamilyCard::make(
            $this->recipient->loadMissing(['children', 'experiences']),
            $this->asker->loadMissing(['children', 'experiences']),
        );

        return new Content(
            view: 'emails.somebody-asked',
            text: 'emails.somebody-asked-text',
            with: [
                'card' => $card,
                'name' => $this->asker->displayName(),
                'area' => $this->asker->area(),
                'children' => $card->childSummary(),
                'commonality' => $card->commonality(),
                'url' => route('requests'),
            ],
        );
    }
}
