<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Support\EditablePage;
use App\Support\PageContent;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Renders a page from the admin form's unsaved state.
 *
 * The same Blade and the same stylesheet as the real page, so what an editor
 * sees while typing is the page, not an approximation of it that can drift
 * from it. The content comes from the session, put there by the edit screen
 * as fields are filled in, rather than the URL, because a page's worth of
 * copy does not belong in a query string.
 *
 * Behind the admin guard, and noindex: it is somebody's draft.
 */
class PagePreviewController extends Controller
{
    public function __invoke(Request $request, string $slug): HttpResponse
    {
        $page = Page::query()->where('slug', $slug)->firstOrFail();

        // Not every editable row is a page with a view of its own. See
        // App\Support\EditablePage: three of them are copy that appears on a
        // screen somewhere else, and one has no single screen at all.
        $view = EditablePage::view($slug);

        abort_if($view === null, 404);

        $draft = $request->session()->get(self::key($slug));

        if (is_array($draft)) {
            PageContent::preview($slug, $draft);
        }

        try {
            $html = view($view)->render();
        } finally {
            // The override is static. Left in place it would outlive this
            // render and hand somebody's draft to the next read.
            PageContent::endPreview($slug);
        }

        return response($html)
            ->header('X-Robots-Tag', 'noindex, nofollow')
            // A draft is not something to keep a copy of.
            ->header('Cache-Control', 'no-store, private');
    }

    public static function key(string $slug): string
    {
        return "page-preview.{$slug}";
    }
}
