<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use App\Support\Taxonomy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Section two: the more detailed statements, one screen per area they chose.
 *
 * The source spreadsheet is emphatic about this — "give users the ability to
 * SKIP this section to speed up registration / limit form-filling fatigue" —
 * so every screen can be skipped, the whole section can be skipped, and none
 * of it changes whether they are registered. They already are.
 */
class RegistrationExperienceController extends Controller
{
    private const SESSION_ID = 'registration.id';

    private const SESSION_SHADOW = 'registration.shadow';

    /** Sends them to the first area they picked, or straight to the end. */
    public function start(Request $request): RedirectResponse
    {
        $registration = $this->current($request);

        if ($registration === null) {
            return redirect()->route('register.start');
        }

        $areas = $registration->orderedSupportAreas();

        return $areas === []
            ? redirect()->route('register.done')
            : redirect()->route('register.experiences.show', $areas[0]);
    }

    public function show(Request $request, string $category): View|RedirectResponse
    {
        $registration = $this->current($request);

        if ($registration === null) {
            return redirect()->route('register.start');
        }

        $areas = $registration->orderedSupportAreas();

        // Only the areas they actually chose. Anything else is not their form.
        if (! in_array($category, $areas, true)) {
            return redirect()->route('register.experiences');
        }

        $position = (int) array_search($category, $areas, true);

        return view('register.experiences', [
            'registration' => $registration,
            'category' => $category,
            'meta' => Taxonomy::category($category),
            'selected' => $registration->experiences
                ->where('category', $category)
                ->pluck('item')
                ->all(),
            'position' => $position + 1,
            'total' => count($areas),
            'previous' => $areas[$position - 1] ?? null,
        ]);
    }

    public function store(Request $request, string $category): RedirectResponse
    {
        $registration = $this->current($request);

        if ($registration === null) {
            return redirect()->route('register.start');
        }

        $areas = $registration->orderedSupportAreas();

        if (! in_array($category, $areas, true)) {
            return redirect()->route('register.experiences');
        }

        // "Skip the rest" leaves everything already answered untouched.
        if ($request->input('action') === 'skip-all') {
            return redirect()->route('register.done');
        }

        $data = Validator::make($request->all(), [
            'items' => ['nullable', 'array'],
            'items.*' => [Rule::in(Taxonomy::itemSlugs($category))],
        ])->validate();

        if (! $this->isShadow($request)) {
            $this->saveSelections($registration, $category, $data['items'] ?? []);
        }

        $position = (int) array_search($category, $areas, true);
        $next = $areas[$position + 1] ?? null;

        return $next === null
            ? redirect()->route('register.done')
            : redirect()->route('register.experiences.show', $next);
    }

    /** @param array<int, string> $items */
    private function saveSelections(Registration $registration, string $category, array $items): void
    {
        // Scoped to this category so revisiting one screen cannot wipe another.
        $registration->experiences()->where('category', $category)->delete();

        foreach ($items as $item) {
            $registration->experiences()->create(['category' => $category, 'item' => $item]);
        }

        $registration->load('experiences');
    }

    public function done(Request $request): View|RedirectResponse
    {
        $registration = $this->current($request);

        if ($registration === null) {
            return redirect()->route('register.start');
        }

        if (! $this->isShadow($request)) {
            $registration->forceFill(['completed_at' => $registration->completed_at ?? now()])->save();
        }

        return view('register.done', ['registration' => $registration]);
    }

    private function current(Request $request): ?Registration
    {
        $id = $request->session()->get(self::SESSION_ID);

        return $id ? Registration::query()->with(['children', 'experiences'])->find($id) : null;
    }

    private function isShadow(Request $request): bool
    {
        return (bool) $request->session()->get(self::SESSION_SHADOW, false);
    }
}
