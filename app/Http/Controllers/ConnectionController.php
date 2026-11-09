<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Models\Journey;
use App\Models\Registration;
use App\Support\FamilyCard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Asking to connect, and answering.
 *
 * Nobody is put in front of anybody without both families agreeing. A request
 * is pending until it is answered; answering no keeps the row, so the same
 * family is not offered again after somebody has said no once.
 */
class ConnectionController extends Controller
{
    public function index(Request $request): View
    {
        $family = $request->user('founder')->loadMissing(['children', 'experiences']);
        $hidden = $family->separatedIds();

        $connections = Connection::query()
            ->for($family)
            ->with(['requester.children', 'requester.experiences', 'addressee.children', 'addressee.experiences', 'journey'])
            ->latest('id')
            ->get()
            ->reject(fn (Connection $c) => in_array($c->otherThan($family)->id, $hidden, true));

        return view('pages.requests.index', [
            'family' => $family,
            'askedYou' => $connections->filter(fn ($c) => $c->addressee_id === $family->id && $c->status === Connection::PENDING),
            'youAsked' => $connections->filter(fn ($c) => $c->requester_id === $family->id && $c->status === Connection::PENDING),
            'closed' => $connections->filter(fn ($c) => $c->status === Connection::DECLINED),
            'cardFor' => fn (Connection $c) => FamilyCard::make($family, $c->otherThan($family)),
        ]);
    }

    public function store(Request $request, string $publicId): RedirectResponse
    {
        $family = $request->user('founder');
        $other = Registration::query()->where('public_id', $publicId)->firstOrFail();

        abort_if($other->is($family) || $family->isSeparatedFrom($other), 404);

        if (Connection::between($family, $other)) {
            return back()->with('status', 'That is already in hand.');
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

        return back()->with('status', 'Asked. '.$other->displayName().' will see it when they next look.');
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

    /** Only the family who was asked can answer, and only while it is open. */
    private function mineToAnswer(Request $request, Connection $connection): void
    {
        abort_unless($connection->addressee_id === $request->user('founder')->id, 403);
        abort_unless($connection->status === Connection::PENDING, 409);
    }
}
