<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Searches the help topics.
 *
 * Server-side and case-insensitive, over the same content file the page is
 * built from, so a topic added in the admin panel is searchable the moment it
 * is saved. No index to rebuild and no JavaScript — the form is a GET, which
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
                    'title' => $link['label'],
                    'topic' => $card['title'],
                    'url' => $link['url'] ?? SiteNavigation::url($link['route'] ?? 'help'),
                ]))
            ->filter(fn (array $item) => Str::contains(
                $item['title'].' '.$item['topic'],
                $query,
                ignoreCase: true,
            ))
            ->values();
    }
}
