<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\Registration;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Blocking, and reporting.
 *
 * Two different things on purpose. Blocking needs no reason and tells nobody;
 * reporting is read by a person. The report screen does both, because
 * somebody who has been made uncomfortable should not have to do it twice.
 */
class SafetyController extends Controller
{
    public function blockForm(Request $request, string $publicId): View
    {
        return view('pages.safety.block', ['other' => $this->other($request, $publicId)]);
    }

    public function block(Request $request, string $publicId): RedirectResponse
    {
        $family = $request->user('founder');
        $other = $this->other($request, $publicId);

        $this->separate($family, $other);

        return redirect()
            ->route('village')
            ->with('status', $other->displayName().' can no longer see you, and you will not see them.');
    }

    public function reportForm(Request $request, string $publicId): View
    {
        return view('pages.safety.report', [
            'other' => $this->other($request, $publicId),
            'reasons' => config('frith-safety.reasons'),
        ]);
    }

    public function report(Request $request, string $publicId): RedirectResponse
    {
        $family = $request->user('founder');
        $other = $this->other($request, $publicId);

        $data = $request->validate([
            'reason' => ['required', 'string', \Illuminate\Validation\Rule::in(array_keys(config('frith-safety.reasons')))],
            'note' => ['nullable', 'string', 'max:2000'],
        ], [
            'reason.required' => 'Please choose the closest one. It helps us know where to start.',
        ]);

        Report::query()->create([
            'reporter_id' => $family->id,
            'reported_id' => $other->id,
            // Kept as text too, so the record still reads if that account goes.
            'reported_name' => $other->displayName(),
            'reason' => $data['reason'],
            'note' => $data['note'] ?? null,
        ]);

        // Reporting blocks as well. Nobody should have to do it twice.
        $this->separate($family, $other);

        return redirect()
            ->route('village')
            ->with('status', 'Thank you. A person reads every report, usually within 24 hours. '.$other->displayName().' can no longer see you.');
    }

    /** Idempotent, so a second press is not an error. */
    private function separate(Registration $family, Registration $other): void
    {
        Block::query()->firstOrCreate([
            'blocker_id' => $family->id,
            'blocked_id' => $other->id,
        ]);
    }

    private function other(Request $request, string $publicId): Registration
    {
        $family = $request->user('founder');
        $other = Registration::query()->where('public_id', $publicId)->firstOrFail();

        abort_if($other->is($family), 404);

        return $other;
    }
}
