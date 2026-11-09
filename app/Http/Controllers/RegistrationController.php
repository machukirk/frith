<?php

namespace App\Http\Controllers;

use App\Mail\FounderWelcome;
use App\Models\Registration;
use App\Support\AnswerSheet;
use App\Support\RegistrationFlow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * The Frith Founders registration.
 *
 * Every step is a real POST that saves before it redirects. A visitor who
 * answers the first screen and then has to go and deal with something is
 * registered. That is the entire point, and why this is not a single form
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
     * behaves identically, but nothing is written, so the form can never be
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
        // about them. There is nothing there to ask once nothing is chosen.
        if ($step === 'areas' && $request->input('action') === 'skip') {
            $registration = $this->current($request);

            if ($registration === null) {
                return redirect()->route('register.step', RegistrationFlow::STEPS[0]);
            }

            if (! $this->isShadow($request)) {
                AnswerSheet::clearAreas($registration);
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
     * Sequential, assigned once, never reused: "Founder #47" stays true for
     * that family even if number 12 later deletes their account.
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
     * usually either finished or stopped. So one message reads correctly for
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

        // The questions, and what happens to an answer, are shared with the
        // account screens: a family changes these long after registering,
        // and both paths have to agree about every one of them.
        AnswerSheet::apply($registration, $step, $data);

        $registration->recordProgress(RegistrationFlow::number($step) + 1);
    }

    /** @return array<string, mixed> */
    private function validateStep(Request $request, string $step): array
    {
        return Validator::make($request->all(), ...AnswerSheet::rules($step))->validate();
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
