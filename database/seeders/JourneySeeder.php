<?php

namespace Database\Seeders;

use App\Models\Journey;
use Illuminate\Database\Seeder;

/**
 * Seeds the nine Journeys from config.
 *
 * firstOrCreate, like every other seeder here: running it again on a live
 * site must never overwrite wording an editor has changed, and must never
 * bring back a Journey somebody has deliberately archived.
 */
class JourneySeeder extends Seeder
{
    public function run(): void
    {
        $position = 0;

        foreach (config('frith-journeys', []) as $slug => $journey) {
            Journey::query()->firstOrCreate(
                ['slug' => $slug],
                [
                    'title' => $journey['title'],
                    'blurb' => $journey['blurb'] ?? null,
                    'position' => $position,
                ],
            );

            $position++;
        }
    }
}
