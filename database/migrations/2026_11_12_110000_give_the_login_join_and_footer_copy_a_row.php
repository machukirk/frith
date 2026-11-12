<?php

use App\Support\PageContent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Creates the pages rows for the log-in screens, the end of registration and
 * the site footer.
 *
 * PageSeeder has just learned these three slugs, which is enough for a fresh
 * install and nothing at all for a server that already has a database. A
 * deploy runs migrations; it seeds only when somebody asks it to. Without
 * this, staging and production would get the admin screens for these pages
 * with no row behind them, and every word on /login, /forgot, /link-sent,
 * /verified, /join/welcome and the foot of every page would stay fixed at
 * deploy time.
 *
 * The content is read from config rather than copied out into this file. A
 * migration is normally frozen in time and a copy here would be the honest
 * version of that, but config/content is the floor by design: this row is
 * meant to be what those files say on the day it runs, exactly as PageSeeder
 * would write it. Two copies of forty lines of prose would drift apart with
 * nothing to catch it. The slug and the name are written out, because those
 * are this migration's own decision rather than the content's.
 *
 * Any slug that already has a row is left alone. Somebody may have seeded by
 * hand between the deploy and this running, and their row is not ours to
 * replace.
 */
return new class extends Migration
{
    /** @var array<string, string> */
    private const PAGES = [
        'auth' => 'Log in screens',
        'join' => 'End of registration',
        'footer' => 'Site footer',
    ];

    public function up(): void
    {
        foreach (self::PAGES as $slug => $name) {
            if (DB::table('pages')->where('slug', $slug)->exists()) {
                continue;
            }

            $content = config('frith-content.'.str_replace('-', '_', $slug));

            if (! is_array($content)) {
                continue;
            }

            DB::table('pages')->insert([
                'slug' => $slug,
                'name' => $name,
                'content' => json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->forget($slug);
        }
    }

    public function down(): void
    {
        // Only the rows nobody has written into. Rolling back is a developer
        // undoing a deploy, and deleting a page an editor has since changed
        // would throw their words away with nothing to get them back from.
        DB::table('pages')
            ->whereIn('slug', array_keys(self::PAGES))
            ->whereNull('updated_by')
            ->delete();

        foreach (array_keys(self::PAGES) as $slug) {
            $this->forget($slug);
        }
    }

    /**
     * Inserting round the model means Page::saved() never fires, and that hook
     * is what normally clears this. PageContent caches forever, so on a server
     * that has served a page since the last cache clear the new row would
     * otherwise sit there doing nothing.
     *
     * Rescued because the row is the point and the cache is the nicety. A
     * cache store that is not answering must not fail a deploy halfway and
     * leave the migration table disagreeing with the pages table.
     */
    private function forget(string $slug): void
    {
        rescue(fn () => PageContent::forget($slug), report: false);
    }
};
