<?php

namespace App\Console\Commands;

use App\Models\Registration;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Console\Command;

/**
 * Adds or removes the local demo data.
 *
 * Clearing only ever touches @example.com addresses, which is a reserved
 * domain nobody can receive mail at — so this cannot delete a real family.
 */
class DemoData extends Command
{
    protected $signature = 'frith:demo {--clear : Remove the demo data instead}';

    protected $description = 'Fill the local admin panel with believable data, or clear it';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('Refusing to run in production.');

            return self::FAILURE;
        }

        if ($this->option('clear')) {
            $registrations = Registration::query()->where('email', 'like', '%@example.com')->get();
            $registrations->each->delete();

            $this->info("  Removed {$registrations->count()} demo registration(s).");

            return self::SUCCESS;
        }

        $this->call('db:seed', ['--class' => DemoDataSeeder::class, '--force' => true]);

        return self::SUCCESS;
    }
}
