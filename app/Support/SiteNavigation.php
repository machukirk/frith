<?php

namespace App\Support;

/**
 * The links in the header and the footer.
 *
 * Config-backed for now, like every other piece of copy on the site was before
 * it moved into the admin panel. Routes that do not exist yet resolve to '#'
 * rather than throwing, so a link can be designed before it is built.
 */
class SiteNavigation
{
    /** @return array<int, array{label: string, url: string}> */
    /**
     * The header's links.
     *
     * A family who is logged in gets the product's own set, which is what the
     * designs show on every screen behind the door. Everybody else gets the
     * marketing one.
     */
    public static function primary(): array
    {
        return self::resolve(config(
            auth('founder')->check() ? 'frith-nav.menu_founder' : 'frith-nav.primary',
            [],
        ));
    }

    /**
     * The phone menu, which carries more than the top bar has room for.
     *
     * @return array<int, array{label: string, url: string, meta: string|null}>
     */
    public static function menu(bool $founder = false): array
    {
        return self::resolve(config($founder ? 'frith-nav.menu_founder' : 'frith-nav.menu', []));
    }

    /** @return array<int, array{label: string, url: string, meta: string|null}> */
    public static function menuFoot(bool $founder = false): array
    {
        return self::resolve(config($founder ? 'frith-nav.menu_foot_founder' : 'frith-nav.menu_foot', []));
    }

    /** @return array<int, array{title: string, links: array<int, array{label: string, url: string}>}> */
    public static function footer(): array
    {
        return collect(config('frith-nav.footer', []))
            ->map(fn (array $group) => [
                'title' => $group['title'],
                'links' => self::resolve($group['links']),
            ])
            ->all();
    }

    /**
     * @param  array<int, array{label: string, route?: string, url?: string}>  $items
     * @return array<int, array{label: string, url: string}>
     */
    private static function resolve(array $items): array
    {
        return collect($items)
            ->map(fn (array $item) => [
                'label' => $item['label'],
                'url' => self::url($item),
                'meta' => $item['meta'] ?? null,
            ])
            ->all();
    }

    /**
     * A route by name, or a dead anchor if that page is not built yet.
     *
     * The footer carries the whole designed sitemap while the pages behind it
     * are still being built, and a missing one should not 500 every page that
     * renders the nav.
     */
    public static function url(string|array $item): string
    {
        if (is_string($item)) {
            $item = ['route' => $item];
        }

        if (isset($item['url'])) {
            return $item['url'];
        }

        $route = $item['route'] ?? null;

        return $route && app('router')->has($route) ? route($route) : '#';
    }
}
