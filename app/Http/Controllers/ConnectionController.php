<?php

namespace App\Http\Controllers;

use App\Mail\SomebodyAsked;
use App\Models\Connection;
use App\Models\Journey;
use App\Models\Registration;
use App\Support\FamilyCard;
use App\Support\NotificationSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * Asking to connect, and answering.
 *
 * Nobody is put in front of anybody without both families agreeing. A request
 * is pending until it is answered; answering no keeps the row, so the same
 * family is not offered again after somebody has said no once.
 *
 * Three facts live in the one table and they are not the same fact. A pending
 * row is a question nobody has answered. A declined row is a no, kept on
 * purpose. A withdrawn row is neither, so it is removed: see withdraw().
 */
class ConnectionController extends Controller
{
    /**
     * How many of your own asks can be waiting at once.
     *
     * The help page and the hi-fi both put the number at ten, and until now
     * nothing enforced it. It is a cap on asks waiting for a reply, not on
     * asks ever made, so answering or withdrawing one frees a place.
     */
    public const PENDING_LIMIT = 10;

    /** The second button on the ask form. See the family profile. */
    private const NOT_FOR_US = 'not-for-us';

    public function index(Request $request): View
    {
        $family = $request->user('founder')->loadMissing(['children', 'experiences']);
        $hidden = $family->separatedIds();

        $connections = Connection::query()
            ->for($family)
            ->with(['requester.children', 'requester.experiences', 'addressee.children', 'addressee.experiences', 'journey'])
            ->latest('id')
            ->get()
            ->reject(fn (Connection $c) => in_array($c->otherThan($family)->id, $hidden, true)
                || $c->otherThan($family)->hasLeft());

        return view('pages.requests.index', [
            'family' => $family,
            'askedYou' => $connections->filter(fn ($c) => $c->addressee_id === $family->id && $c->status === Connection::PENDING),
            'youAsked' => $connections->filter(fn ($c) => $c->requester_id === $family->id && $c->status === Connection::PENDING),
            'closed' => $connections->filter(fn ($c) => $c->status === Connection::DECLINED),
            'limit' => self::PENDING_LIMIT,
            'cardFor' => fn (Connection $c) => FamilyCard::make($family, $c->otherThan($family)),
        ]);
    }

    public function store(Request $request, string $publicId): RedirectResponse
    {
        $family = $request->user('founder');
        $other = Registration::query()->where('public_id', $publicId)->firstOrFail();

        abort_if($other->is($family) || $other->hasLeft() || $family->isSeparatedFrom($other), 404);

        $existing = Connection::between($family, $other);

        // "Not for us" is the other button on the same form, so dismissing a
        // family needs no JavaScript and no route of its own. It is read
        // before the check below, because setting somebody aside has to work
        // whether or not there is already a row between the two families.
        if ($request->input('action') === self::NOT_FOR_US) {
            return $this->setAside($family, $other, $existing);
        }

        if ($existing) {
            return back()->with('status', 'That is already in hand.');
        }

        $waiting = $this->waitingOn($family);

        if ($waiting->count() >= self::PENDING_LIMIT) {
            return back()->with('status', $this->atTheLimit($waiting));
        }

        $journey = $request->filled('journey')
            ? Journey::query()->where('slug', $request->string('journey'))->first()
            : null;

        Connection::query()->create([
            'requester_id' => $family->id,
            'addressee_id' => $other->id,
            'journey_id' => $journey?->id,
            'status' => Connection::PENDING,
        ]);

        $told = $this->tell($other, $family);

        return back()->with('status', $told
            ? 'Asked. '.$other->displayName().' has been told.'
            : 'Asked. '.$other->displayName().' will see it when they next look.');
    }

    public function accept(Request $request, Connection $connection): RedirectResponse
    {
        $this->mineToAnswer($request, $connection);

        $connection->update(['status' => Connection::ACCEPTED, 'responded_at' => now()]);

        return redirect()
            ->route('messages.show', $connection)
            ->with('status', 'You are connected. Say what you like.');
    }

    public function decline(Request $request, Connection $connection): RedirectResponse
    {
        $this->mineToAnswer($request, $connection);

        // Kept rather than deleted, so nobody is offered twice.
        $connection->update(['status' => Connection::DECLINED, 'responded_at' => now()]);

        return back()->with('status', 'That one is closed. Nobody is told.');
    }

