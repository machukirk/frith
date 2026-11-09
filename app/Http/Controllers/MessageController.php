<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Conversations between two families who have both said yes.
 *
 * A message cannot exist without an accepted connection behind it, which is
 * checked on the way in rather than assumed from the URL.
 */
class MessageController extends Controller
{
    public function index(Request $request): View
    {
        $family = $request->user('founder');
        $hidden = $family->separatedIds();

        $conversations = Connection::query()
            ->for($family)
            ->where('status', Connection::ACCEPTED)
            ->with(['requester', 'addressee', 'messages'])
            ->get()
            ->reject(fn (Connection $c) => in_array($c->otherThan($family)->id, $hidden, true))
            ->sortByDesc(fn (Connection $c) => $c->messages->last()?->created_at ?? $c->responded_at)
            ->values();

        return view('pages.messages.index', [
            'family' => $family,
            'conversations' => $conversations,
        ]);
    }

    public function show(Request $request, Connection $connection): View
    {
        $family = $this->mine($request, $connection);
        $other = $connection->otherThan($family);

        // Reading it is what marks it read, and only their lines.
        $connection->messages()
            ->where('sender_id', '!=', $family->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('pages.messages.show', [
            'family' => $family,
            'other' => $other,
            'connection' => $connection,
            'messages' => $connection->messages()->with('sender')->get(),
        ]);
    }

    public function store(Request $request, Connection $connection): RedirectResponse
    {
        $family = $this->mine($request, $connection);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ], [
            'body.required' => 'Nothing to send yet.',
            'body.max' => 'That is longer than this box takes. Two thousand characters or fewer.',
        ]);

        Message::query()->create([
            'connection_id' => $connection->id,
            'sender_id' => $family->id,
            'body' => trim($data['body']),
        ]);

        return redirect()->route('messages.show', $connection);
    }

    /** Theirs to read, and still open. */
    private function mine(Request $request, Connection $connection): \App\Models\Registration
    {
        $family = $request->user('founder');

        abort_unless($connection->involves($family), 404);
        abort_unless($connection->isAccepted(), 404);
        abort_if($family->isSeparatedFrom($connection->otherThan($family)), 404);

        return $family;
    }
}
