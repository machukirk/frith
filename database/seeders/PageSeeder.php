<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Seeds the editable copy from the defaults in config/frith-content.php.
     *
     * One row per page, keyed by the slug the URL uses. firstOrCreate, not
     * updateOrCreate: running seeders again on a live site must never
     * overwrite what an editor has written.
     */
    public function run(): void
    {
        foreach (self::pages() as $slug => $name) {
            $content = config('frith-content.'.str_replace('-', '_', $slug));

            if (! is_array($content)) {
                continue;
            }

            Page::query()->firstOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'content' => $content],
            );
        }
    }

    /**
     * The slugs here that are not a page at a URL of their own.
     *
     * They have editable copy all the same, which is why they are in the list
     * below. Written out rather than worked out from the router, because the
     * things that need to know ask before the application has booted: a test's
     * data provider runs with no facade root, so Route::has() throws there.
     *
     * @var array<int, string>
     */
    public const WITHOUT_A_ROUTE = ['auth', 'join', 'footer'];

    /**
     * The pages that have editable copy behind them.
     *
     * @return array<string, string>
     */
    public static function pages(): array
    {
        return [
            'home' => 'Home page',
            'how-it-works' => 'How it works',
            'about' => 'About',
            'help' => 'Help centre',
            'meet-up-safety' => 'Meet-up safety guide',
            'reporting' => 'Reporting & complaints',
            'community-guidelines' => 'Community Guidelines',
            'terms' => 'Terms of Service',
            'privacy' => 'Privacy Policy',
            'frith-plus' => 'Frith+',
            'cookies' => 'Cookies',

            // Not pages with a URL of their own: the four log-in screens, the
            // screen at the end of registration, and the four lines at the
            // bottom of every page. They are listed here because this array is
            // what decides whether copy is editable at all. PagesTable filters
            // the admin list to it, so without a line here a hand-inserted row
            // would still never appear.
            //
            // SitemapController already drops any slug with no route of that
            // name, so none of these three reaches the sitemap. Two loose ends
            // it does not cover: the admin's "View" button resolves to '#' for
            // them, and the preview pane looks for a pages.<slug> view and 404s.
            // Both are worth fixing where they live. Neither is worth leaving
            // the copy developer-only over.
            'auth' => 'Log in screens',
            'join' => 'End of registration',
            'footer' => 'Site footer',
        ];
    }
}
