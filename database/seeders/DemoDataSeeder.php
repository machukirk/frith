<?php

namespace Database\Seeders;

use App\Models\Registration;
use App\Support\Taxonomy;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

/**
 * Believable local data, so the admin panel can be looked at properly.
 *
 * Never for production: it writes invented families, and a waiting list you
 * cannot tell apart from the real one is a genuinely bad afternoon.
 *
 *   ddev artisan db:seed --class=DemoDataSeeder      add it
 *   ddev artisan frith:demo --clear                  take it away again
 */
class DemoDataSeeder extends Seeder
{
    /** Spread across a few outcodes so the "busiest area" figure means something. */
    private const FAMILIES = [
        ['Priya', 'SS9', true], ['Dan', 'SS0', true], ['Aoife', 'M21', true],
        ['Marcus', 'SS9', true], ['Nadia', 'BS7', true], ['Tom', 'SS9', true],
        ['Grace', 'LS6', true], ['Ellie', 'SS0', true], ['Rhys', 'CF14', true],
        ['Joanne', 'EH11', true], ['Sam', 'SS9', false], ['Beth', 'NE6', false],
        ['Iwona', 'SS0', false],
    ];

    public function run(): void
    {
        if (App::isProduction()) {
            $this->command->error('Not in production. This writes invented families.');

            return;
        }

        $categories = Taxonomy::categorySlugs();
        $structures = Taxonomy::familyStructureSlugs();

        // Continue from wherever the real numbering got to, the same way the
        // controller does. Hard-coding 1..n collides with anything already there.
        $nextFounderNumber = (int) Registration::query()->max('founder_number');

        foreach (self::FAMILIES as $i => [$first, $outcode, $finished]) {
            $email = strtolower($first).'.'.($i + 1).'@example.com';

            if (Registration::query()->where('email', $email)->exists()) {
                continue;
            }

            $areas = collect($categories)->shuffle()->take(rand(1, 4))->values()->all();
            $registeredAt = now()->subDays(rand(0, 20))->subHours(rand(0, 23));

            $registration = Registration::query()->create([
                'email' => $email,
                'first_name' => $first,
                'postcode_outcode' => $outcode,
                'family_structures' => collect($structures)->shuffle()->take(rand(1, 2))->values()->all(),
                'support_areas' => $finished ? $areas : null,
                // The unfinished ones stopped somewhere in the middle, which is
                // the case the panel most needs to make visible.
                'furthest_step' => $finished ? 6 : rand(2, 4),
                'completed_at' => $finished ? $registeredAt : null,
                'founder_number' => ++$nextFounderNumber,
                'email_verified_at' => $finished && $i % 3 === 0 ? $registeredAt : null,
                'consent_version' => config('frith.consent.version'),
                'consent_text' => config('frith.consent.text'),
                'consented_at' => $registeredAt,
                'consent_ip' => '203.0.113.'.rand(2, 250),
                'consent_user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15',
                'created_at' => $registeredAt,
                'updated_at' => $registeredAt,
            ]);

            foreach (range(1, rand(1, 3)) as $n) {
                $registration->children()->create([
                    'birth_month' => rand(1, 12),
                    'birth_year' => rand(2009, 2024),
                    'position' => $n - 1,
                ]);
            }

            // Only some fill in section two — it is optional, and the panel
            // should show what that actually looks like.
            if ($finished && $i % 4 !== 3) {
                foreach (array_slice($areas, 0, rand(1, 2)) as $category) {
                    foreach (collect(Taxonomy::itemSlugs($category))->shuffle()->take(rand(1, 4)) as $item) {
                        $registration->experiences()->firstOrCreate([
                            'category' => $category,
                            'item' => $item,
                        ]);
                    }
                }
            }
        }

        $this->command->info('  Demo data added. Remove it with: ddev artisan frith:demo --clear');
    }
}
