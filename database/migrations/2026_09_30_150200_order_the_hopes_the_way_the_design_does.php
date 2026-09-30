<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Puts the hopes list into the order the design asks for.
 *
 * What somebody wants from other families first, then what they can offer,
 * then the practical — rather than the order the options happened to be
 * written in. Archived options go to the end: they are off the form, and
 * leaving them interleaved makes the list in the editor harder to read than
 * the list on the form.
 *
 * Conditional, like the rewording: if the stored order is not the one that
 * shipped, somebody has arranged it themselves and that stands.
 */
return new class extends Migration
{
    /** The order as it shipped, which is the only one this migration moves. */
    private const SHIPPED = [
        'families-who-understand',
        'parents-to-talk-to',
        'friendships-for-my-child',
        'local-families',
        'activity-ideas',
        'practical-advice',
        'similar-experience',
        'belonging',
        'supporting-others',
        'something-else',
    ];

    public function up(): void
    {
        $this->reorder(array_keys(config('frith-taxonomy.hopes', [])), self::SHIPPED);
    }

    public function down(): void
    {
        $this->reorder(self::SHIPPED, array_keys(config('frith-taxonomy.hopes', [])));
    }

    /**
     * @param  array<int, string>  $to
     * @param  array<int, string>  $expected
     */
    private function reorder(array $to, array $expected): void
    {
        $stored = DB::table('form_options')
            ->where('group', 'hopes')
            ->whereNull('parent_id')
            ->orderBy('position')
            ->pluck('slug')
            ->all();

        if ($stored !== $expected) {
            return;
        }

        foreach ($to as $position => $slug) {
            DB::table('form_options')
                ->where('group', 'hopes')
                ->whereNull('parent_id')
                ->where('slug', $slug)
                ->update(['position' => $position, 'updated_at' => now()]);
        }
    }
};
