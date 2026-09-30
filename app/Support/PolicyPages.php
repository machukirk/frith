<?php

namespace App\Support;

/**
 * The policies, as the sidebar on each of them lists them.
 *
 * One list in one place: four pages show it, and a policy that appears on
 * three of the four is the kind of thing nobody notices for a year.
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
}
