<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Support\PolicyPages;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The "Last updated" line on the five policies.
 *
 * A policy exists to be the version in force on a given date, so that date is
 * the one field that must not be hand-maintained. It used to be a string in
 * the editable copy: an edit moved the row, the sitemap's lastmod moved with
 * it, and the page kept claiming the date somebody typed months before.
 */
class PolicyDateTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{0: string}> */
    public static function policies(): array
    {
        return collect(array_keys(PolicyPages::PAGES))
            ->mapWithKeys(fn (string $slug) => [$slug => [$slug]])
            ->all();
    }

    #[Test]
    #[DataProvider('policies')]
    public function the_date_comes_from_the_page_row(string $slug): void
    {
        $this->seed(PageSeeder::class);

        Page::query()->where('slug', $slug)->update([
            'updated_at' => Carbon::parse('2027-03-03 09:00:00'),
        ]);

        $this->get(route($slug))
            ->assertOk()
            ->assertSee('Last updated 3 March 2027');
    }

    #[Test]
    #[DataProvider('policies')]
    public function the_typed_date_and_its_reading_time_are_gone(string $slug): void
    {
        $this->seed(PageSeeder::class);

        $this->get(route($slug))
            ->assertOk()
            ->assertDontSee('9 September 2026')
            ->assertDontSee('minutes to read');
    }

    #[Test]
    public function editing_a_policy_moves_the_date_and_the_sitemap_together(): void
    {
        $this->seed(PageSeeder::class);

        $this->travelTo(Carbon::parse('2027-03-03 09:00:00'));

        $page = Page::query()->where('slug', 'privacy')->firstOrFail();
        $page->update(['content' => ['head' => ['title' => 'Privacy Policy']] + $page->content]);

        $this->get(route('privacy'))
            ->assertOk()
            ->assertSee('Last updated 3 March 2027');

        // The page and the sitemap now read the same column, which is the
        // whole point: before this they could disagree by months.
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('2027-03-03T09:00:00', false);
    }

    #[Test]
    #[DataProvider('policies')]
    public function the_date_is_not_in_the_editable_copy(string $slug): void
    {
        $this->seed(PageSeeder::class);

        // Both layers. Config is the floor and the stored row is layered over
        // it, so a date left in either one is a date an editor can type into.
        // The admin form is built from the stored content, so a key here is a
        // box in the panel whatever the view does with it.
        $config = config('frith-content.'.str_replace('-', '_', $slug), []);
        $stored = json_decode(DB::table('pages')->where('slug', $slug)->value('content'), true);

        foreach (['config' => $config, 'stored row' => $stored] as $where => $content) {
            foreach (self::strings($content) as $path => $value) {
                $this->assertStringNotContainsString(
                    'Last updated',
                    $value,
                    "{$slug} still has a date in its {$where} at {$path}",
                );
            }
        }
    }

    #[Test]
    public function a_page_with_no_row_yet_prints_no_date_at_all(): void
    {
        // Nothing seeded. A fresh install has no row to read, and an invented
        // date on a policy is worse than none.
        $this->assertNull(PolicyPages::lastUpdated('privacy'));

        $this->get(route('privacy'))
            ->assertOk()
            ->assertDontSee('Last updated');
    }

    #[Test]
    public function the_migration_takes_the_shipped_line_out_and_leaves_the_date_alone(): void
    {
        $seeded = Carbon::parse('2026-09-09 10:00:00');

        $this->storePrivacy('Last updated 9 September 2026 · about 7 minutes to read', $seeded);

        $this->runTheMigration();

        $row = DB::table('pages')->where('slug', 'privacy')->first();
        $content = json_decode($row->content, true);

        $this->assertArrayNotHasKey('doc_meta', $content['head']);
        $this->assertSame('Privacy Policy', $content['head']['title'], 'the rest of the head was disturbed');
        $this->assertSame(
            $seeded->toDateTimeString(),
            Carbon::parse($row->updated_at)->toDateTimeString(),
            'the migration moved updated_at, which is now the date the page prints',
        );
    }

    #[Test]
    public function the_migration_leaves_a_line_an_editor_rewrote(): void
    {
        $this->storePrivacy('In force since 1 October 2026', Carbon::parse('2026-10-01 10:00:00'));

        $this->runTheMigration();

        $content = json_decode(DB::table('pages')->where('slug', 'privacy')->value('content'), true);

        $this->assertSame('In force since 1 October 2026', $content['head']['doc_meta']);
    }

    /** A privacy row as it shipped, with whatever date line is being tested. */
    private function storePrivacy(string $line, Carbon $at): void
    {
        DB::table('pages')->insert([
            'slug' => 'privacy',
            'name' => 'Privacy Policy',
            'content' => json_encode([
                'head' => [
                    'eyebrow' => 'Legal',
                    'title' => 'Privacy Policy',
                    'doc_meta' => $line,
                ],
            ]),
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }

    /**
     * The migration has already run against an empty table, so it is run again
     * here against a row that looks like the one on the server.
     */
    private function runTheMigration(): void
    {
        $migration = require database_path(
            'migrations/2026_11_12_130000_take_the_typed_date_off_the_policy_pages.php',
        );

        $migration->up();
    }

    /**
     * Every string in a content tree, keyed by its dot path.
     *
     * @return array<string, string>
     */
    private static function strings(mixed $content, string $path = ''): array
    {
        if (is_string($content)) {
            return [$path => $content];
        }

        if (! is_array($content)) {
            return [];
        }

        $found = [];

        foreach ($content as $key => $value) {
            $found += self::strings($value, $path === '' ? (string) $key : "{$path}.{$key}");
        }

        return $found;
    }
}
