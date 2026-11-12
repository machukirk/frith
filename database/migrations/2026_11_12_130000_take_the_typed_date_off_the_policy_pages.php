<?php

use App\Support\PageContent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Takes the typed "Last updated" line out of the five policy pages.
 *
 * Each one carried its own date as a string inside the editable copy. An
 * editor changed the Privacy Policy, the row's updated_at moved, the sitemap's
 * lastmod moved with it, and the page went on saying 9 September 2026. The
 * views now print the date from pages.updated_at instead, via
 * App\Support\PolicyPages::lastUpdated.
 *
 * Config alone would not have been enough. config/content is only the FLOOR,
 * and PageContent layers the stored row over the top key by key, so the
 * string has to come out of the rows as well. Left in, it would also keep its
 * field in the admin panel, because the editing form is built from the stored
 * content: a box an editor can type a date into that no visitor will ever see.
 *
 * Conditional, like every reword here. Only the exact line that shipped is
 * removed. If somebody has rewritten theirs, those are their words and a
 * migration is not the place to delete them. They will see a field that no
 * longer renders, which is the lesser of the two wrongs, and clearing it is
 * one edit.
 *
 * Worth knowing: pages.content is JSON, so the middle dot is stored as a
 * · escape. Grepping the column as text finds nothing and reads as a
 * clean result. Decode before you search.
 */
return new class extends Migration
{
    /** @var array<int, array{slug: string, path: string, was: string}> */
    private const SHIPPED = [
        [
            'slug' => 'privacy',
            'path' => 'head.doc_meta',
            'was' => 'Last updated 9 September 2026 · about 7 minutes to read',
        ],
        [
            'slug' => 'terms',
            'path' => 'head.doc_meta',
            'was' => 'Last updated 9 September 2026 · about 6 minutes to read',
        ],
        [
            'slug' => 'community-guidelines',
            'path' => 'head.updated',
            'was' => 'Last updated 9 September 2026 · about 5 minutes to read',
        ],
        [
            'slug' => 'meet-up-safety',
            'path' => 'head.updated',
            'was' => 'Last updated 9 September 2026 · about 4 minutes to read',
        ],
        [
            'slug' => 'reporting',
            'path' => 'head.meta',
            'was' => 'Last updated 9 September 2026 · about 4 minutes to read',
        ],
    ];

    public function up(): void
    {
        foreach (self::SHIPPED as $line) {
            $this->rewrite($line['slug'], function (array $content) use ($line): ?array {
                if (data_get($content, $line['path']) !== $line['was']) {
                    return null;
                }

                Arr::forget($content, $line['path']);

                return $content;
            });
        }
    }

    public function down(): void
    {
        foreach (self::SHIPPED as $line) {
            $this->rewrite($line['slug'], function (array $content) use ($line): ?array {
                // Only where up() took one out. A row that still has its own
                // line keeps it.
                if (data_get($content, $line['path']) !== null) {
                    return null;
                }

                data_set($content, $line['path'], $line['was']);

                return $content;
            });
        }
    }

    /** @param  callable(array<string, mixed>): ?array<string, mixed>  $change */
    private function rewrite(string $slug, callable $change): void
    {
        $row = DB::table('pages')->where('slug', $slug)->first();

        if ($row === null) {
            return;
        }

        $content = json_decode($row->content ?? '{}', true);

        if (! is_array($content)) {
            return;
        }

        $changed = $change($content);

        if ($changed === null) {
            return;
        }

        // updated_at is deliberately left where it is, and must stay that way.
        // The other reword migrations here touch it, because a reword is a
        // change to the policy. This is not. Taking out a line that duplicates
        // the column is housekeeping, and the column is now the date the page
        // prints: bumping it would have all five policies announce they
        // changed on the day of a deploy that changed nothing they say.
        DB::table('pages')->where('id', $row->id)->update([
            'content' => json_encode($changed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        // Written round the back of the model, so the hook that clears this
        // cache on save never fires. Without this the cached copy keeps the
        // key the row has just lost.
        PageContent::forget($slug);
    }
};
