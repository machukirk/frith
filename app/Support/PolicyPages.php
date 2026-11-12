<?php

namespace App\Support;

use App\Models\Page;
use DateTimeInterface;

/**
 * The policies, as the sidebar on each of them lists them, and when each one
 * last changed.
 *
 * One list in one place: four pages show it, and a policy that appears on
 * three of the four is the kind of thing nobody notices for a year. The date
 * is here for the same reason, so five views cannot each grow their own way
 * of working it out.
 */
class PolicyPages
{
    /** @return array<string, string> route name => label */
    public const PAGES = [
        'terms' => 'Terms of Service',
        'privacy' => 'Privacy Policy',
        'community-guidelines' => 'Community Guidelines',
        'meet-up-safety' => 'Meet-up safety',
        'reporting' => 'Reporting & complaints',
        'cookies' => 'Cookies',
    ];

    /** @return array<int, array{label: string, url: string, current: bool}> */
    public static function all(?string $current = null): array
    {
        return collect(self::PAGES)
            ->map(fn (string $label, string $route) => [
                'label' => $label,
                'url' => SiteNavigation::url($route),
                'current' => $route === $current,
            ])
            ->values()
            ->all();
    }

    /**
     * When a policy last changed, worded the way the page prints it.
     *
     * Read from that page's own pages.updated_at, never from its copy. The
     * line used to be a string inside the editable content, which meant the
     * one fact a policy exists to state could be wrong the moment somebody
     * saved the page: the row moved, the sitemap's lastmod moved with it
     * (SitemapController reads the same column), and the page went on
     * claiming a date two months earlier. Do not put it back in the copy.
     *
     * The wording is not editable either. "Last updated" in front of a date
     * that is generated is part of the fact, not copy to play with, and an
     * editable prefix is a place to type a second date into.
     *
     * No reading time beside it any more. The old one was typed by hand and
     * so was wrong for the same reason the date was. Computing one is worse
     * than it looks: the only thing this class can count is the whole content
     * array, which carries search descriptions, button labels and alt text
     * that nobody reads, so the number would be confidently wrong and no
     * editor could correct it. The date is the part that matters.
     *
     * Null when the page has no row, which is a fresh install before the
     * seeder has run. No row means no date we can stand behind, and the views
     * print nothing rather than invent one.
     */
    public static function lastUpdated(string $slug): ?string
    {
        // Not cached. PageContent's cache is cleared by a hook on the Page
        // model, and anything cached here would miss a migration that writes
        // to the row directly. One read of a ten row table is cheaper than a
        // date that is stale exactly when a policy has just changed.
        //
        // During migrate:fresh the table may not exist yet, and a policy page
        // rendered then should still render.
        $changed = rescue(
            fn () => Page::query()->where('slug', $slug)->value('updated_at'),
            rescue: null,
            report: false,
        );

        return $changed instanceof DateTimeInterface
            ? 'Last updated '.$changed->format('j F Y')
            : null;
    }
}
