<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Database\Seeders\PageSeeder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

/**
 * Generates the sitemap from the pages that actually exist.
 *
 * A static XML file would go stale the moment a page is added, and a stale
 * sitemap is worse than none — it tells a crawler to spend its budget on URLs
 * that have moved. The list comes from the same place the seeder takes it, so
 * a new page appears here by existing rather than by being remembered.
 *
 * lastmod comes from the content row, so editing a page in the admin panel is
 * what marks it as changed.
 */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $changed = Page::query()->pluck('updated_at', 'slug');

        $urls = collect(PageSeeder::pages())
            ->keys()
            // A page that has copy but no route yet is not a URL.
            ->filter(fn (string $slug) => Route::has($slug) || $slug === 'home')
            ->map(fn (string $slug) => [
                'loc' => route($slug),
                'lastmod' => $changed[$slug] ?? null,
                'changefreq' => $slug === 'home' ? 'weekly' : 'monthly',
                'priority' => $slug === 'home' ? '1.0' : '0.7',
            ])
            ->values()
            ->all();

        return response(view('sitemap', ['urls' => $urls])->render(), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
