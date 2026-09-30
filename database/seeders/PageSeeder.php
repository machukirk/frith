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
     * The pages that have editable copy behind them.
     *
     * @return array<string, string>
     */
    public static function pages(): array
    {
        return ['home' => 'Home page'];
    }
}
