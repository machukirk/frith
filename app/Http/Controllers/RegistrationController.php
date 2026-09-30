<?php

namespace App\Http\Controllers;

use App\Mail\FounderWelcome;
use App\Models\Registration;
use App\Support\RegistrationFlow;
use App\Support\Taxonomy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The Frith Founders registration.
 *
 * Every step is a real POST that saves before it redirects. A visitor who
 * answers the first screen and then has to go and deal with something is
 * registered — which is the entire point, and why this is not a single form
 * with a submit button at the bottom.
 *
 * It works with no JavaScript at all: each step is its own URL, "add another
 * child" is a submit, and the accordions on step five are <details>. That
 * matters for an audience the guidelines describe as reading on a cracked
 * phone at 11pm.
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

    /**
     * The end of the form. Guarded, because "You are now a First Frith
     * Family" is not something to tell somebody who is not one.
     */
    public function welcome(Request $request): View|RedirectResponse
    {
        if ($this->current($request) === null) {
            return redirect()->route('register.start');
        }

        return view('register.welcome');
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
            'previous' => RegistrationFlow::backFrom($step),
            'children' => $this->childRows($request, $registration),
        ]);
    }

    public function store(Request $request, string $step): RedirectResponse
    {
        abort_unless(RegistrationFlow::exists($step), 404);

        // "Add another child" and "Remove" are submits, so the screen works
        // without JavaScript. They re-render rather than moving on.
        if ($step === 'family' && $request->input('action') !== 'continue') {
            return $this->adjustChildRows($request);
        }

        // "Skip this section" on the areas screen. It clears the areas and
        // their detail statements and goes straight past the screen that asks
        // about them — there is nothing there to ask once nothing is chosen.
        if ($step === 'areas' && $request->input('action') === 'skip') {
            $registration = $this->current($request);

            if ($registration === null) {
                return redirect()->route('register.step', RegistrationFlow::STEPS[0]);
            }

            if (! $this->isShadow($request)) {
                $registration->forceFill(['support_areas' => []])->save();
                $registration->experiences()->delete();
                $registration->recordProgress(RegistrationFlow::number('experiences') + 1);
            }

            return redirect()->route('register.step', 'interests');
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

        // The last screen. They have been a Founder since the first one.
        if (! $this->isShadow($request)) {
            $registration->forceFill(['completed_at' => $registration->completed_at ?? now()])->save();
        }

        return redirect()->route('register.welcome');
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
            'source' => 'website',
            'consent_version' => config('frith.consent.version'),
            'consent_text' => config('frith.consent.text'),
            'consented_at' => now(),
            'consent_ip' => $request->ip(),
            'consent_user_agent' => substr((string) $request->userAgent(), 0, 1000),
        ]);

        $registration->fill([
            'first_name' => $data['first_name'],
            'postcode_outcode' => Registration::normaliseOutcode($data['postcode_outcode']),
        ]);

        $registration->save();

        $this->assignFounderNumber($registration);
        $this->sendWelcome($registration);
        $registration->recordProgress(2);

        $request->session()->put(self::SESSION_ID, $registration->id);

        return $registration;
    }

    /**
     * Sequential, assigned once, never reused — so "Founder #47" stays true
     * for that family even if number 12 later deletes their account.
     *
     * Only the first hundred get one. After that a registration is complete
     * and perfectly normal; it simply has no number, and everything that shows
     * one already checks for it.
     *
     * Counted and written inside one locked transaction, because two families
     * registering in the same second must not both be told they are number
     * one hundred.
     */
    private function assignFounderNumber(Registration $registration): void
    {
        if ($registration->founder_number !== null) {
            return;
        }

        DB::transaction(function () use ($registration) {
            // The high-water mark, not a count of rows: a family who leaves
            // takes their number with them rather than freeing a place, so the
            // hundredth Founder is the hundredth number issued.
            $highest = (int) Registration::query()
                ->whereNotNull('founder_number')
                ->lockForUpdate()
                ->max('founder_number');

            if ($highest >= (int) config('frith.founders.limit')) {
                return;
            }

            $registration->forceFill(['founder_number' => $highest + 1])->save();
        });
    }

    /**
     * The Founder email, sent once.
     *
     * Delayed rather than immediate: somebody still filling in the form does
     * not need their phone buzzing at them, and by the time it lands they have
     * usually either finished or stopped — so one message reads correctly for
     * both. The timestamp is written first, so a retry cannot send it twice.
     */
    private function sendWelcome(Registration $registration): void
    {
        if ($registration->verification_sent_at !== null) {
            return;
        }

        $registration->forceFill(['verification_sent_at' => now()])->save();

        Mail::to($registration->email)->later(
            now()->addMinutes((int) config('frith.registration.welcome_delay_minutes')),
            new FounderWelcome($registration),
        );
    }

    /** @param array<string, mixed> $data */
    private function applyStep(Request $request, Registration $registration, string $step, array $data): void
    {
        if ($this->isShadow($request)) {
            return;
        }

        match ($step) {
            'family' => $this->saveFamily($registration, $data),

            'hopes' => $registration->forceFill([
                'hopes' => $data['hopes'] ?? [],
            ])->save(),

            'areas' => $registration->forceFill([
                'support_areas' => $data['support_areas'] ?? [],
            ])->save(),

            'experiences' => $this->saveExperiences($registration, $data['items'] ?? []),

            'interests' => $registration->forceFill([
                'interests' => $this->interestSelections($data),
                'interests_other' => $data['interests_other'] ?? null,
                'activity_supports' => $data['activity_supports'] ?? [],
                'connection_styles' => $data['connection_styles'] ?? [],
            ])->save(),

            default => null,
        };

        $registration->recordProgress(RegistrationFlow::number($step) + 1);
    }

    /**
     * The interests they ticked, plus "Other" if they wrote in the box.
     *
     * The box is always on screen — the form works with no JavaScript, so it
     * cannot be revealed by a checkbox — and somebody who fills it in has
     * plainly told us something. Their answer should not turn on whether they
     * also spotted the pill above it.
     *
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    private function interestSelections(array $data): array
    {
        $chosen = array_values($data['interests'] ?? []);

        $wroteSomething = filled($data['interests_other'] ?? null);
        $otherIsOffered = in_array('other', Taxonomy::interestSlugs(), true);

        if ($wroteSomething && $otherIsOffered && ! in_array('other', $chosen, true)) {
            $chosen[] = 'other';
        }

        return $chosen;
    }

    /** @param array<string, mixed> $data */
    private function saveFamily(Registration $registration, array $data): void
    {
        $registration->forceFill([
            'family_structures' => $data['family_structures'] ?? [],
        ])->save();

        $this->saveChildren($registration, $data['children'] ?? []);
    }

    /** @param array<int, array{birth_month: string, birth_year: string}> $children */
    private function saveChildren(Registration $registration, array $children): void
    {
        // Replaced wholesale rather than diffed: the form is the full picture
        // of the household each time it is submitted, and a partial update
        // would leave a child behind after somebody removed a row.
        $registration->children()->delete();

        foreach (array_values($children) as $position => $child) {
            if (blank($child['birth_month'] ?? null) || blank($child['birth_year'] ?? null)) {
                continue;
            }

            $registration->children()->create([
                'birth_month' => (int) $child['birth_month'],
                'birth_year' => (int) $child['birth_year'],
                'position' => $position,
            ]);
        }

        $registration->load('children');
    }

    /**
     * The detail statements, all of them, from one screen.
     *
     * Replaced wholesale, and filtered rather than validated: the statements
     * arrive keyed by area, which is not a shape a validation rule expresses
     * well, and an unknown pair should be dropped rather than throw the whole
     * screen back at somebody. Anything not in the taxonomy, and anything
     * under an area they did not choose, is not theirs to answer.
     *
     * @param  array<string, array<int, string>>  $items
     */
    private function saveExperiences(Registration $registration, array $items): void
    {
        $chosenAreas = $registration->orderedSupportAreas();

        $registration->experiences()->delete();

        foreach ($items as $category => $statements) {
            if (! in_array($category, $chosenAreas, true)) {
                continue;
            }

            $allowed = Taxonomy::itemSlugs($category);

            foreach (array_unique((array) $statements) as $item) {
                if (! is_string($item) || ! in_array($item, $allowed, true)) {
                    continue;
                }

                $registration->experiences()->create(['category' => $category, 'item' => $item]);
            }
        }

        $registration->load('experiences');
    }

    /** @return array<string, mixed> */
    private function validateStep(Request $request, string $step): array
    {
        return Validator::make($request->all(), ...$this->rulesFor($request, $step))->validate();
    }

    /** @return array{0: array<string, mixed>, 1: array<string, string>} */
    private function rulesFor(Request $request, string $step): array
    {
        $emailFormat = config('frith.registration.validate_email_dns') ? 'email:rfc,dns' : 'email:rfc';

        return match ($step) {
            'you' => [[
                'first_name' => ['required', 'string', 'max:80'],
                'email' => ['required', 'string', $emailFormat, 'max:254'],
                // Outcode only: the first half of a UK postcode. Between one
                // and two letters, a digit, then optionally another digit or
                // letter — SS9, M1, EC1A, W1A.
                'postcode_outcode' => ['required', 'string', 'regex:/^[A-Za-z]{1,2}\d[A-Za-z\d]?$/'],
            ], [
                'first_name.required' => 'We need something to call you. A first name or a nickname is fine.',
                'email.required' => 'We need an email address so we can tell you when Frith opens.',
                'email.email' => 'That email does not look right. It should look like name@example.com.',
                'postcode_outcode.required' => 'We need the first part of your postcode to find families near you.',
                'postcode_outcode.regex' => 'That does not look like the first part of a postcode. It should look like SS9.',
            ]],

            'family' => [[
                'family_structures' => ['nullable', 'array'],
                'family_structures.*' => [Rule::in(Taxonomy::familyStructureSlugs())],
                'children' => ['nullable', 'array', 'max:12'],
                'children.*.birth_month' => ['nullable', 'integer', 'between:1,12'],
                'children.*.birth_year' => ['nullable', 'integer', 'between:'.(now()->year - 25).','.now()->year],
            ], [
                'children.*.birth_year.between' => 'Please check the year of birth.',
            ]],

            'hopes' => [[
                'hopes' => ['nullable', 'array'],
                'hopes.*' => [Rule::in(Taxonomy::hopeSlugs())],
            ], []],

            'areas' => [[
                'support_areas' => ['nullable', 'array'],
                'support_areas.*' => [Rule::in(Taxonomy::categorySlugs())],
            ], []],

            // One accordion per area they chose, so the statements arrive
            // keyed by area. Only areas they actually chose are accepted.
            'experiences' => [[
                'items' => ['nullable', 'array'],
                'items.*' => ['array'],
            ], []],

            'interests' => [[
                'interests' => ['nullable', 'array'],
                'interests.*' => [Rule::in(Taxonomy::interestSlugs())],
                'interests_other' => ['nullable', 'string', 'max:1000'],
                'activity_supports' => ['nullable', 'array'],
                'activity_supports.*' => [Rule::in(Taxonomy::activitySupportSlugs())],
                'connection_styles' => ['nullable', 'array'],
                'connection_styles.*' => [Rule::in(Taxonomy::connectionStyleSlugs())],
            ], [
                'interests_other.max' => 'That is a little long for this box — could you shorten it a bit?',
            ]],

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
        $request->session()->flash('registration.family_structures', $request->input('family_structures', []));

        return redirect()->route('register.step', 'family');
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

        return $id ? Registration::query()->with(['children', 'experiences'])->find($id) : null;
    }

    private function isShadow(Request $request): bool
    {
        return (bool) $request->session()->get(self::SESSION_SHADOW, false);
    }
}
