<?php

namespace App\Support;

use App\Models\Page;
use Illuminate\Support\Facades\Cache;

/**
 * Reads page copy, with the database layered over the defaults in config.
 *
 * The config file stays the floor. If the pages table is empty, a key has never
 * been filled in, or a deploy adds a new field before anyone has edited it, the
 * page still renders sensible words rather than a blank space or an error.
 *
 * Copy for the site's pages lives in config/frith-content.php, one block per
 * slug. Structure is not editable — which sections a page has and in what order
 * is code, because a section is a designed thing rather than a free-form block.
 */
class PageContent
{
    private const CACHE_PREFIX = 'page-content:';

    /**
     * @return array<string, mixed>
     */
    public static function for(string $slug): array
    {
        return Cache::rememberForever(self::CACHE_PREFIX.$slug, function () use ($slug) {
            // Slugs are hyphenated because they are URLs; config keys are
            // snake_case because they are PHP array keys. "how-it-works" reads
            // from frith-content.how_it_works.
            $key = str_replace('-', '_', $slug);

            $defaults = config("frith-content.{$key}", []);

            // During `migrate:fresh` and on a brand new install the table may not
            // exist yet. Falling back to config keeps artisan usable.
            $stored = rescue(
                fn () => Page::query()->where('slug', $slug)->value('content'),
                rescue: null,
                report: false,
            );

            return is_array($stored) ? self::merge($defaults, $stored) : $defaults;
        });
    }

    /**
     * Dot-notation read, e.g. PageContent::get('home', 'hero.headline').
     */
    public static function get(string $slug, string $key, mixed $default = null): mixed
    {
        return data_get(self::for($slug), $key, $default);
    }

    public static function forget(string $slug): void
    {
        Cache::forget(self::CACHE_PREFIX.$slug);
    }

    /**
     * Stored content wins, key by key.
     *
     * Lists are replaced outright rather than merged element by element. If an
     * editor deletes the third card, an element-wise merge would quietly put the
     * default third card back — the deletion would look like it failed.
     *
     * @param  array<string, mixed>  $defaults
     * @param  array<string, mixed>  $stored
     * @return array<string, mixed>
     */
    private static function merge(array $defaults, array $stored): array
    {
        foreach ($stored as $key => $value) {
            if (is_array($value) && ! array_is_list($value) && is_array($defaults[$key] ?? null)) {
                $defaults[$key] = self::merge($defaults[$key], $value);

                continue;
            }

            // An editor clearing an optional field should not silently resurrect
            // the default, but a key that was never touched should still fall back.
            if ($value === null && ! array_key_exists($key, $stored)) {
                continue;
            }

            $defaults[$key] = $value;
        }

        return $defaults;
    }
}
