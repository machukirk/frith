<?php

namespace Tests\Feature;

use App\Models\Registration;
use App\Support\Taxonomy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /** @param array<string, mixed> $overrides */
    private function openWith(array $overrides = []): Registration
    {
        $this->post(route('register.step.store', 'you'), array_merge([
            'first_name' => 'Sam',
            'email' => 'sam@example.com',
        ], $overrides));

        return Registration::query()->latest('id')->first();
    }

    #[Test]
    public function a_last_name_is_never_asked_for_or_stored(): void
    {
        // Decided against collecting it. The column is gone, so anything sent
        // for it has nowhere to land — this guards against it creeping back in
        // via a copied form or a stray fill().
        $this->get(route('register.step', 'you'))
            ->assertOk()
            ->assertDontSee('name="last_name"', false)
            ->assertDontSee('Last name');

        $this->post(route('register.step.store', 'you'), [
            'first_name' => 'Sam',
            'last_name' => 'Okafor',
            'email' => 'sam@example.com',
        ])->assertSessionHasNoErrors();

        $registration = Registration::sole();

        $this->assertSame('Sam', $registration->first_name);
        $this->assertFalse(
            Schema::hasColumn('registrations', 'last_name'),
            'the last_name column should have been dropped',
        );
        $this->assertArrayNotHasKey('last_name', $registration->getAttributes());
    }

    #[Test]
    public function the_homepage_sends_people_to_the_registration(): void
    {
        $this->get(route('coming-soon'))
            ->assertOk()
            ->assertSee(route('register.start'), false)
            ->assertSee(config('frith.coming_soon.form.button'));
    }

    #[Test]
    public function the_first_screen_registers_them_before_anything_else_is_asked(): void
    {
        // The whole design rests on this: answer one screen, and you are
        // registered whatever happens next.
        $this->post(route('register.step.store', 'you'), [
            'first_name' => 'Sam',
            'email' => '  SAM@Example.com ',
        ])->assertRedirect(route('register.step', 'location'));

        $registration = Registration::sole();

        $this->assertSame('sam@example.com', $registration->email, 'the address should be normalised');
        $this->assertSame('Sam', $registration->first_name);
        $this->assertSame(1, $registration->founder_number);
        $this->assertNotNull($registration->consented_at);
        $this->assertSame(config('frith.consent.version'), $registration->consent_version);
    }

    #[Test]
    public function dropping_off_half_way_still_leaves_a_registration(): void
    {
        $this->openWith();
        $this->post(route('register.step.store', 'location'), ['postcode_outcode' => 'ss9']);

        // …and then they close the tab.
        $registration = Registration::sole();

        $this->assertSame('SS9', $registration->postcode_outcode);
        $this->assertSame(3, $registration->furthest_step);
        $this->assertNull($registration->completed_at, 'they have not finished, but they are registered');
    }

    #[Test]
    public function founder_numbers_are_sequential_and_never_reused(): void
    {
        $first = $this->openWith(['email' => 'one@example.com']);
        $this->flushSession();
        $second = $this->openWith(['email' => 'two@example.com']);

        $this->assertSame(1, $first->founder_number);
        $this->assertSame(2, $second->founder_number);

        // Founder 1 leaves. Founder 3 must not inherit their number, or "#2"
        // would refer to two different families over time.
        $first->delete();
        $this->flushSession();
        $third = $this->openWith(['email' => 'three@example.com']);

        $this->assertSame(3, $third->founder_number);
    }

    #[Test]
    public function the_postcode_is_only_ever_the_outcode(): void
    {
        $this->openWith();

        $this->post(route('register.step.store', 'location'), ['postcode_outcode' => 'SS9 1AB'])
            ->assertSessionHasErrors('postcode_outcode');

        $this->post(route('register.step.store', 'location'), ['postcode_outcode' => 'EC1A'])
            ->assertSessionHasNoErrors();

        $this->assertSame('EC1A', Registration::sole()->postcode_outcode);
    }

    #[Test]
    public function children_are_stored_in_order_with_month_and_year_only(): void
    {
        $this->openWith();

        $this->post(route('register.step.store', 'children'), [
            'action' => 'continue',
            'children' => [
                ['birth_month' => 3, 'birth_year' => 2017],
                ['birth_month' => 11, 'birth_year' => 2020],
            ],
        ])->assertRedirect(route('register.step', 'support'));

        $children = Registration::sole()->children;

        $this->assertCount(2, $children);
        $this->assertSame([3, 11], $children->pluck('birth_month')->all());
        $this->assertSame([0, 1], $children->pluck('position')->all());
    }

    #[Test]
    public function removing_a_child_actually_removes_them(): void
    {
        // Children are replaced wholesale on save, because the form is the
        // whole household each time. A diff would leave the removed one behind.
        $this->openWith();

        $this->post(route('register.step.store', 'children'), [
            'action' => 'continue',
            'children' => [
                ['birth_month' => 3, 'birth_year' => 2017],
                ['birth_month' => 11, 'birth_year' => 2020],
            ],
        ]);

        $this->post(route('register.step.store', 'children'), [
            'action' => 'continue',
            'children' => [['birth_month' => 3, 'birth_year' => 2017]],
        ]);

        $this->assertCount(1, Registration::sole()->children);
    }

    #[Test]
    public function adding_a_child_row_works_without_javascript(): void
    {
        $this->openWith();

        $this->post(route('register.step.store', 'children'), [
            'action' => 'add',
            'children' => [['birth_month' => 3, 'birth_year' => 2017]],
        ])->assertRedirect(route('register.step', 'children'));

        // The extra row is waiting on the next render, and nothing was saved
        // yet because they have not pressed continue.
        $this->get(route('register.step', 'children'))
            ->assertOk()
            ->assertSee('Child 2');
    }

    #[Test]
    public function an_unknown_support_area_is_rejected(): void
    {
        // Slugs are matching data. One that resolves to nothing is a family
        // who will never be matched on it.
        $this->openWith();

        $this->post(route('register.step.store', 'support'), [
            'support_areas' => ['learning-education', 'not-a-real-area'],
        ])->assertSessionHasErrors('support_areas.1');
    }

    #[Test]
    public function finishing_section_one_completes_the_registration(): void
    {
        $this->openWith();

        $this->post(route('register.step.store', 'support'), [
            'support_areas' => ['learning-education', 'identity-belonging'],
        ])->assertRedirect(route('register.experiences'));

        $registration = Registration::sole();

        $this->assertNotNull($registration->completed_at);
        $this->assertSame(['learning-education', 'identity-belonging'], $registration->support_areas);
    }

    #[Test]
    public function section_two_only_offers_the_areas_they_chose(): void
    {
        $this->openWith();
        $this->post(route('register.step.store', 'support'), ['support_areas' => ['identity-belonging']]);

        $this->get(route('register.experiences'))
            ->assertRedirect(route('register.experiences.show', 'identity-belonging'));

        // An area they did not pick is not their form to fill in.
        $this->get(route('register.experiences.show', 'health-wellbeing'))
            ->assertRedirect(route('register.experiences'));
    }

    #[Test]
    public function section_two_is_ordered_by_the_taxonomy_not_by_submission(): void
    {
        $this->openWith();
        $this->post(route('register.step.store', 'support'), [
            'support_areas' => ['identity-belonging', 'learning-education'],
        ]);

        // Submitted last-first, but the first screen is still the first area.
        $this->get(route('register.experiences'))
            ->assertRedirect(route('register.experiences.show', 'learning-education'));
    }

    #[Test]
    public function detailed_experiences_are_saved_against_their_category(): void
    {
        $this->openWith();
        $this->post(route('register.step.store', 'support'), ['support_areas' => ['learning-education']]);

        $this->post(route('register.experiences.store', 'learning-education'), [
            'action' => 'continue',
            'items' => ['school-attendance', 'education-options'],
        ])->assertRedirect(route('register.done'));

        $experiences = Registration::sole()->experiences;

        $this->assertCount(2, $experiences);
        $this->assertSame(['learning-education'], $experiences->pluck('category')->unique()->all());
    }

    #[Test]
    public function revisiting_one_area_does_not_wipe_another(): void
    {
        $this->openWith();
        $this->post(route('register.step.store', 'support'), [
            'support_areas' => ['learning-education', 'identity-belonging'],
        ]);

        $this->post(route('register.experiences.store', 'learning-education'), ['action' => 'continue', 'items' => ['school-attendance']]);
        $this->post(route('register.experiences.store', 'identity-belonging'), ['action' => 'continue', 'items' => ['masking']]);

        // Going back and changing the first one leaves the second alone.
        $this->post(route('register.experiences.store', 'learning-education'), ['action' => 'continue', 'items' => ['homework-exams']]);

        $experiences = Registration::sole()->fresh()->experiences;

        $this->assertCount(2, $experiences);
        $this->assertTrue($experiences->contains(fn ($e) => $e->item === 'masking'));
        $this->assertTrue($experiences->contains(fn ($e) => $e->item === 'homework-exams'));
    }

    #[Test]
    public function skipping_the_rest_goes_straight_to_the_end(): void
    {
        // The source spreadsheet asks for this explicitly, to limit form fatigue.
        $this->openWith();
        $this->post(route('register.step.store', 'support'), [
            'support_areas' => ['learning-education', 'identity-belonging'],
        ]);

        $this->post(route('register.experiences.store', 'learning-education'), ['action' => 'skip-all'])
            ->assertRedirect(route('register.done'));

        $this->get(route('register.done'))->assertOk()->assertSee('Frith Founder');
        $this->assertNotNull(Registration::sole()->completed_at);
    }

    #[Test]
    public function you_cannot_skip_ahead_without_starting(): void
    {
        $this->get(route('register.step', 'children'))
            ->assertRedirect(route('register.step', 'you'));

        $this->get(route('register.done'))
            ->assertRedirect(route('register.start'));
    }

    #[Test]
    public function coming_back_picks_up_an_unfinished_registration_rather_than_duplicating_it(): void
    {
        $this->openWith(['email' => 'sam@example.com']);
        $this->post(route('register.step.store', 'location'), ['postcode_outcode' => 'SS9']);

        $this->flushSession();
        $this->openWith(['email' => 'sam@example.com', 'first_name' => 'Samuel']);

        $this->assertDatabaseCount('registrations', 1);
        $this->assertSame('Samuel', Registration::sole()->first_name);
    }

    #[Test]
    public function somebody_elses_verified_registration_cannot_be_overwritten(): void
    {
        // Anyone can type anyone's address. Without this, the form is a way to
        // rewrite another family's answers — and, by behaving differently, a
        // way to find out they are registered at all.
        $existing = Registration::query()->create([
            'email' => 'priya@example.com',
            'first_name' => 'Priya',
            'postcode_outcode' => 'M1',
            'founder_number' => 1,
            'email_verified_at' => now(),
            'consent_version' => config('frith.consent.version'),
            'consent_text' => config('frith.consent.text'),
            'consented_at' => now(),
        ]);

        $this->post(route('register.step.store', 'you'), [
            'first_name' => 'Impostor',
            'email' => 'priya@example.com',
        ])->assertRedirect(route('register.step', 'location'));

        $this->post(route('register.step.store', 'location'), ['postcode_outcode' => 'ZZ9']);

        $existing->refresh();

        $this->assertSame('Priya', $existing->first_name);
        $this->assertSame('M1', $existing->postcode_outcode);
        $this->assertDatabaseCount('registrations', 1);
    }

    #[Test]
    public function every_taxonomy_slug_in_the_form_is_one_the_validator_accepts(): void
    {
        // Guards against a label being added to the config without a slug, or
        // a category being renamed and the form quietly rejecting everything.
        $this->openWith();

        foreach (Taxonomy::categorySlugs() as $category) {
            $this->post(route('register.step.store', 'support'), ['support_areas' => [$category]])
                ->assertSessionHasNoErrors();
        }

        $this->post(route('register.step.store', 'support'), [
            'support_areas' => Taxonomy::categorySlugs(),
        ]);

        foreach (Taxonomy::categorySlugs() as $category) {
            $this->post(route('register.experiences.store', $category), [
                'action' => 'continue',
                'items' => Taxonomy::itemSlugs($category),
            ])->assertSessionHasNoErrors();
        }

        $this->assertCount(63, Registration::sole()->fresh()->experiences);
    }
}
