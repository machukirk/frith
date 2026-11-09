<?php

namespace Tests\Feature;

use App\Models\Journey;
use App\Models\Registration;
use Database\Seeders\JourneySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VillageTest extends TestCase
{
    use RefreshDatabase;

    private function family(array $overrides = []): Registration
    {
        return Registration::query()->create(array_merge([
            'email' => 'sarah@example.com',
            'first_name' => 'Sarah',
            'postcode_outcode' => 'TN13',
            'email_verified_at' => now(),
            'consent_version' => config('frith.consent.version'),
            'consent_text' => config('frith.consent.text'),
            'consented_at' => now(),
        ], $overrides));
    }

    #[Test]
    public function the_village_is_only_for_families_who_are_logged_in(): void
    {
        $this->get(route('village'))->assertRedirect(route('login'));
    }

    #[Test]
    public function the_first_visit_asks_what_other_families_should_call_them(): void
    {
        // Registration never asks, because the answer is no use until there
        // are other families to be called it by.
        $family = $this->family();

        $this->actingAs($family, 'founder')
            ->get(route('village'))
            ->assertRedirect(route('village.setup'));

        $this->actingAs($family, 'founder')
            ->get(route('village.setup'))
            ->assertOk()
            ->assertSee('The name other families see')
            // Prefilled with what we already have, so most people press on.
            ->assertSee('value="Sarah"', false);
    }

    #[Test]
    public function naming_themselves_lets_them_in_and_is_not_asked_again(): void
    {
        $family = $this->family();

        $this->actingAs($family, 'founder')
            ->post(route('village.setup.store'), ['display_name' => '  Fun House  '])
            ->assertRedirect(route('village'));

        $family->refresh();

        $this->assertSame('Fun House', $family->display_name, 'it should be trimmed');
        $this->assertSame('Fun House', $family->displayName());
        $this->assertTrue($family->hasProfile());

        $this->actingAs($family, 'founder')->get(route('village'))->assertOk();

        // Going back to the setup is not a way to be asked twice.
        $this->actingAs($family, 'founder')
            ->get(route('village.setup'))
            ->assertRedirect(route('village'));
    }

    #[Test]
    public function a_name_is_required_and_bounded(): void
    {
        $family = $this->family();

        $this->actingAs($family, 'founder')
            ->post(route('village.setup.store'), ['display_name' => '   '])
            ->assertSessionHasErrors('display_name');

        $this->actingAs($family, 'founder')
            ->post(route('village.setup.store'), ['display_name' => str_repeat('a', 61)])
            ->assertSessionHasErrors('display_name');

        $this->assertFalse($family->fresh()->hasProfile());
    }

    #[Test]
    public function the_display_name_falls_back_to_their_first_name(): void
    {
        // Everybody who registered before the village existed has no chosen
        // name, and nothing should render blank because of it.
        $family = $this->family();

        $this->assertSame('Sarah', $family->displayName());
        $this->assertSame('S', $family->initial());
        $this->assertSame('TN13 area', $family->area());
    }

    #[Test]
    public function it_shows_the_journeys_they_are_in_and_offers_the_rest(): void
    {
        $this->seed(JourneySeeder::class);
        $family = $this->family(['display_name' => 'Fun House']);

        $sleep = Journey::query()->where('slug', 'sleep')->sole();
        $family->journeys()->attach($sleep->id);

        $response = $this->actingAs($family, 'founder')->get(route('village'))->assertOk();

        $response->assertSee('Your village')
            ->assertSee($sleep->title)
            ->assertSee('You are the first family in this journey')
            ->assertSee('Browse all Journeys');
    }

    #[Test]
    public function a_family_in_nothing_is_told_what_joining_is_for(): void
    {
        $this->seed(JourneySeeder::class);
        $family = $this->family(['display_name' => 'Fun House']);

        $this->actingAs($family, 'founder')
            ->get(route('village'))
            ->assertOk()
            ->assertSee('You have not joined a Journey yet')
            ->assertSee('Browse all 9 Journeys');
    }

    #[Test]
    public function a_family_who_is_in_gets_the_products_own_navigation(): void
    {
        // The designs put Home, Your village, Journeys and Messages in the
        // header on every screen behind the door.
        $family = $this->family(['display_name' => 'Fun House']);

        // The visitor first: actingAs lasts for the rest of the test, so
        // checking the guest afterwards would check the family again.
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('How it works')
            ->assertDontSee('Your village');

        $this->actingAs($family, 'founder')
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Your village')
            ->assertSee('Journeys');
    }
}
