<?php

namespace App\Support;

/**
 * Read-only access to the experience taxonomy in config/frith-taxonomy.php.
 *
 * Everything the database stores is a slug from here. Validation goes through
 * these methods so an unknown slug can never be written — a selection nobody
 * can resolve back to a label is a family who will never be matched on it.
 */
class Taxonomy
{
    /** @return array<string, array{label: string, description: string, items: array<string, string>}> */
    public static function categories(): array
    {
        return config('frith-taxonomy.categories', []);
    }

    /** @return array<int, string> */
    public static function categorySlugs(): array
    {
        return array_keys(self::categories());
    }

    public static function category(string $slug): ?array
    {
        return self::categories()[$slug] ?? null;
    }

    public static function categoryLabel(string $slug): string
    {
        return self::category($slug)['label'] ?? $slug;
    }

    /** @return array<string, string> */
    public static function items(string $categorySlug): array
    {
        return self::category($categorySlug)['items'] ?? [];
    }

    public static function itemLabel(string $categorySlug, string $itemSlug): string
    {
        return self::items($categorySlug)[$itemSlug] ?? $itemSlug;
    }

    /** Every valid item slug within one category. @return array<int, string> */
    public static function itemSlugs(string $categorySlug): array
    {
        return array_keys(self::items($categorySlug));
    }

    /** @return array<string, string> */
    public static function familyStructures(): array
    {
        return config('frith-taxonomy.family_structures', []);
    }

    /** @return array<int, string> */
    public static function familyStructureSlugs(): array
    {
        return array_keys(self::familyStructures());
    }

    public static function familyStructureLabel(string $slug): string
    {
        return self::familyStructures()[$slug] ?? $slug;
    }

    /**
     * The categories in a given list, in taxonomy order rather than the order
     * they happened to be submitted, so section two always reads the same way.
     *
     * @param  array<int, string>  $slugs
     * @return array<int, string>
     */
    public static function orderCategories(array $slugs): array
    {
        return array_values(array_intersect(self::categorySlugs(), $slugs));
    }
}
