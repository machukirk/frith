<?php

namespace Tests\Feature;

use App\Models\Registration;
use App\Support\RegistrationFlow;
use Database\Seeders\FormSeeder;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * "Pick up where you left off."
 *
 * Offered on the confirm screen and in the Founder email to a family who
 * stopped part way. It sent them to screen one, where the form looked their
 * address up, found a verified account and put them into shadow mode: every
 * screen accepted their answers, none of them saved, and the end told them
 * they were now a First Frith Family. The guard against overwriting somebody
 * else's answers was being applied to that family themselves.
 */
class ResumeRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function family(array $overrides = []): Registration
    {
        return Registration::query()->create(array_merge([
            'email' => 'sarah@example.com',
            'first_name' => 'Sarah',
            'postcode_outcode' => 'SS9',
            'email_verified_at' => now(),
            'furthest_step' => 4,
            'consent_version' => config('frith.consent.version'),
            'consent_text' => config('frith.consent.text'),
            'consented_at' => now(),
        ], $overrides));
    }

    #[Test]
    public function a_family_comes_back_to_the_screen_they_stopped_on(): void
    {
        $family = $this->family(['furthest_step' => 4]);

        $this->actingAs($family, 'founder')
            ->get(route('register.start'))
            ->assertRedirect(route('register.step', 'areas'));
    }

    #[Test]
    public function resuming_puts_them_on_their_own_registration_so_answers_save(): void
    {
        // The bug: without this they were shadowed, and every screen accepted
        // an answer that went nowhere.
        $this->seed(FormSeeder::class);
        $family = $this->family(['furthest_step' => 3]);

        $this->actingAs($family, 'founder')->get(route('register.start'));

        $this->actingAs($family, 'founder')
            ->post(route('register.step.store', 'hopes'), ['hopes' => ['local-families']])
            ->assertRedirect(route('register.step', 'areas'));

        $this->assertSame(['local-families'], $family->fresh()->hopes);
    }

    #[Test]
    public function a_visitor_who_is_not_signed_in_still_starts_at_the_beginning(): void
    {
        $this->get(route('register.start'))
            ->assertRedirect(route('register.step', RegistrationFlow::STEPS[0]));
    }

    #[Test]
    public function a_family_who_has_finished_is_sent_to_their_village(): void
    {
        // There is nothing to pick up, and asking the questions again would
        // overwrite answers they have already given.
        $family = $this->family([
            'display_name' => 'Sarah',
            'completed_at' => now(),
            'furthest_step' => 7,
        ]);

        $this->actingAs($family, 'founder')
            ->get(route('register.start'))
            ->assertRedirect(route('village'));
    }

    #[Test]
    public function a_number_past_the_end_of_the_form_lands_on_the_last_screen(): void
    {
        // The form has lost a screen before now. A number written against the
        // old one must not index off the end of the new one.
        $this->assertSame('interests', RegistrationFlow::resumeAt(99));
        $this->assertSame('you', RegistrationFlow::resumeAt(0));
        $this->assertSame('you', RegistrationFlow::resumeAt(-3));
        $this->assertSame('family', RegistrationFlow::resumeAt(2));
    }

    #[Test]
    public function the_confirm_screen_offers_the_link_only_to_somebody_unfinished(): void
    {
        $this->seed(PageSeeder::class);

        $unfinished = $this->family(['email' => 'unfinished@example.com']);
        $done = $this->family([
            'email' => 'done@example.com',
            'display_name' => 'Done',
            'completed_at' => now(),
        ]);

        $this->actingAs($unfinished, 'founder')
            ->get(route('verified'))
            ->assertOk()
            ->assertSee('Pick up where you left off');

        $this->actingAs($done, 'founder')
            ->get(route('verified'))
            ->assertOk()
            ->assertDontSee('Pick up where you left off');
    }
}
