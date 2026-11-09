<?php

namespace App\Support;

use App\Models\Registration;
use Illuminate\Support\Collection;

/**
 * A family's own profile, section by section, as the account screen lists it.
 *
 * One section per registration step, which is not a coincidence: the form a
 * family filled in is the profile they later edit, so the account screen's
 * Edit links lead back to the same six screens rather than to a second set
 * that could drift out of step with them.
 *
 * "Complete" never means required. The screen says so in as many words:
 * "The more you share, the more meaningful your connections can be. Nothing
 * here is required." It is there so somebody who wants to finish can see
 * what is left, not to make an unfinished profile feel like a failure.
 */
class ProfileSections
{
    /**
     * @return Collection<int, array{
     *     step: string, title: string, summary: string,
     *     complete: bool, private: bool
     * }>
     */
    public static function for(Registration $family): Collection
    {
        $family->loadMissing(['children', 'experiences']);

        return collect([
            self::nameAndArea($family),
            self::children($family),
            self::list('hopes', 'What you are hoping to find', $family->hopes, fn ($s) => Taxonomy::hopeLabel($s)),
            self::list('areas', 'Areas of family life', $family->support_areas, fn ($s) => Taxonomy::categoryLabel($s)),
            self::experiences($family),
            self::interests($family),
        ]);
    }

    /** How far through they are: "5 of 6 sections complete". */
    public static function tally(Registration $family): string
    {
        $sections = self::for($family);

        return $sections->where('complete')->count().' of '.$sections->count().' sections complete';
    }

    /** @return array<string, mixed> */
    private static function nameAndArea(Registration $family): array
    {
        $town = Postcode::town($family->postcode_outcode);
        $outcode = mb_strtoupper((string) $family->postcode_outcode);

        return [
            'step' => 'name',
            'title' => 'Name and area',
            // The outcode and what it is shown as, both, so a family can see
            // exactly how much of their location another family gets.
            'summary' => collect([
                $family->displayName(),
                $outcode ?: null,
                $town ? 'shown as '.$town.' area' : ($outcode ? 'shown as '.$outcode.' area' : null),
            ])->filter()->implode(' · '),
            'complete' => filled($family->displayName()) && filled($family->postcode_outcode),
            'private' => false,
        ];
    }

    /** @return array<string, mixed> */
    private static function children(Registration $family): array
    {
        $children = $family->children;

        return [
            'step' => 'family',
            'title' => 'Your children',
            'summary' => $children->isEmpty()
                ? 'Nothing added'
                : ucfirst(Words::count($children->count()).' '.str('child')->plural($children->count()))
                    .', born '.Words::list($children->map->bornLabel()->all()),
            'complete' => $children->isNotEmpty(),
            'private' => false,
        ];
    }

    /** @return array<string, mixed> */
    private static function experiences(Registration $family): array
    {
        $items = $family->experiences
            ->map(fn ($e) => Taxonomy::itemLabel($e->category, $e->item))
            ->all();

        return [
            'step' => 'experiences',
            'title' => 'Your experiences',
            'summary' => $items === [] ? 'Nothing added' : implode(' · ', $items),
            'complete' => $items !== [],
            // The one section that is never shown to another family as
            // written. Only the overlap between two families is, and only as
            // a count. The screen has to say so, or the list looks public.
            'private' => true,
        ];
    }

    /** @return array<string, mixed> */
    private static function interests(Registration $family): array
    {
        $labels = collect($family->orderedInterests())->map(fn ($s) => Taxonomy::interestLabel($s));
        $styles = collect($family->connection_styles ?? [])->map(fn ($s) => Taxonomy::connectionStyleLabel($s));
        $both = $labels->merge($styles);

        return [
            'step' => 'interests',
            'title' => 'Interests and how you connect',
            'summary' => $both->isEmpty()
                ? 'Nothing added. What your family enjoys, and how you like to meet'
                : $both->implode(' · '),
            'complete' => $labels->isNotEmpty(),
            'private' => false,
        ];
    }

    /**
     * @param  array<int, string>|null  $slugs
     * @return array<string, mixed>
     */
    private static function list(string $step, string $title, ?array $slugs, callable $label): array
    {
        $labels = collect($slugs ?? [])->map($label);

        return [
            'step' => $step,
            'title' => $title,
            'summary' => $labels->isEmpty() ? 'Nothing added' : $labels->implode(' · '),
            'complete' => $labels->isNotEmpty(),
            'private' => false,
        ];
    }
}
