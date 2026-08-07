<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Response;

/**
 * Generates the sitemap from the routes that actually exist.
 *
 * A static XML file would go stale the moment a page is added, and a stale
 * sitemap is worse than none — it tells a crawler to spend its budget on URLs
 * that have moved. lastmod comes from the content row, so editing the page in
 * the admin panel is what marks it as changed.
 */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [
            [
                'loc' => route('coming-soon'),
                'lastmod' => Page::query()->where('slug', 'coming-soon')->value('updated_at'),
                'changefreq' => 'weekly',
                'priority' => '1.0',
            ],
        ];

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
