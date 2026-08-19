<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use App\Support\RegistrationFlow;
use App\Support\Taxonomy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The Frith Founders registration form.
 *
 * Every step is a real POST that saves before it redirects. A visitor who
 * answers the first screen and then has to go and deal with something is
 * registered — which is the entire point, and why this is not a single form
 * with a submit button at the bottom.
 *
 * It works with no JavaScript at all: each step is its own URL, "add another
 * child" is a submit, and the sliding between steps is a CSS animation on
 * arrival. That matters for an audience the brand guidelines describe as
 * reading on a cracked phone at 11pm.
 */
class RegistrationController extends Controller
{
    private const SESSION_ID = 'registration.id';

    /**
     * Set when the email already belongs to a verified Founder. Every screen
     * behaves identically, but nothing is written — so the form can never be
     * used to overwrite somebody else's answers, and never reveals that the
     * address is already registered.
     */
    private const SESSION_SHADOW = 'registration.shadow';

    public function start(): RedirectResponse
    {
        return redirect()->route('register.step', RegistrationFlow::STEPS[0]);
    }

    public function show(Request $request, string $step): View|RedirectResponse
    {
        abort_unless(RegistrationFlow::exists($step), 404);

        $registration = $this->current($request);

        // You cannot skip ahead to a screen that depends on answers not given.
        if ($registration === null && $step !== RegistrationFlow::STEPS[0]) {
            return redirect()->route('register.step', RegistrationFlow::STEPS[0]);
        }

        return view("register.{$step}", [
            'registration' => $registration,
            'step' => $step,
            'stepNumber' => RegistrationFlow::number($step),
            'totalSteps' => RegistrationFlow::total(),
            'previous' => RegistrationFlow::previous($step),
            'children' => $this->childRows($request, $registration),
        ]);
    }

    public function store(Request $request, string $step): RedirectResponse
    {
        abort_unless(RegistrationFlow::exists($step), 404);

        // "Add another child" and "Remove" are submits, so the screen works
        // without JavaScript. They re-render rather than moving on.
        if ($step === 'children' && $request->input('action') !== 'continue') {
            return $this->adjustChildRows($request);
        }

        $data = $this->validateStep($request, $step);
        $registration = $this->current($request);

        if ($step === RegistrationFlow::STEPS[0]) {
            $registration = $this->openRegistration($request, $data);
        } elseif ($registration === null) {
            return redirect()->route('register.step', RegistrationFlow::STEPS[0]);
        } else {
            $this->applyStep($request, $registration, $step, $data);
        }

        $next = RegistrationFlow::next($step);

        if ($next !== null) {
            return redirect()->route('register.step', $next);
        }

        // End of section one. They are a Founder from here whatever they do next.
        if (! $this->isShadow($request)) {
            $registration->forceFill(['completed_at' => $registration->completed_at ?? now()])->save();
        }

        return redirect()->route('register.experiences');
    }

    /**
     * Creates the row, or picks up where an unfinished one left off.
     *
     * @param  array<string, mixed>  $data
     */
    private function openRegistration(Request $request, array $data): Registration
    {
        $email = Registration::normaliseEmail($data['email']);
        $existing = Registration::query()->where('email', $email)->first();

        // Somebody else's verified account. Walk them through the form
        // normally, write nothing, and tell the real owner by email.
        if ($existing && $existing->email_verified_at !== null) {
            $request->session()->put(self::SESSION_SHADOW, true);
            $request->session()->put(self::SESSION_ID, $existing->id);

            return $existing;
        }

        $request->session()->forget(self::SESSION_SHADOW);

        $registration = $existing ?? new Registration([
            'email' => $email,
            'source' => 'coming-soon',
            'consent_version' => config('frith.consent.version'),
            'consent_text' => config('frith.consent.text'),
            'consented_at' => now(),
            'consent_ip' => $request->ip(),
            'consent_user_agent' => substr((string) $request->userAgent(), 0, 1000),
        ]);

        $registration->fill(['first_name' => $data['first_name']]);

        $registration->save();

        $this->assignFounderNumber($registration);
        $registration->recordProgress(2);

        $request->session()->put(self::SESSION_ID, $registration->id);

        return $registration;
    }

    /**
     * Sequential, assigned once, never reused — so "Founder #47" stays true
     * for that family even if number 12 later deletes their account.
     */
    private function assignFounderNumber(Registration $registration): void
    {
        if ($registration->founder_number !== null) {
            return;
        }

        $next = (int) Registration::query()->max('founder_number') + 1;

        $registration->forceFill(['founder_number' => $next])->save();
    }

