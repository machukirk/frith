<?php

namespace Tests\Feature;

use App\Models\Journey;
use App\Models\Registration;
use Database\Seeders\JourneySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class JourneyTest extends TestCase
{
    use RefreshDatabase;

    private function family(string $email = 'sarah@example.com'): Registration
    {
        return Registration::query()->create([
            'email' => $email,
            'first_name' => 'Sarah',
            'postcode_outcode' => 'TN13',
            'email_verified_at' => now(),
            'consent_version' => config('frith.consent.version'),
            'consent_text' => config('frith.consent.text'),
            'consented_at' => now(),
        ]);
    }

    #[Test]
    public function the_nine_journeys_are_seeded_from_config_without_overwriting_an_edit(): void
    {
        $this->seed(JourneySeeder::class);

        $this->assertSame(
            array_keys(config('frith-journeys')),
            Journey::query()->offered()->pluck('slug')->all(),
            'the slugs and their order should come straight from config',
        );

        Journey::query()->where('slug', 'sleep')->sole()->update(['title' => 'Sleep']);
        $this->seed(JourneySeeder::class);

        $this->assertSame('Sleep', Journey::query()->where('slug', 'sleep')->sole()->title);
        $this->assertCount(9, Journey::query()->get(), 'seeding twice must not duplicate them');
    }

    #[Test]
    public function the_list_is_only_for_families_who_are_logged_in(): void
    {
        // Everything here is about who can see whom, so none of it is public.
        $this->get(route('journeys'))->assertRedirect(route('login'));

        $this->post(route('journeys.join', 'sleep'))->assertRedirect(route('login'));
    }

    #[Test]
    public function it_lists_every_journey_with_what_it_is_called(): void
    {
        $this->seed(JourneySeeder::class);
        $this->actingAs($this->family(), 'founder');

        $response = $this->get(route('journeys'))->assertOk();

        foreach (Journey::query()->offered()->get() as $journey) {
            $response->assertSee($journey->title);
        }

        $response->assertSee('All 9');
    }

    #[Test]
    public function joining_is_a_post_and_can_be_undone_from_the_same_screen(): void
    {
        $this->seed(JourneySeeder::class);
        $family = $this->family();
        $journey = Journey::query()->where('slug', 'sleep')->sole();

        $this->actingAs($family, 'founder')
            ->post(route('journeys.join', $journey))
            ->assertRedirect();

        $this->assertTrue($family->fresh()->journeys->contains($journey));

        // The row becomes the way out of it.
        $this->actingAs($family, 'founder')
            ->get(route('journeys'))
            ->assertOk()
            ->assertSee('Leave '.$journey->title);

        $this->actingAs($family, 'founder')
            ->post(route('journeys.leave', $journey));

        $this->assertFalse($family->fresh()->journeys->contains($journey));
    }

    #[Test]
    public function joining_twice_does_not_make_two_memberships(): void
    {
        // A double tap, or a retried request on a bad connection.
        $this->seed(JourneySeeder::class);
        $family = $this->family();
        $journey = Journey::query()->where('slug', 'ehcp')->sole();

        $this->actingAs($family, 'founder')->post(route('journeys.join', $journey));
        $this->actingAs($family, 'founder')->post(route('journeys.join', $journey));

        $this->assertDatabaseCount('journey_registration', 1);
    }

    #[Test]
    public function the_count_counts_the_family_reading_it(): void
    {
        $this->seed(JourneySeeder::class);
        $journey = Journey::query()->where('slug', 'dads')->sole();

        $mine = $this->family();
        $journey->members()->attach([$mine->id, $this->family('b@example.com')->id, $this->family('c@example.com')->id]);

        // Three in it, and one of them is reading the page.
        $this->actingAs($mine, 'founder')
            ->get(route('journeys'))
            ->assertOk()
            ->assertSee('You and 2 others are in this journey');

        // Somebody not in it is told the plain number.
        $this->actingAs($this->family('d@example.com'), 'founder')
            ->get(route('journeys'))
            ->assertOk()
            ->assertSee('3 families are in this journey');
    }

    #[Test]
    public function searching_narrows_the_list_and_offers_the_way_back(): void
    {
        $this->seed(JourneySeeder::class);
        $this->actingAs($this->family(), 'founder');

        $this->get(route('journeys', ['q' => 'school']))
            ->assertOk()
            ->assertSee('School refusal')
            ->assertDontSee('Sleep challenges')
            ->assertSee('Show all 9');

        $this->get(route('journeys', ['q' => 'xyzzy']))
            ->assertOk()
            ->assertSee('Nothing matches');
    }

    #[Test]
    public function an_archived_journey_is_off_the_list_and_cannot_be_joined(): void
    {
        // Archived rather than deleted, so the families already in it keep a
        // membership that still means something.
        $this->seed(JourneySeeder::class);
        $family = $this->family();
        $journey = Journey::query()->where('slug', 'tribunal')->sole();
        $journey->members()->attach($family->id);
        $journey->update(['archived_at' => now()]);

        $this->actingAs($family, 'founder')
            ->get(route('journeys'))
            ->assertOk()
            ->assertDontSee($journey->title);

        $this->actingAs($this->family('e@example.com'), 'founder')
            ->post(route('journeys.join', $journey))
            ->assertNotFound();

        $this->assertTrue($journey->fresh()->members->contains($family));
    }
}
