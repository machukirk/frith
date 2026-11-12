<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Models\Page;
use App\Models\Registration;
use App\Models\User;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The three content files that were stranded.
 *
 * config/content is only the floor. A file with no row in the pages table is
 * copy that nobody but a developer can change, and all three of these were in
 * that state: the four log-in screens, the screen at the end of registration,
 * and the four lines at the bottom of every page.
 *
 * The footer was a step further out again. Its Blade read config() directly,
 * so a row would not have helped it even if somebody had made one.
 */
class EditableCopyTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{0: string}> */
    public static function stranded(): array
    {
        return collect(PageSeeder::WITHOUT_A_ROUTE)
            ->mapWithKeys(fn (string $slug) => [$slug => [$slug]])
            ->all();
    }

    private function editing(string $slug): Page
    {
        $this->seed(PageSeeder::class);

        return Page::query()->where('slug', $slug)->sole();
    }

    private function family(array $overrides = []): Registration
    {
        return Registration::query()->create(array_merge([
            'email' => 'sarah@example.com',
            'first_name' => 'Sarah',
            'display_name' => 'Sarah',
            'postcode_outcode' => 'SS9',
            'email_verified_at' => now(),
            'completed_at' => now(),
            'consent_version' => config('frith.consent.version'),
            'consent_text' => config('frith.consent.text'),
            'consented_at' => now(),
        ], $overrides));
    }

    #[Test]
    #[DataProvider('stranded')]
    public function the_migration_gives_it_a_row_without_anybody_seeding(string $slug): void
    {
        // No seeder call here, deliberately. A deploy runs migrations and
        // seeds only when somebody asks it to, so on staging and production
        // the migration is the only thing that ever reaches these rows.
        $row = Page::query()->where('slug', $slug)->sole();

        $this->assertNotEmpty($row->content, "{$slug} has a row with nothing in it");
        $this->assertSame(
            config('frith-content.'.$slug),
            $row->content,
            "{$slug}'s row does not match the file it was taken from",
        );
    }

    #[Test]
    public function the_migration_leaves_a_row_somebody_has_already_written_in_alone(): void
    {
        $page = Page::query()->where('slug', 'footer')->sole();
        $page->update(['content' => ['tagline' => 'Written by an editor'] + $page->content]);

        // Running it again is what a second deploy does, and what anybody
        // re-running migrations by hand does.
        $this->migration()->up();

        $this->assertSame('Written by an editor', $page->refresh()->content['tagline']);
    }

    #[Test]
    #[DataProvider('stranded')]
    public function the_admin_offers_it(string $slug): void
    {
        // PagesTable filters the list to PageSeeder::pages(). Until these
        // three were in that array, a row inserted by hand would still not
        // have appeared on any screen.
        $this->seed(PageSeeder::class);
        $this->actingAs(User::factory()->create(['role' => UserRole::Editor]));

        Livewire::test(ListPages::class)
            ->assertOk()
            ->assertCanSeeTableRecords(Page::query()->where('slug', $slug)->get());
    }

    #[Test]
    public function an_edit_to_the_log_in_copy_reaches_all_four_screens(): void
    {
        $page = $this->editing('auth');
        $content = $page->content;

        $content['login']['title'] = 'Log in, rewritten';
        $content['forgot']['title'] = 'Forgot, rewritten';
        $content['link_sent']['title'] = 'Link sent, rewritten';
        $content['verified']['title'] = 'Verified, rewritten';

        $page->update(['content' => $content]);

        $this->get(route('login'))->assertOk()->assertSee('Log in, rewritten');
        $this->get(route('forgot'))->assertOk()->assertSee('Forgot, rewritten');
        $this->get(route('link-sent'))->assertOk()->assertSee('Link sent, rewritten');

        $this->actingAs($this->family(), 'founder')
            ->get(route('verified'))
            ->assertOk()
            ->assertSee('Verified, rewritten');
    }

    #[Test]
    public function an_edit_to_the_end_of_registration_copy_reaches_the_screen(): void
    {
        $page = $this->editing('join');
        $content = $page->content;
        $content['welcome']['journeys_title'] = 'Pick your Journeys, rewritten';
        $page->update(['content' => $content]);

        $this->withSession(['registration.id' => $this->family()->id])
            ->get(route('register.welcome'))
            ->assertOk()
            ->assertSee('Pick your Journeys, rewritten');
    }

    #[Test]
    public function an_edit_to_the_footer_reaches_the_foot_of_every_page(): void
    {
        // The safeguarding line is the one that matters most here: it promises
        // a reply within 24 hours, on every page of the site, and it used to
        // take a deploy to change that number.
        $page = $this->editing('footer');
        $page->update(['content' => [
            ...$page->content,
            'safeguarding' => 'A person reads every safeguarding concern, usually within a day.',
        ]]);

        foreach ([route('home'), route('about'), route('terms')] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('A person reads every safeguarding concern, usually within a day.');
        }
    }

    /**
     * The migration object itself, so a test can run it a second time.
     *
     * Required rather than resolved by class name: migrations are anonymous
     * classes, which is what stops two of them colliding.
     */
    private function migration(): object
    {
        return require database_path(
            'migrations/2026_11_12_110000_give_the_login_join_and_footer_copy_a_row.php',
        );
    }
}
