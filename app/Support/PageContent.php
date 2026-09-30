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
     * Content to use instead of what is stored, for one request.
     *
     * The admin panel's live preview renders the real page from the form's
     * unsaved state. Overriding here rather than passing content down through
     * every view means the preview goes through exactly the same Blade and the
     * same stylesheet as the page itself, so it cannot drift from it.
     *
     * @var array<string, array<string, mixed>>
     */
    private static array $overrides = [];

    /** @param array<string, mixed> $content */
    public static function preview(string $slug, array $content): void
    {
        self::$overrides[$slug] = $content;
    }

    /**
     * Stops previewing.
     *
     * The override is static, so without this it would outlive the render and
     * a later read in the same process would get somebody's draft.
     */
    public static function endPreview(?string $slug = null): void
    {
        if ($slug === null) {
            self::$overrides = [];

            return;
        }

        unset(self::$overrides[$slug]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function for(string $slug): array
    {
        // A preview never touches the cache, in either direction: it must not
        // read a stale one and must not leave its unsaved content behind for
        // the next visitor to the real page.
        //
        // It layers over the stored page as well as the defaults, because a
        // draft is only the fields the form has hydrated so far — everything
        // the editor has not scrolled to yet still has to render.
        if (isset(self::$overrides[$slug])) {
            $stored = rescue(
                fn () => Page::query()->where('slug', $slug)->value('content'),
                rescue: null,
                report: false,
            );

            $base = is_array($stored)
                ? self::merge(self::defaults($slug), $stored)
                : self::defaults($slug);

            return self::merge($base, self::$overrides[$slug]);
        }

        return Cache::rememberForever(self::CACHE_PREFIX.$slug, function () use ($slug) {
            $defaults = self::defaults($slug);

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
     * The shipped copy for a page.
     *
     * Slugs are hyphenated because they are URLs; config keys are snake_case
     * because they are PHP array keys. "how-it-works" reads from
     * frith-content.how_it_works.
     *
     * @return array<string, mixed>
     */
    private static function defaults(string $slug): array
    {
        return config('frith-content.'.str_replace('-', '_', $slug), []);
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