    /**
     * Taking back an ask nobody has answered.
     *
     * The row is deleted, and that is the whole decision. A decline is kept
     * deliberately, so that a family who has said no is never put in front of
     * the same people again. A withdrawal is a different fact: nothing was
     * refused, and the other family may never have seen the ask at all.
     * Keeping it as a closed row would spend that family for good on a
     * question they were never asked, and it would read as their no in every
     * query that counts declines.
     *
     * Considered and rejected: a fourth status, WITHDRAWN. Nobody reads it,
     * and every query about who has said no would have to learn to skip it.
     * Do not add one without a screen that shows it.
     */
    public function withdraw(Request $request, Connection $connection): RedirectResponse
    {
        $family = $request->user('founder');

        // Only the family who asked, and only while it is still a question.
        abort_unless($connection->requester_id === $family->id, 403);
        abort_unless($connection->status === Connection::PENDING, 409);

        $other = $connection->addressee;

        $connection->delete();

        return back()->with('status', 'Taken back. '.$other->displayName().' was never told you asked.');
    }

    /**
     * "Not for us", from a family's profile.
     *
     * The same thing declining does: a closed row, kept, so they are not
     * suggested again. Nothing is sent and nobody is told, which is what the
     * button on the profile says.
     */
    private function setAside(Registration $family, Registration $other, ?Connection $existing): RedirectResponse
    {
        if ($existing?->isAccepted()) {
            return back()->with('status', 'You are already connected to '.$other->displayName().'. Block them if you would rather they could not see you.');
        }

        if ($existing) {
            // A real ask, really answered. closed_by stays null, which is what
            // null has always meant here.
            $existing->update(['status' => Connection::DECLINED, 'responded_at' => now()]);
        } else {
            // No request was ever made here, so the row is written closed. It
            // exists only to keep this family off the village: Suggestions
            // skips anybody with a row either way round.
            //
            // closed_by matters. Without it this row is byte-identical to
            // "you asked them and they said no", about two families where no
            // ask happened, and withdraw() a few lines up deletes its row
            // rather than leave that kind of lie behind.
            Connection::query()->create([
                'requester_id' => $family->id,
                'addressee_id' => $other->id,
                'status' => Connection::DECLINED,
                'closed_by' => $family->id,
                'responded_at' => now(),
            ]);
        }

        return back()->with('status', 'Closed. '.$other->displayName().' will not be suggested again, and they are not told.');
    }

    /**
     * Emails the family who has been asked, if the rules say so.
     *
     * The sender's plan decides, not the recipient's. A Frith+ member's ask
     * always lands, because that is what their membership buys: their
     * requests are seen. A free-plan ask lands only if the recipient pays for
     * "any family asks to connect".
     *
     * This is the one place somebody else's membership changes what we send
     * you, and it is in the asker's favour, so a paid request is never missed.
     * Both halves live in App\Support\NotificationSettings; neither is
     * decided here.
     *
     * Returns whether anything was sent, so the screen can say which it was
     * rather than implying the other family has been told when they have not.
     */
    private function tell(Registration $recipient, Registration $asker): bool
    {
        $send = $asker->hasPlus() || NotificationSettings::sends($recipient, 'any_asks');

        if (! $send) {
            return false;
        }

        Mail::to($recipient->email)->queue(new SomebodyAsked($recipient, $asker));

        return true;
    }

    /**
     * The asks of your own that are still waiting for a reply.
     *
     * Counted the way the Requests screen lists them, which is why the blocks
     * and the departed are dropped: an ask to a family who has gone, or to
     * one you have since blocked, is not shown anywhere and cannot be
     * withdrawn, so it must not hold a place either.
     *
     * @return Collection<int, Connection>
     */
    private function waitingOn(Registration $family): Collection
    {
        $hidden = $family->separatedIds();

        return Connection::query()
            ->where('requester_id', $family->id)
            ->where('status', Connection::PENDING)
            ->with('addressee')
            ->get()
            ->reject(fn (Connection $c) => in_array($c->addressee_id, $hidden, true) || $c->addressee->hasLeft())
            ->values();
    }

    /**
     * What a family is told at the limit.
     *
     * It names them. "You have reached ten" on its own leaves somebody
     * hunting for which ten, and the way out of this is to withdraw one.
     *
     * @param  Collection<int, Connection>  $waiting
     */
    private function atTheLimit(Collection $waiting): string
    {
        $names = $waiting
            ->map(fn (Connection $c) => $c->addressee->displayName())
            ->filter()
            ->join(', ', ' and ');

        return 'You have '.self::PENDING_LIMIT.' asks waiting for a reply, which is as many as you can have at once. '
            .'You are waiting on '.$names.'. '
            .'Withdraw one on your Requests page, or wait for an answer, and you can ask somebody else.';
    }

    /** Only the family who was asked can answer, and only while it is open. */
    private function mineToAnswer(Request $request, Connection $connection): void
    {
        abort_unless($connection->addressee_id === $request->user('founder')->id, 403);
        abort_unless($connection->status === Connection::PENDING, 409);
    }
}
