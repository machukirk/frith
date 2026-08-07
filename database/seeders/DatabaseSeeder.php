<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // No user seeding. Admin accounts are created deliberately with
        // `php artisan frith:admin`, so a deploy can never quietly stand up an
        // account that can read the waiting list.
        $this->call(PageSeeder::class);
    }
}
