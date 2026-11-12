<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Takes the typed-out Journey list off the home page's stored copy.
 *
 * The front page listed nine Journey titles with nine family counts, all of
 * them typed into config/content/home once and never true again. A Journey
 * renamed in the admin kept its old name there, an archived one still
 * appeared, and "7 families" was a number nobody could correct because it was
 * copy rather than data. App\Support\JourneyBoard reads the journeys table
 * instead.
 *
 * The config floor has already lost the list. This removes it from the stored
 * page too, which is the version that actually renders, and which would
 * otherwise go on offering an editor a block of text that changes nothing.
 *
 * The note keeps its wording but gains a :count placeholder, the same way the
 * policy pages carry :email, so "These are all nine" stays correct when a
 * tenth Journey is added.
 *
 * Conditional, like every reword here: only where the stored text is still
 * what shipped. Anything an editor has rewritten is left as they wrote it.
 */
return new class extends Migration
{
    private const NOTE_WAS = 'These are all nine. You can be in as many as you like, and leave any of them at any time.';

    private const NOTE_NOW = 'These are all :count. You can be in as many as you like, and leave any of them at any time.';

    public function up(): void
    {
        $this->edit(function (array $content): array {
            // The list is data now. Dropping it stops the editor being shown a
            // field that has no effect on the page.
            unset($content['journeys']['items']);

            if (($content['journeys']['note'] ?? null) === self::NOTE_WAS) {
                $content['journeys']['note'] = self::NOTE_NOW;
            }

            return $content;
        });
    }

    public function down(): void
    {
        $this->edit(function (array $content): array {
            $content['journeys']['items'] = collect(config('frith-journeys', []))
                ->map(fn (array $journey) => ['title' => $journey['title'], 'families' => 0])
                ->values()
                ->all();

            if (($content['journeys']['note'] ?? null) === self::NOTE_NOW) {
                $content['journeys']['note'] = self::NOTE_WAS;
            }

            return $content;
        });
    }

    private function edit(callable $change): void
    {
        $row = DB::table('pages')->where('slug', 'home')->first();

        if ($row === null) {
            return;
        }

        $content = json_decode($row->content ?? '{}', true);

        if (! is_array($content) || ! isset($content['journeys'])) {
            return;
        }

        DB::table('pages')->where('id', $row->id)->update([
            'content' => json_encode($change($content), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updated_at' => now(),
        ]);
    }
};
