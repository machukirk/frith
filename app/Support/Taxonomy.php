<?php

namespace App\Support;

/**
 * The experience taxonomy, as the rest of the application sees it.
 *
 * Backed by the database now, with config as the fallback — but the shape of
 * these methods has not changed, so controllers, views and exports did not
 * have to. See App\Support\FormDefinition for the layering.
 *
 * Two views of the same data, and the difference matters:
 *
 *   categories() / items()     what to offer and what to accept — live only
 *   categoryLabel() / itemLabel()  what to display — archived included
 *
 * An archived option must stop being offered without erasing the answer of
 * every family who already chose it.
 */
class Taxonomy
{
    public const FORM = 'founders-registration';

    /** @return array<string, array{label: string, description: ?string, items: array<string, string>}> */
    public static function categories(): array
    {
        return collect(self::rawCategories())
            ->reject(fn ($category) => $category['archived'])
            ->map(fn ($category) => [
                'label' => $category['label'],
                'description' => $category['description'],
                'items' => collect($category['items'])
                    ->reject(fn ($item) => $item['archived'])
                    ->map(fn ($item) => $item['label'])
                    ->all(),
            ])
            ->all();
    }

    /** @return array<int, string> */
    public static function categorySlugs(): array
    {
        return array_keys(self::categories());
    }

    /** @return array{label: string, description: ?string, items: array<string, string>}|null */
    public static function category(string $slug): ?array
    {
        return self::categories()[$slug] ?? null;
    }

    /** Includes archived, because an answer already given still needs a name. */
    public static function categoryLabel(string $slug): string
    {
        return self::rawCategories()[$slug]['label'] ?? $slug;
    }

    /** @return array<string, string> */
    public static function items(string $categorySlug): array
    {
        return self::category($categorySlug)['items'] ?? [];
    }

    /** @return array<int, string> */
    public static function itemSlugs(string $categorySlug): array
    {
        return array_keys(self::items($categorySlug));
    }

    /** Includes archived, for the same reason as categoryLabel(). */
    public static function itemLabel(string $categorySlug, string $itemSlug): string
    {
        return self::rawCategories()[$categorySlug]['items'][$itemSlug]['label'] ?? $itemSlug;
    }

    /** @return array<string, string> */
    public static function familyStructures(): array
    {
        return self::liveLabels('family_structures');
    }

    /** @return array<int, string> */
    public static function familyStructureSlugs(): array
    {
        return array_keys(self::familyStructures());
    }

    public static function familyStructureLabel(string $slug): string
    {
        return self::label('family_structures', $slug);
    }

    /**
     * What families enjoy, as opposed to what they find hard.
     *
     * Carries a description as well as a label, so the choice cards can say
     * "Parks, walks, gardening" under "Outdoors & Nature" — the examples are
     * what make a broad heading mean something to somebody skim-reading.
     *
     * @return array<string, array{label: string, description: ?string}>
     */
    public static function interests(): array
    {
        return collect(self::rawGroup('interests'))
            ->reject(fn ($option) => $option['archived'])
            ->map(fn ($option) => [
                'label' => $option['label'],
                'description' => $option['description'],
            ])
            ->all();
    }

    /** @return array<int, string> */
    public static function interestSlugs(): array
    {
        return array_keys(self::interests());
    }

    /** Includes archived, for the same reason as categoryLabel(). */
    public static function interestLabel(string $slug): string
    {
        return self::label('interests', $slug);
    }

    /**
     * Taxonomy order rather than submission order, so a family's interests
     * always read the same way round wherever they are shown.
     *
     * @param  array<int, string>  $slugs
     * @return array<int, string>
     */
    public static function orderInterests(array $slugs): array
    {
        return array_values(array_intersect(array_keys(self::rawGroup('interests')), $slugs));
    }

    /** @return array<string, string> */
    public static function activitySupports(): array
    {
        return self::liveLabels('activity_supports');
    }

    /** @return array<int, string> */
    public static function activitySupportSlugs(): array
    {
        return array_keys(self::activitySupports());
    }

    /** Includes archived, for the same reason as categoryLabel(). */
    public static function activitySupportLabel(string $slug): string
    {
        return self::label('activity_supports', $slug);
    }

    /**
     * Categories in taxonomy order rather than submission order, so section
     * two always reads the same way round.
     *
     * @param  array<int, string>  $slugs
     * @return array<int, string>
     */
    public static function orderCategories(array $slugs): array
    {
        return array_values(array_intersect(self::categorySlugs(), $slugs));
    }

    /**
     * A flat group's live options as slug => label. Archived ones are dropped,
     * because these feed what gets offered and what gets accepted.
     *
     * @return array<string, string>
     */
    private static function liveLabels(string $group): array
    {
        return collect(self::rawGroup($group))
            ->reject(fn ($option) => $option['archived'])
            ->map(fn ($option) => $option['label'])
            ->all();
    }

    /** Archived included: an answer already given still needs a name. */
    private static function label(string $group, string $slug): string
    {
        return self::rawGroup($group)[$slug]['label'] ?? $slug;
    }

    /** @return array<string, mixed> */
    private static function rawCategories(): array
    {
        return self::rawGroup('support_areas');
    }

    /** @return array<string, mixed> */
    private static function rawGroup(string $group): array
    {
        return FormDefinition::for(self::FORM)['options'][$group] ?? [];
    }
}
