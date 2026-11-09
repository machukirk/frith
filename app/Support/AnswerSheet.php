<?php

namespace App\Support;

use App\Models\Registration;
use Illuminate\Validation\Rule;

/**
 * The six questions, what counts as a valid answer, and how an answer is saved.
 *
 * Pulled out of the registration controller because a family does not answer
 * these once. They answer them while registering and then change them from
 * their account, and the two have to agree about everything: what is allowed,
 * what an empty answer means, and what happens to the answers underneath when
 * somebody removes an area of family life.
 *
 * Two copies of that would drift, and the half that drifted would be the
 * account screens — the ones nobody sees until months after launch.
 *
 * What is NOT here: creating the row, consent, the Founder number and the
 * welcome email. Those happen once, at registration, and belong to it.
 */
class AnswerSheet
{
    /**
     * Validation for one step: rules, then messages.
     *
     * @return array{0: array<string, mixed>, 1: array<string, string>}
     */
    public static function rules(string $step): array
    {
        $emailFormat = config('frith.registration.validate_email_dns') ? 'email:rfc,dns' : 'email:rfc';

        return match ($step) {
            // The account's version of the first screen. A display name
            // rather than a first name, because by the time a family edits
            // this they have met the question "what should other families
            // call you?" and that is the answer being changed. No email:
            // the address is the account, and changing it needs a
            // confirmation at the new address, which is a different job.
            'name' => [[
                'display_name' => ['required', 'string', 'max:60'],
                'postcode_outcode' => ['required', 'string', 'regex:/^[A-Za-z]{1,2}\d[A-Za-z\d]?$/'],
            ], [
                'display_name.required' => 'We need something to call you. Whatever your family goes by is fine.',
                'display_name.max' => 'That is a little long. Sixty characters or fewer, please.',
                'postcode_outcode.required' => 'We need the first part of your postcode to find families near you.',
                'postcode_outcode.regex' => 'That does not look like the first part of a postcode. It should look like SS9.',
            ]],

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
     * Writes one step's answers.
     *
     * The 'you' step here only changes a name and an outcode. The email
     * address is the account, so changing it is a different job with a
     * confirmation behind it, and this is not that job.
     *
     * @param  array<string, mixed>  $data
     */
    public static function apply(Registration $registration, string $step, array $data): void
    {
        match ($step) {
            'name' => $registration->forceFill([
                'display_name' => trim($data['display_name']),
                'postcode_outcode' => Registration::normaliseOutcode($data['postcode_outcode']),
            ])->save(),

            'you' => $registration->forceFill([
                'first_name' => $data['first_name'],
                'postcode_outcode' => Registration::normaliseOutcode($data['postcode_outcode']),
            ])->save(),

            'family' => self::saveFamily($registration, $data),

            'hopes' => $registration->forceFill([
                'hopes' => $data['hopes'] ?? [],
            ])->save(),

            'areas' => self::saveAreas($registration, $data['support_areas'] ?? []),

            'experiences' => self::saveExperiences($registration, $data['items'] ?? []),

            'interests' => $registration->forceFill([
                'interests' => self::interestSelections($data),
                'interests_other' => $data['interests_other'] ?? null,
                'activity_supports' => $data['activity_supports'] ?? [],
                'connection_styles' => $data['connection_styles'] ?? [],
            ])->save(),

            default => null,
        };
    }

    /**
     * Clears the areas of family life and everything answered underneath them.
     *
     * The skip on the areas screen, and also what happens when somebody
     * removes every area from their account. Either way the statements under
     * an area nobody has chosen are not theirs to hold.
     */
    public static function clearAreas(Registration $registration): void
    {
        $registration->forceFill(['support_areas' => []])->save();
        $registration->experiences()->delete();
        $registration->load('experiences');
    }

    /**
     * The areas they chose, and the detail underneath any they dropped.
     *
     * Dropping an area from the account screen has to take its statements
     * with it, or a family would keep matching on something they have just
     * said is no longer part of their life.
     *
     * @param  array<int, string>  $areas
     */
    private static function saveAreas(Registration $registration, array $areas): void
    {
        $registration->forceFill(['support_areas' => $areas])->save();

        $registration->experiences()
            ->when($areas !== [], fn ($query) => $query->whereNotIn('category', $areas))
            ->delete();

        $registration->load('experiences');
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
    private static function interestSelections(array $data): array
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
    private static function saveFamily(Registration $registration, array $data): void
    {
        $registration->forceFill([
            'family_structures' => $data['family_structures'] ?? [],
        ])->save();

        self::saveChildren($registration, $data['children'] ?? []);
    }

    /** @param array<int, array{birth_month: string, birth_year: string}> $children */
    private static function saveChildren(Registration $registration, array $children): void
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
    private static function saveExperiences(Registration $registration, array $items): void
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
}
