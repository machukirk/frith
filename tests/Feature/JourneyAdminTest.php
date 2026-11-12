<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Journeys\JourneyResource;
use App\Filament\Resources\Journeys\Pages\EditJourney;
use App\Filament\Resources\Journeys\Pages\ListJourneys;
use App\Models\Journey;
use App\Models\Registration;
use App\Models\User;
use App\Support\JourneyBoard;
use Database\Seeders\JourneySeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class JourneyAdminTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::factory()->create(['role' => UserRole::Owner]);
    }

    private function editor(): User
    {
        return User::factory()->create(['role' => UserRole::Editor]);
    }

    private function family(string $name): Registration
    {
        return Registration::query()->create([
            'email' => Str::slug($name).'@example.com',
            'first_name' => $name,
            'display_name' => $name,
            'postcode_outcode' => 'SS9',
            'email_verified_at' => now(),
            'completed_at' => now(),
            'consent_version' => config('frith.consent.version'),
            'consent_text' => config('frith.consent.text'),
            'consented_at' => now(),
        ]);
    }

    #[Test]
    public function an_editor_can_reword_a_journey(): void
    {
        // These words are content, so the content editor owns them.
        $this->seed(JourneySeeder::class);
        $journey = Journey::query()->where('slug', 'sleep')->sole();

        $this->actingAs($this->editor());

        Livewire::test(EditJourney::class, ['record' => $journey->slug])
            ->fillForm([
                'title' => 'Sleep, and the lack of it',
                'blurb' => 'Bedtimes that take hours, and nights that end at four.',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $journey->refresh();

        $this->assertSame('Sleep, and the lack of it', $journey->title);
        $this->assertSame('sleep', $journey->slug, 'the address must not move');
    }

    #[Test]
    public function the_address_cannot_be_changed_by_posting_it(): void
    {
        // It is in the link families may have saved and in the record of who
        // joined, so moving it detaches everybody in the Journey.
        $this->seed(JourneySeeder::class);
        $journey = Journey::query()->where('slug', 'sleep')->sole();

        $this->actingAs($this->owner());

        Livewire::test(EditJourney::class, ['record' => $journey->slug])
            ->fillForm(['title' => 'Renamed', 'slug' => 'something-else'])
            ->call('save');

        $this->assertSame('sleep', $journey->fresh()->slug);
    }

    #[Test]
    public function a_journey_can_never_be_created_or_deleted_from_the_panel(): void
    {
        $this->seed(JourneySeeder::class);
        $journey = Journey::query()->first();

        $this->actingAs($this->owner());

        $this->assertFalse(JourneyResource::canCreate());
        $this->assertFalse(JourneyResource::canDelete($journey));
        $this->assertFalse(JourneyResource::canDeleteAny());
    }

    #[Test]
    public function only_an_owner_can_archive_one(): void
    {
        // Archiving changes what families can do, not just what they read.
        $this->seed(JourneySeeder::class);
        $journey = Journey::query()->where('slug', 'sleep')->sole();

        $this->actingAs($this->editor());
        Livewire::test(ListJourneys::class)
            ->assertActionHidden(TestAction::make('archive')->table($journey));

        $this->actingAs($this->owner());
        Livewire::test(ListJourneys::class)
            ->assertActionVisible(TestAction::make('archive')->table($journey));
    }

    #[Test]
    public function archiving_keeps_the_families_who_already_joined(): void
    {
        $this->seed(JourneySeeder::class);
        $journey = Journey::query()->where('slug', 'sleep')->sole();
        $family = $this->family('Fun House');
        $journey->members()->attach($family->id);

        $this->actingAs($this->owner());

        Livewire::test(ListJourneys::class)
            ->callAction(TestAction::make('archive')->table($journey));

        $journey->refresh();

        $this->assertTrue($journey->isArchived());
        $this->assertSame(1, $journey->members()->count(), 'membership must survive archiving');
        $this->assertFalse(Journey::query()->offered()->where('slug', 'sleep')->exists());
    }

    #[Test]
    public function the_home_page_shows_the_live_journeys_not_a_typed_list(): void
    {
        $this->seed(JourneySeeder::class);
        $sleep = Journey::query()->where('slug', 'sleep')->sole();
        $sleep->members()->attach($this->family('One')->id);
        $sleep->members()->attach($this->family('Two')->id);

        $board = JourneyBoard::forHome();

        $this->assertCount(9, $board);
        $this->assertSame(2, $board->firstWhere('slug', 'sleep')['families']);

        // Renaming it in the admin changes the front page.
        $sleep->forceFill(['title' => 'Sleep, and the lack of it'])->save();

        $this->assertSame(
            'Sleep, and the lack of it',
            JourneyBoard::forHome()->firstWhere('slug', 'sleep')['title'],
        );
    }

    #[Test]
    public function an_archived_journey_comes_off_the_home_page(): void
    {
        $this->seed(JourneySeeder::class);
        Journey::query()->where('slug', 'sleep')->update(['archived_at' => now()]);

        $board = JourneyBoard::forHome();

        $this->assertCount(8, $board);
        $this->assertNull($board->firstWhere('slug', 'sleep'));
        $this->assertSame(8, JourneyBoard::count());
    }

    #[Test]
    public function a_visitor_is_sent_to_register_rather_than_into_a_login_wall(): void
    {
        // /journeys is behind the founder guard, so sending a visitor there
        // from the public home page bounces them to a login screen they have
        // no account for. That is a dead end on the page meant to persuade.
        $this->seed(JourneySeeder::class);

        foreach (JourneyBoard::forHome() as $card) {
            $this->assertSame(route('register.start'), $card['url']);
        }
    }

    #[Test]
    public function a_family_goes_straight_into_a_journey_they_are_already_in(): void
    {
        $this->seed(JourneySeeder::class);
        $family = $this->family('Fun House');
        $sleep = Journey::query()->where('slug', 'sleep')->sole();
        $sleep->members()->attach($family->id);

        $this->actingAs($family->fresh(), 'founder');

        $board = JourneyBoard::forHome();

        $this->assertSame(route('journeys.show', $sleep), $board->firstWhere('slug', 'sleep')['url']);

        // And to the directory for one they are not in, where joining is a
        // deliberate act rather than a link from the front page.
        $this->assertSame(route('journeys'), $board->firstWhere('slug', 'ehcp')['url']);
    }

    #[Test]
    public function the_registration_welcome_panel_names_real_journeys(): void
    {
        // This panel used to read a typed-out list from the home page's copy.
        // When that list was deleted it fell through to an empty default and
        // rendered a heading, a button, and nothing between them. Nothing
        // covered it, which is why it broke in silence.
        $this->seed(JourneySeeder::class);

        $family = $this->family('Fun House');
        $this->withSession(['registration.id' => $family->id]);

        $response = $this->get(route('register.welcome'))->assertOk();

        $titles = Journey::query()->offered()->take(3)->pluck('title');

        $this->assertCount(3, $titles);

        foreach ($titles as $title) {
            $response->assertSee($title, false);
        }

        // And the count beside the button is live too.
        $response->assertSee('Browse all nine Journeys');
    }

    #[Test]
    public function the_welcome_panel_follows_the_table_when_a_journey_is_archived(): void
    {
        $this->seed(JourneySeeder::class);
        Journey::query()->where('slug', 'autism-assessment')->update(['archived_at' => now()]);

        $family = $this->family('Fun House');
        $this->withSession(['registration.id' => $family->id]);

        $this->get(route('register.welcome'))
            ->assertOk()
            ->assertDontSee('Waiting for an autism assessment')
            ->assertSee('Browse all eight Journeys');
    }
}
