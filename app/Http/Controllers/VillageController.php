<?php

namespace App\Http\Controllers;

use App\Models\Journey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The village: what a family sees once they are in.
 *
 * Registration never asks what other families should call them, because it is
 * no use until there are other families to be called it by. So the village
 * asks once, the first time it is opened, and waits until it has an answer.
 */
class VillageController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $family = $request->user('founder');

        if (! $family->hasProfile()) {
            return redirect()->route('village.setup');
        }

        $joined = $family->journeys()
            ->offered()
            ->withCount('members')
            ->orderBy('position')
            ->get();

        return view('pages.village.show', [
            'family' => $family,
            'joined' => $joined,
            'rest' => Journey::query()
                ->offered()
                ->whereNotIn('id', $joined->pluck('id'))
                ->get(),
        ]);
    }

    public function setup(Request $request): View|RedirectResponse
    {
        $family = $request->user('founder');

        if ($family->hasProfile()) {
            return redirect()->route('village');
        }

        return view('pages.village.setup', ['family' => $family]);
    }

    public function store(Request $request): RedirectResponse
    {
        $family = $request->user('founder');

        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:60'],
        ], [
            'display_name.required' => 'We need something to call you. Whatever your family goes by is fine.',
            'display_name.max' => 'That is a little long. Sixty characters or fewer, please.',
        ]);

        $family->forceFill(['display_name' => trim($data['display_name'])])->save();

        return redirect()->route('village');
    }
}
