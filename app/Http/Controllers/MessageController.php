<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Models\Message;
use App\Models\Registration;
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
            // A family who has left is still listed here. Their words are in
            // this inbox and the leaving screen says we cannot take them out,
            // so hiding the conversation would do it by the back door.
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
            // Readable, but there is nobody at the other end to read a reply.
            'canReply' => ! $other->hasLeft(),
        ]);
    }

    public function store(Request $request, Connection $connection): RedirectResponse
    {
        $family = $this->mine($request, $connection);

        // Nothing to send it to.
        abort_if($connection->otherThan($family)->hasLeft(), 410);

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
    private function mine(Request $request, Connection $connection): Registration
    {
        $family = $request->user('founder');

        abort_unless($connection->involves($family), 404);
        abort_unless($connection->isAccepted(), 404);
        abort_if($family->isSeparatedFrom($connection->otherThan($family)), 404);

        return $family;
    }
}
