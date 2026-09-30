<?php

namespace App\Http\Middleware;

use App\Support\SearchVisibility;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends X-Robots-Tag on every response from a site that is not the real one.
 *
 * The layout's meta tag says the same thing, but only on a page that goes
 * through the layout. This covers the sitemap, the manifest, an error page —
 * anything a crawler can reach. One place, and nothing to remember per route.
 */
class KeepCopiesOutOfSearch
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! SearchVisibility::indexable($request)) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
