<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\Connection;
use App\Models\Registration;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Mail\Message as MailMessage;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * Your data: taking a copy, leaving, and who you have blocked.
 *
 * The blocked list lives here rather than on its own screen because the
 * design puts it here, and because it reads better next to leaving than next
 * to notifications: this is the page about getting out from under things.
 */
class AccountDataController extends Controller
{
    public function show(Request $request): View
    {
        $family = $request->user('founder');

        return view('pages.account.data', [
            'family' => $family,
            'blocks' => Block::query()
                ->where('blocker_id', $family->id)
                ->with('blocked')
                ->latest('id')
                ->get(),
            'graceDays' => (int) config('frith.deletion.grace_days'),
        ]);
    }

    /**
     * Asking for a copy.
     *
     * A timestamp rather than a built file: a family's data includes messages
     * with other families in them, and deciding what of a conversation is
     * theirs to take is a judgement a person makes, not a query. So this is a
     * request on a queue that a person works through, and the screen says
     * "usually ready within an hour" rather than handing over a download.
     */
    public function request(Request $request): RedirectResponse
    {
        $family = $request->user('founder');

        // Asked twice is asked once. Pressing it again should not reset the
        // clock on a request somebody is already working on.
        if ($family->data_requested_at === null) {
            $this->tellTheTeam($family);

            $family->forceFill(['data_requested_at' => now()])->save();
        }

        return redirect()
            ->route('account.data')
            ->with('status', 'Asked for. We will email it to '.$family->email.', usually within the hour.');
    }

    /**
     * The part that makes the promise true.
     *
     * The screen says the copy is usually ready within the hour. The
     * timestamp on its own told nobody: there is no admin screen for data
     * requests and nothing reads the column, so the request sat in a table
     * while the hour went by. A person is emailed instead, the way the help
     * page's form emails one.
     *
     * Sent before the timestamp is written, and sent rather than queued. If
     * the mail fails, nothing is recorded, so pressing the button again is a
     * real second try instead of a no-op on a request nobody received.
     *
     * Plain text rather than a Mailable: it lands in our own inbox, there is
     * nothing to brand and nothing to unsubscribe from. If it ever needs a
     * template, copy App\Mail\ContactMessage.
     */
    private function tellTheTeam(Registration $family): void
    {
        $name = $family->displayName() ?: $family->email;

        Mail::raw(implode("\n", [
            $name.' has asked for a copy of their data.',
            '',
            'Email: '.$family->email,
            'Account: '.$family->public_id,
            'Asked: '.now()->format('j F Y, H:i'),
            '',
            'The screen tells them it usually arrives within the hour.',
            'Their messages have another family in them, so what goes in the file is a judgement somebody makes, not a query.',
        ]), fn (MailMessage $message) => $message
            ->to(config('frith.company.contact_email'))
            ->replyTo($family->email)
            ->subject('Frith: data request from '.$name));
    }

    /** The screen that says what leaving actually does, before it happens. */
    public function confirmDelete(Request $request): View
    {
        return view('pages.account.delete', [
            'family' => $request->user('founder'),
            'graceDays' => (int) config('frith.deletion.grace_days'),
        ]);
    }

    /**
     * Leaving.
     *
     * Two halves, and the split is the promise the screen makes. Straight
     * away: the profile stops being visible, the journeys are left, and
     * pending requests are closed, so nobody is left waiting on a family who
     * has gone. On a timer: the row itself.
     *
     * Messages already sent are not touched. We cannot remove somebody's
     * words from another family's conversation, and the screen says so rather
     * than implying a clean exit that is not on offer.
     */
    public function delete(Request $request): RedirectResponse
    {
        $family = $request->user('founder');

        $request->validate([
            'understood' => ['accepted'],
        ], [
            'understood.accepted' => 'Please tick the box to confirm you have read what happens.',
        ]);

        $family->forceFill(['deletion_requested_at' => now()])->save();
        $family->journeys()->detach();

        Connection::query()
            ->for($family)
            ->where('status', Connection::PENDING)
            ->update(['status' => Connection::DECLINED, 'responded_at' => now()]);

        auth('founder')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'Your profile is gone. The rest is deleted within '.config('frith.deletion.grace_days').' days.');
    }

    /** What they kept, so they can remember why. Only ever their own blocks. */
    public function blocked(Request $request, string $publicId): View
    {
        $family = $request->user('founder');

        $block = Block::query()
            ->where('blocker_id', $family->id)
            ->whereHas('blocked', fn ($query) => $query->where('public_id', $publicId))
            ->with('blocked')
            ->firstOrFail();

        return view('pages.account.blocked', [
            'block' => $block,
            'report' => Report::query()
                ->where('reporter_id', $family->id)
                ->where('reported_id', $block->blocked_id)
                ->latest('id')
                ->first(),
            'reasons' => config('frith-safety.reasons'),
        ]);
    }

    /**
     * Unblocking. Silent in both directions, like the block itself: the other
     * family was never told it happened and is not told it has stopped.
     */
    public function unblock(Request $request, string $publicId): RedirectResponse
    {
        $family = $request->user('founder');
        $other = Registration::query()->where('public_id', $publicId)->firstOrFail();

        Block::query()
            ->where('blocker_id', $family->id)
            ->where('blocked_id', $other->id)
            ->delete();

        return redirect()
            ->route('account.data')
            ->with('status', $other->displayName().' can see you again. They were not told either way.');
    }
}
