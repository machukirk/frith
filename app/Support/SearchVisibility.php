<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Whether this site is the one that belongs in a search result.
 *
 * Decided from the host the request actually arrived on, not from APP_URL.
 * That is deliberate: a staging site is usually stood up by copying
 * production's .env, so its APP_URL says frith.community while it answers on
 * an entirely different domain. Trusting APP_URL would call that site live and
 * invite Google to index a duplicate of the real one.
 */
class SearchVisibility
{
    public static function indexable(?Request $request = null): bool
    {
        $override = config('frith.site.indexable');

        if ($override !== null && $override !== '') {
            return filter_var($override, FILTER_VALIDATE_BOOLEAN);
        }

        return self::host($request) === self::canonicalHost();
    }

    public static function canonicalHost(): string
    {
        return strtolower(trim((string) config('frith.site.canonical_host')));
    }

    /** The host in hand: the request's, or APP_URL's on the command line. */
    private static function host(?Request $request): string
    {
        $host = $request?->getHost()
            ?? request()?->getHost()
            ?? parse_url((string) config('app.url'), PHP_URL_HOST);

        return strtolower(trim((string) $host));
    }
}