    /** @param array<string, mixed> $data */
    private function applyStep(Request $request, Registration $registration, string $step, array $data): void
    {
        if ($this->isShadow($request)) {
            return;
        }

        match ($step) {
            'location' => $registration->forceFill([
                'postcode_outcode' => Registration::normaliseOutcode($data['postcode_outcode']),
            ])->save(),

            'family' => $registration->forceFill([
                'family_structures' => $data['family_structures'] ?? [],
            ])->save(),

            'children' => $this->saveChildren($registration, $data['children'] ?? []),

            'support' => $registration->forceFill([
                'support_areas' => $data['support_areas'] ?? [],
            ])->save(),

            default => null,
        };

        $registration->recordProgress(RegistrationFlow::number($step) + 1);
    }

    /** @param array<int, array{birth_month: string, birth_year: string}> $children */
    private function saveChildren(Registration $registration, array $children): void
    {
        // Replaced wholesale rather than diffed: the form is the full picture
        // of the household each time it is submitted, and a partial update
        // would leave a child behind after somebody removed a row.
        $registration->children()->delete();

        foreach (array_values($children) as $position => $child) {
            $registration->children()->create([
                'birth_month' => (int) $child['birth_month'],
                'birth_year' => (int) $child['birth_year'],
                'position' => $position,
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function validateStep(Request $request, string $step): array
    {
        return Validator::make($request->all(), ...$this->rulesFor($step))->validate();
    }

    /** @return array{0: array<string, mixed>, 1: array<string, string>} */
    private function rulesFor(string $step): array
    {
        $emailFormat = config('frith.registration.validate_email_dns') ? 'email:rfc,dns' : 'email:rfc';

        return match ($step) {
            'you' => [[
                'first_name' => ['required', 'string', 'max:80'],
                'email' => ['required', 'string', $emailFormat, 'max:254'],
            ], [
                'first_name.required' => 'We need something to call you. A first name or a nickname is fine.',
                'email.required' => 'We need an email address so we can tell you when Frith opens.',
                'email.email' => 'That email does not look right. It should look like name@example.com.',
            ]],

            'location' => [[
                // Outcode only: the first half of a UK postcode. Between one
                // and two letters, a digit, then optionally another digit or
                // letter — SS9, M1, EC1A, W1A.
                'postcode_outcode' => ['required', 'string', 'regex:/^[A-Za-z]{1,2}\d[A-Za-z\d]?$/'],
            ], [
                'postcode_outcode.required' => 'We need the first part of your postcode to find families near you.',
                'postcode_outcode.regex' => 'That does not look like the first part of a postcode. It should look like SS9.',
            ]],

            'family' => [[
                'family_structures' => ['nullable', 'array'],
                'family_structures.*' => [Rule::in(Taxonomy::familyStructureSlugs())],
            ], []],

            'children' => [[
                'children' => ['nullable', 'array', 'max:12'],
                'children.*.birth_month' => ['required', 'integer', 'between:1,12'],
                'children.*.birth_year' => ['required', 'integer', 'between:'.(now()->year - 25).','.now()->year],
            ], [
                'children.*.birth_month.required' => 'Please choose a month for each child, or remove the row.',
                'children.*.birth_year.required' => 'Please choose a year for each child, or remove the row.',
                'children.*.birth_year.between' => 'Please check the year of birth.',
            ]],

            'support' => [[
                'support_areas' => ['nullable', 'array'],
                'support_areas.*' => [Rule::in(Taxonomy::categorySlugs())],
            ], []],

            default => [[], []],
        };
    }

    /**
     * Adds or removes a child row without leaving the page, for people with no
     * JavaScript. The rows live in the session until the step is submitted.
     */
    private function adjustChildRows(Request $request): RedirectResponse
    {
        $rows = array_values($request->input('children', []));

        if ($request->input('action') === 'add') {
            $rows[] = ['birth_month' => '', 'birth_year' => ''];
        }

        if (str_starts_with((string) $request->input('action'), 'remove:')) {
            unset($rows[(int) explode(':', $request->input('action'))[1]]);
            $rows = array_values($rows);
        }

        $request->session()->put('registration.child_rows', $rows);

        return redirect()->route('register.step', 'children');
    }

    /**
     * The rows to render: whatever is mid-edit in the session, else what is
     * saved, else one empty row to start from.
     *
     * @return array<int, array{birth_month: string|int, birth_year: string|int}>
     */
    private function childRows(Request $request, ?Registration $registration): array
    {
        if ($request->session()->has('registration.child_rows')) {
            return $request->session()->pull('registration.child_rows');
        }

        if ($registration && $registration->children->isNotEmpty()) {
            return $registration->children
                ->map(fn ($c) => ['birth_month' => $c->birth_month, 'birth_year' => $c->birth_year])
                ->all();
        }

        return [['birth_month' => '', 'birth_year' => '']];
    }

    private function current(Request $request): ?Registration
    {
        $id = $request->session()->get(self::SESSION_ID);

        return $id ? Registration::query()->with('children')->find($id) : null;
    }

    private function isShadow(Request $request): bool
    {
        return (bool) $request->session()->get(self::SESSION_SHADOW, false);
    }
}
