<?php

namespace App\Http\Controllers;

use App\Support\PageContent;
use Illuminate\Http\JsonResponse;

/**
 * Serves the web app manifest.
 *
 * Built in PHP and served through a route rather than sat in public/ for two
 * reasons: Cloudways' nginx has no mime type for .webmanifest and hands it out
 * as application/octet-stream, and building it here means the name and
 * description follow whatever is in the CMS instead of drifting from it.
 */
class WebManifestController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $copy = PageContent::for('coming-soon');

        return response()->json([
            'name' => $copy['meta']['title'] ?? 'Frith',
            'short_name' => 'Frith',
            'description' => $copy['meta']['description'] ?? null,
            'lang' => 'en-GB',
            'dir' => 'ltr',
            'start_url' => '/',
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'portrait',
            'background_color' => '#F8F4EE',
            'theme_color' => '#F8F4EE',
            'categories' => ['social', 'lifestyle', 'education'],
            'icons' => [
                [
                    'src' => asset('brand/icon/frith-icon-192.png'),
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => asset('brand/icon/frith-icon-512.png'),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    // Android crops icons to whatever shape the launcher uses,
                    // so this one carries a safe zone around the mark.
                    'src' => asset('brand/icon/frith-icon-512-maskable.png'),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ],
            ],
        ], 200, ['Content-Type' => 'application/manifest+json'], JSON_UNESCAPED_SLASHES);
    }
}
