<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Searches the help topics.
 *
 * Server-side and case-insensitive, over the same content file the page is
 * built from, so a topic added in the admin panel is searchable the moment it
 * is saved. No index to rebuild and no JavaScript. The form is a GET, which
 * also means a result page can be linked to and bookmarked.
 */
class HelpSearch
{
    /**
     * @return Collection<int, array{title: string, topic: string, url: string}>
     */
    public static function run(?string $query): Collection
    {
        $query = trim((string) $query);

        if ($query === '') {
            return collect();
        }

        return collect(PageContent::get('help', 'topics', []))
            ->flatMap(fn (array $card) => collect($card['links'] ?? [])
                ->map(fn (array $link) => [
                    'title' => $link['label'] ?? '',
                    'topic' => $card['title'],
                    // Searched but never shown. Most of the help centre is now
                    // the answers rather than the labels, and matching on
                    // labels alone meant "password", "delete" and "unsubscribe"
                    // all returned nothing while the words sat on the page.
                    'body' => $link['answer'] ?? '',
                    'url' => self::urlFor($link, $query),
                ]))
            ->reject(fn (array $item) => $item['title'] === '')
            ->filter(fn (array $item) => Str::contains(
                $item['title'].' '.$item['topic'].' '.$item['body'],
                $query,
                ignoreCase: true,
            ))
            ->map(fn (array $item) => collect($item)->except('body')->all())
            ->values();
    }

    /**
     * The id of a topic's answer on the help page, so that the page and a
     * search result agree on one.
     *
     * The stored anchor, which is the one that must not change once it has
     * shipped. Falling back to the label keeps an answer somebody adds in the
     * admin panel reachable without them having to know what an anchor is.
     *
     * @param  array{label?: string, anchor?: string}  $link
     */
    public static function anchorFor(array $link): string
    {
        return $link['anchor'] ?? Str::slug($link['label'] ?? '');
    }

    /**
     * Where a result goes.
     *
     * Most of the help centre is answered on the help page itself rather than
     * on a page of its own, and those results used to come back through
     * SiteNavigation as '#'. A family searched, got four results, pressed one
     * and the page did nothing. So a topic with an answer points at its own
     * disclosure instead.
     *
     * The fragment scrolls to it and `open` is what actually opens it. A
     * fragment never reaches the server, so the page cannot tell from '#'
     * alone which one to render open, and CSS cannot open a closed <details>
     * from :target. The query string is the part that has to be there.
     *
     * `q` is carried along so the results somebody was reading are still on
     * screen underneath the answer, rather than being thrown away by the act
     * of reading one of them.
     *
     * @param  array{label: string, route?: string|null, url?: string, anchor?: string, answer?: string}  $link
     */
    private static function urlFor(array $link, string $query): string
    {
        if (isset($link['url'])) {
            return $link['url'];
        }

        $url = SiteNavigation::url($link);

        // A real page behind it. '#' is what SiteNavigation gives back for a
        // route that is null or not built, and that is what must not ship.
        if ($url !== '#') {
            return $url;
        }

        if (blank($link['answer'] ?? null)) {
            return route('help', ['q' => $query]);
        }

        $anchor = self::anchorFor($link);

        return route('help', ['q' => $query, 'open' => $anchor]).'#'.$anchor;
    }
}
