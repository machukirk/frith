<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Seeds the editable copy from the defaults in config/frith.php.
     *
     * Uses firstOrCreate, not updateOrCreate: running seeders again on a live
     * site must never overwrite what an editor has written.
     */
    public function run(): void
    {
        Page::query()->firstOrCreate(
            ['slug' => 'coming-soon'],
            [
                'name' => 'Coming soon page',
                'content' => config('frith.coming_soon'),
            ],
        );
    }
}
