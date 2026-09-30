<?php

namespace App\Http\Controllers;

use App\Support\SearchVisibility;
use Illuminate\Http\Response;

/**
 * robots.txt, decided at request time rather than shipped as a file.
 *
 * A static public/robots.txt is the same file wherever the code is deployed,
 * so the staging site would hand crawlers the same open invitation the real
 * one does — and, worse, point them at frith.community's sitemap from a
 * different domain. This says Disallow everywhere except on the real host.
 */
class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        return response($this->body(), 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    private function body(): string
    {
        if (! SearchVisibility::indexable()) {
            return implode("\n", [
                '# Not the real Frith — a copy, for building and checking things on.',
                '# The site itself is at https://'.SearchVisibility::canonicalHost(),
                '',
                'User-agent: *',
                'Disallow: /',
                '',
            ]);
        }

        return implode("\n", [
            '# '.SearchVisibility::canonicalHost(),
            '',
            'User-agent: *',
            'Allow: /',
            '',
            '# The admin panel has nothing to offer a search result. The registration',
            '# steps are left crawlable on purpose: they carry a noindex tag, and a',
            '# crawler has to be able to fetch a page to see that it says noindex.',
            'Disallow: /admin',
            '',
            'Sitemap: '.route('sitemap'),
            '',
        ]);
    }
}
