<?php

namespace Tests\Feature;

use App\Models\FormOption;
use App\Models\Registration;
use App\Support\FormDefinition;
use App\Support\RegistrationFlow;
use App\Support\Taxonomy;
use Database\Seeders\FormSeeder;
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
            'postcode_outcode' => 'SS9',
        ], $overrides));

        return Registration::query()->latest('id')->first();
    }

    /** @param array<int, string> $areas */
    private function chooseAreas(array $areas): void
    {
        $this->post(route('register.step.store', 'areas'), ['support_areas' => $areas]);
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
            'postcode_outcode' => 'SS9',
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
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('register.start'), false)
            ->assertSee(config('frith-content.home.hero.primary_cta'));
    }

    #[Test]
    public function the_first_screen_registers_them_before_anything_else_is_asked(): void
    {
        // The whole design rests on this: answer one screen, and you are
        // registered whatever happens next. Name, email and postcode are all
        // on it, because they are all "who are you" — and because it means
        // one screen is enough to be matchable.
        $this->post(route('register.step.store', 'you'), [
            'first_name' => 'Sam',
            'email' => '  SAM@Example.com ',
            'postcode_outcode' => 'ss9',
        ])->assertRedirect(route('register.step', 'family'));

        $registration = Registration::sole();

        $this->assertSame('sam@example.com', $registration->email, 'the address should be normalised');
        $this->assertSame('Sam', $registration->first_name);
        $this->assertSame('SS9', $registration->postcode_outcode, 'the outcode should be normalised');
        $this->assertSame(1, $registration->founder_number);
        $this->assertNotNull($registration->consented_at);
        $this->assertSame(config('frith.consent.version'), $registration->consent_version);
    }

    #[Test]
    public function dropping_off_after_one_screen_still_leaves_a_registration(): void
    {
        $this->openWith();

        // …and then they close the tab.
        $registration = Registration::sole();

        $this->assertSame('SS9', $registration->postcode_outcode);
        $this->assertSame(2, $registration->furthest_step);
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
    public function only_the_first_hundred_registrations_are_founders(): void
    {
        config(['frith.founders.limit' => 3]);

        foreach (['a', 'b', 'c'] as $who) {
            $this->flushSession();
            $this->openWith(['email' => "{$who}@example.com"]);
        }

        $this->assertSame(
            [1, 2, 3],
            Registration::query()->orderBy('id')->pluck('founder_number')->all(),
        );

        // The fourth registers exactly as the others did. They are simply not
        // a Founder, and nothing about the form tells them otherwise.
        $this->flushSession();
        $this->post(route('register.step.store', 'you'), [
            'first_name' => 'Dee',
            'email' => 'd@example.com',
            'postcode_outcode' => 'SS9',
        ])->assertRedirect(route('register.step', 'family'));

        $fourth = Registration::query()->where('email', 'd@example.com')->sole();

        $this->assertNull($fourth->founder_number);
        $this->assertFalse($fourth->isFounder());
        $this->assertNotNull($fourth->consented_at, 'they are still registered');

        // …and they can still finish the whole form.
        $this->post(route('register.step.store', 'interests'), ['interests' => ['animals']])
            ->assertRedirect(route('register.welcome'));

        $this->assertNotNull($fourth->fresh()->completed_at);
    }

    #[Test]
    public function a_family_leaving_does_not_free_up_a_founder_place(): void
    {
        // The cap is on numbers issued, not on families still on the list.
        // Founder 1 leaving must not let somebody else become a Founder, or
        // the hundred quietly becomes however many have ever registered.
        config(['frith.founders.limit' => 2]);

        $first = $this->openWith(['email' => 'one@example.com']);
        $this->flushSession();
        $this->openWith(['email' => 'two@example.com']);

        $first->delete();

        $this->flushSession();
        $third = $this->openWith(['email' => 'three@example.com']);

        $this->assertNull($third->founder_number, 'the two numbers are gone, not the two seats');
    }

    #[Test]
    public function the_founder_email_drops_the_badge_for_everybody_after_the_hundred(): void
    {
        // The mailable already branches on the number, so this is really a
        // guard that it keeps doing so once there are registrations without
        // one.
        config(['frith.founders.limit' => 0]);

        $registration = $this->openWith(['email' => 'late@example.com']);

        $this->assertNull($registration->founder_number);

        $html = (new \App\Mail\FounderWelcome($registration))->render();

        $this->assertStringNotContainsString('Founder #', $html);
    }

    #[Test]
    public function the_postcode_is_only_ever_the_outcode(): void
    {
        $this->post(route('register.step.store', 'you'), [
            'first_name' => 'Sam',
            'email' => 'sam@example.com',
            'postcode_outcode' => 'SS9 1AB',
        ])->assertSessionHasErrors('postcode_outcode');

        // A bad postcode must not half-register anybody.
        $this->assertDatabaseCount('registrations', 0);

        $this->post(route('register.step.store', 'you'), [
            'first_name' => 'Sam',
            'email' => 'sam@example.com',
            'postcode_outcode' => 'EC1A',
        ])->assertSessionHasNoErrors();

        $this->assertSame('EC1A', Registration::sole()->postcode_outcode);
    }

    #[Test]
    public function the_household_and_its_children_are_one_screen(): void
    {
        // Both questions are "who is at home", so they are asked together
        // rather than on a screen each.
        $this->openWith();

        $this->get(route('register.step', 'family'))
            ->assertOk()
            ->assertSee('name="family_structures[]"', false)
            ->assertSee('name="children[0][birth_month]"', false);

        $this->post(route('register.step.store', 'family'), [
            'action' => 'continue',
            'family_structures' => ['parenting-alone', 'blended'],
            'children' => [['birth_month' => 3, 'birth_year' => 2017]],
        ])->assertRedirect(route('register.step', 'hopes'));

        $registration = Registration::sole();

        $this->assertSame(['parenting-alone', 'blended'], $registration->family_structures);
        $this->assertCount(1, $registration->children);
    }

    #[Test]
    public function children_are_stored_in_order_with_month_and_year_only(): void
    {
        $this->openWith();

        $this->post(route('register.step.store', 'family'), [
            'action' => 'continue',
            'children' => [
                ['birth_month' => 3, 'birth_year' => 2017],
                ['birth_month' => 11, 'birth_year' => 2020],
            ],
        ])->assertRedirect(route('register.step', 'hopes'));

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

        $this->post(route('register.step.store', 'family'), [
            'action' => 'continue',
            'children' => [
                ['birth_month' => 3, 'birth_year' => 2017],
                ['birth_month' => 11, 'birth_year' => 2020],
            ],
        ]);

        $this->post(route('register.step.store', 'family'), [
            'action' => 'continue',
            'children' => [['birth_month' => 3, 'birth_year' => 2017]],
        ]);

        $this->assertCount(1, Registration::sole()->children);
    }

    #[Test]
    public function adding_a_child_row_works_without_javascript(): void
    {
        $this->openWith();

        $this->post(route('register.step.store', 'family'), [
            'action' => 'add',
            'children' => [['birth_month' => 3, 'birth_year' => 2017]],
        ])->assertRedirect(route('register.step', 'family'));

        // The extra row is waiting on the next render, and nothing was saved
        // yet because they have not pressed continue.
        $this->get(route('register.step', 'family'))
            ->assertOk()
            ->assertSee('Child 2');

        $this->assertCount(0, Registration::sole()->children);
    }

    #[Test]
    public function adding_a_child_row_does_not_lose_the_household_answers(): void
    {
        // Add another child is a submit, so the pills above it come back to
        // the server and have to be put back on screen. Losing them would
        // silently punish somebody for having two children.
        $this->openWith();

        $this->post(route('register.step.store', 'family'), [
            'action' => 'add',
            'family_structures' => ['foster-adoptive'],
            'children' => [['birth_month' => '', 'birth_year' => '']],
        ]);

        $html = $this->get(route('register.step', 'family'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/value="foster-adoptive"[^>]*checked/', $html);
    }

    #[Test]
    public function an_unknown_household_or_area_is_rejected(): void
    {
        // Slugs are matching data. One that resolves to nothing is a family
        // who will never be matched on it.
        $this->openWith();

        $this->post(route('register.step.store', 'family'), [
            'action' => 'continue',
            'family_structures' => ['parenting-alone', 'not-a-real-household'],
        ])->assertSessionHasErrors('family_structures.1');

        $this->post(route('register.step.store', 'areas'), [
            'support_areas' => ['learning-education', 'not-a-real-area'],
        ])->assertSessionHasErrors('support_areas.1');
    }

    #[Test]
    public function what_they_are_hoping_for_is_its_own_screen_and_is_optional(): void
    {
        $this->openWith();

        $this->get(route('register.step', 'hopes'))
            ->assertOk()
            ->assertSee('name="hopes[]"', false);

        $this->post(route('register.step.store', 'hopes'), [])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('register.step', 'areas'));

        $this->assertSame([], Registration::sole()->hopes);

        $this->post(route('register.step.store', 'hopes'), [
            'hopes' => ['families-who-understand', 'practical-advice'],
        ])->assertSessionHasNoErrors();

        $this->assertSame(
            ['families-who-understand', 'practical-advice'],
            Registration::sole()->hopes,
        );

        $this->post(route('register.step.store', 'hopes'), ['hopes' => ['a-pony']])
            ->assertSessionHasErrors('hopes.0');
    }

    #[Test]
    public function coming_back_to_the_hopes_screen_shows_what_they_chose(): void
    {
        $this->openWith();
        $this->post(route('register.step.store', 'hopes'), ['hopes' => ['local-families']]);

        $html = $this->get(route('register.step', 'hopes'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/value="local-families"[^>]*checked/', $html);
    }

    #[Test]
    public function the_detail_questions_are_one_screen_with_an_accordion_per_area(): void
    {
        // They used to be a screen per area — up to eight of them, with the
        // counter frozen so the form did not appear to grow the more honest
        // somebody was. One accordion screen is the same information without
        // the sleight of hand.
        $this->openWith();
        $this->chooseAreas(['learning-education', 'identity-belonging']);

        $response = $this->get(route('register.step', 'experiences'))->assertOk();
        $html = $response->getContent();

        $this->assertSame(2, substr_count($html, 'experience-groups__item'), 'one accordion per area chosen');
        $this->assertStringContainsString('<details', $html, 'the accordions are native details elements');

        $response->assertSee(Taxonomy::category('learning-education')['label'])
            ->assertSee(Taxonomy::category('identity-belonging')['label'])
            // An area they did not choose is not on their form at all.
            ->assertDontSee(Taxonomy::category('support-services')['label']);

        // And the counter is honest: six screens, and this is one of them.
        $response->assertSee('Step '.RegistrationFlow::number('experiences').' of '.RegistrationFlow::total());
    }

    #[Test]
    public function detailed_experiences_are_saved_against_their_category(): void
    {
        $this->openWith();
        $this->chooseAreas(['learning-education', 'identity-belonging']);

        $this->post(route('register.step.store', 'experiences'), [
            'items' => [
                'learning-education' => ['school-attendance', 'education-options'],
                'identity-belonging' => ['masking'],
            ],
        ])->assertRedirect(route('register.step', 'interests'));

        $experiences = Registration::sole()->experiences;

        $this->assertCount(3, $experiences);
        $this->assertSame(
            ['education-options', 'school-attendance'],
            $experiences->where('category', 'learning-education')->pluck('item')->sort()->values()->all(),
        );
        $this->assertSame(
            ['masking'],
            $experiences->where('category', 'identity-belonging')->pluck('item')->all(),
        );
    }

    #[Test]
    public function the_detail_screen_is_the_whole_picture_each_time(): void
    {
        // One screen holds every area, so what comes back is the complete
        // answer and replaces what was there. Changing one area's statements
        // must not disturb another's.
        $this->openWith();
        $this->chooseAreas(['learning-education', 'identity-belonging']);

        $this->post(route('register.step.store', 'experiences'), [
            'items' => [
                'learning-education' => ['school-attendance'],
                'identity-belonging' => ['masking'],
            ],
        ]);

        $this->post(route('register.step.store', 'experiences'), [
            'items' => [
                'learning-education' => ['homework-exams'],
                'identity-belonging' => ['masking'],
            ],
        ]);

        $experiences = Registration::sole()->fresh()->experiences;

        $this->assertCount(2, $experiences);
        $this->assertTrue($experiences->contains(fn ($e) => $e->item === 'homework-exams'));
        $this->assertTrue($experiences->contains(fn ($e) => $e->item === 'masking'));
        $this->assertFalse($experiences->contains(fn ($e) => $e->item === 'school-attendance'));
    }

    #[Test]
    public function the_detail_questions_show_four_statements_and_hide_the_rest(): void
    {
        // Eight tickboxes at once reads as a form. Four reads as a question,
        // and the rest are one press away behind a nested <details>, so it
        // still works with no JavaScript.
        $this->openWith();
        $this->chooseAreas(['learning-education']);

        $items = array_keys(Taxonomy::items('learning-education'));

        $this->assertCount(8, $items, 'this test assumes the eight-statement area');

        $response = $this->get(route('register.step', 'experiences'))->assertOk();
        $html = $response->getContent();

        $response->assertSee((count($items) - 4).' more');

        // Every statement is on the page — the later ones are behind the
        // disclosure rather than dropped, or they could never be chosen.
        foreach ($items as $slug) {
            $this->assertStringContainsString('value="'.$slug.'"', $html);
        }

        $this->assertSame(1, substr_count($html, 'experience-groups__more-summary'));
    }

    #[Test]
    public function a_statement_behind_the_more_link_is_open_when_it_is_already_ticked(): void
    {
        // Otherwise coming back looks like the form lost their answer.
        $this->openWith();
        $this->chooseAreas(['learning-education']);

        $later = array_keys(Taxonomy::items('learning-education'))[6];

        $this->post(route('register.step.store', 'experiences'), [
            'items' => ['learning-education' => [$later]],
        ]);

        $html = $this->get(route('register.step', 'experiences'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/value="'.$later.'"[^>]*checked/', $html);
        $this->assertMatchesRegularExpression('/experience-groups__more"\s+open/', $html);
    }

    #[Test]
    public function the_privacy_line_sits_inside_every_area_rather_than_at_the_foot(): void
    {
        // It answers the worry at the moment somebody is deciding whether to
        // tick something, not after they have scrolled past all of it.
        $this->openWith();
        $this->chooseAreas(['learning-education', 'identity-belonging']);

        $html = $this->get(route('register.step', 'experiences'))->assertOk()->getContent();

        $this->assertSame(
            2,
            substr_count($html, 'Your selections stay private and help us find families with similar experiences.'),
            'once inside each area',
        );

        // And not also as the shell's own footnote, which would say it twice.
        $this->assertStringNotContainsString('wizard__private', $html);
    }

    #[Test]
    public function skipping_the_areas_section_goes_straight_past_the_detail_questions(): void
    {
        // The source spreadsheet asks for this explicitly, to limit form
        // fatigue. There is nothing to ask on the next screen once nothing is
        // chosen, so it is not worth showing.
        $this->openWith();

        $this->get(route('register.step', 'areas'))
            ->assertOk()
            ->assertSee('Skip this section');

        $this->post(route('register.step.store', 'areas'), ['action' => 'skip'])
            ->assertRedirect(route('register.step', 'interests'));

        $registration = Registration::sole();

        $this->assertSame([], $registration->support_areas);
        $this->assertCount(0, $registration->experiences);
    }

    #[Test]
    public function skipping_clears_what_an_earlier_visit_chose(): void
    {
        // Skip has to mean skip. Leaving the old answers behind would tell
        // matching something they have just said is not true.
        $this->openWith();
        $this->chooseAreas(['learning-education']);

        $this->post(route('register.step.store', 'experiences'), [
            'items' => ['learning-education' => ['school-attendance']],
        ]);

        $this->assertCount(1, Registration::sole()->experiences);

        $this->post(route('register.step.store', 'areas'), ['action' => 'skip'])
            ->assertRedirect(route('register.step', 'interests'));

        $registration = Registration::sole()->fresh();

        $this->assertSame([], $registration->support_areas);
        $this->assertCount(0, $registration->experiences, 'their detail answers went with the areas');
    }

    #[Test]
    public function coming_back_to_the_detail_screen_shows_what_they_chose(): void
    {
        $this->openWith();
        $this->chooseAreas(['learning-education']);

        $this->post(route('register.step.store', 'experiences'), [
            'items' => ['learning-education' => ['school-attendance']],
        ]);

        $html = $this->get(route('register.step', 'experiences'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/value="school-attendance"[^>]*checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/value="homework-exams"[^>]*checked/', $html);
    }

    #[Test]
    public function an_answer_under_an_area_they_did_not_choose_is_dropped_quietly(): void
    {
        // The statements arrive keyed by area, which is not a shape a
        // validation rule expresses well — and throwing the whole screen back
        // at somebody over a stray key would be a poor trade. Anything that
        // is not theirs to answer is simply not saved.
        $this->openWith();
        $this->chooseAreas(['learning-education']);

        $this->post(route('register.step.store', 'experiences'), [
            'items' => [
                'learning-education' => ['school-attendance', 'not-a-real-statement'],
                'support-services' => ['waiting-lists'],
            ],
        ])->assertSessionHasNoErrors();

        $experiences = Registration::sole()->experiences;

        $this->assertCount(1, $experiences);
        $this->assertSame('school-attendance', $experiences->first()->item);
    }

    #[Test]
    public function choosing_no_areas_leaves_the_detail_screen_saying_so(): void
    {
        $this->openWith();
        $this->chooseAreas([]);

        $html = $this->get(route('register.step', 'experiences'))
            ->assertOk()
            ->assertSee('nothing to ask about here', false)
            ->getContent();

        // No accordions at all, rather than eight empty ones. (The site's own
        // menu is a <details> too, so count the wizard's.)
        $this->assertSame(0, substr_count($html, 'experience-groups__item'));

        $this->post(route('register.step.store', 'experiences'), [])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('register.step', 'interests'));
    }

    #[Test]
    public function the_interests_screen_asks_three_questions(): void
    {
        $this->openWith();

        $response = $this->get(route('register.step', 'interests'));

        $response->assertOk()
            ->assertSee('What does your family enjoy?')
            ->assertSee('Shared interests are often where friendships begin.')
            ->assertSee('Things you enjoy together')
            ->assertSee('Things that help you enjoy activities')
            ->assertSee('How would you prefer to connect?')
            ->assertSee('Hosts use this to make meet-ups work for your family.')
            ->assertSee('name="interests_other"', false);

        $html = $response->getContent();

        $this->assertSame(12, substr_count($html, 'name="interests[]"'));
        $this->assertSame(7, substr_count($html, 'name="activity_supports[]"'));
        $this->assertSame(6, substr_count($html, 'name="connection_styles[]"'));

        foreach (['Outdoors &amp; nature', 'Quiet &amp; sensory', 'Other'] as $label) {
            $response->assertSee($label, false);
        }
    }

    #[Test]
    public function every_answer_on_the_interests_screen_is_optional(): void
    {
        $this->openWith();

        $this->post(route('register.step.store', 'interests'), [])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('register.welcome'));

        $registration = Registration::sole();

        $this->assertSame([], $registration->interests);
        $this->assertSame([], $registration->activity_supports);
        $this->assertSame([], $registration->connection_styles);
        $this->assertNull($registration->interests_other);
    }

    #[Test]
    public function all_three_interests_answers_are_saved(): void
    {
        $this->openWith();

        $this->post(route('register.step.store', 'interests'), [
            'interests' => ['animals', 'water', 'quiet-sensory'],
            'activity_supports' => ['smaller-groups', 'familiar-places'],
            'connection_styles' => ['one-to-one'],
        ])->assertSessionHasNoErrors();

        $registration = Registration::sole();

        $this->assertSame(['animals', 'water', 'quiet-sensory'], $registration->interests);
        $this->assertSame(['smaller-groups', 'familiar-places'], $registration->activity_supports);
        $this->assertSame(['one-to-one'], $registration->connection_styles);
    }

    #[Test]
    public function an_interest_that_is_not_offered_is_rejected(): void
    {
        $this->openWith();

        $this->post(route('register.step.store', 'interests'), [
            'interests' => ['animals', 'competitive-yodelling'],
        ])->assertSessionHasErrors('interests.1');

        $this->post(route('register.step.store', 'interests'), [
            'activity_supports' => ['not-a-real-thing'],
        ])->assertSessionHasErrors('activity_supports.0');

        $this->post(route('register.step.store', 'interests'), [
            'connection_styles' => ['carrier-pigeon'],
        ])->assertSessionHasErrors('connection_styles.0');
    }

    #[Test]
    public function an_empty_textarea_is_stored_as_nothing_rather_than_an_empty_string(): void
    {
        // What a browser actually posts: the textarea is always present, and
        // present-but-empty is not the same as absent. An empty string here
        // would read as "they wrote something" everywhere downstream.
        $this->openWith();

        $this->post(route('register.step.store', 'interests'), [
            'interests' => ['animals'],
            'interests_other' => '',
        ])->assertSessionHasNoErrors();

        $registration = Registration::sole();

        $this->assertNull($registration->interests_other);
        $this->assertSame(['animals'], $registration->interests, 'an empty box is not a choice of Other');
    }

    #[Test]
    public function whitespace_alone_in_the_box_is_not_an_answer(): void
    {
        $this->openWith();

        $this->post(route('register.step.store', 'interests'), [
            'interests_other' => '   ',
        ])->assertSessionHasNoErrors();

        $registration = Registration::sole();

        $this->assertNull($registration->interests_other);
        $this->assertSame([], $registration->interests);
    }

    #[Test]
    public function writing_in_the_box_counts_as_choosing_other(): void
    {
        // The box is always on screen, because the form works with no
        // JavaScript. Somebody who fills it in has told us something, and
        // their answer should not turn on whether they spotted the pill.
        $this->openWith();

        $this->post(route('register.step.store', 'interests'), [
            'interests' => ['animals'],
            'interests_other' => 'Steam trains, mostly. And the seaside.',
        ])->assertSessionHasNoErrors();

        $registration = Registration::sole();

        $this->assertSame(['animals', 'other'], $registration->interests);
        $this->assertSame('Steam trains, mostly. And the seaside.', $registration->interests_other);
    }

    #[Test]
    public function ticking_other_as_well_does_not_record_it_twice(): void
    {
        $this->openWith();

        $this->post(route('register.step.store', 'interests'), [
            'interests' => ['other'],
            'interests_other' => 'Birdwatching',
        ]);

        $this->assertSame(['other'], Registration::sole()->interests);
    }

    #[Test]
    public function an_archived_other_is_never_added_behind_the_scenes(): void
    {
        // Archiving takes an option off the form. Auto-adding it would put a
        // retired slug back into live data, which is the one thing archiving
        // exists to prevent.
        $this->seed(FormSeeder::class);
        FormOption::query()->where('group', 'interests')->where('slug', 'other')
            ->update(['archived_at' => now()]);
        FormDefinition::forget(Taxonomy::FORM);

        $this->openWith();

        $this->post(route('register.step.store', 'interests'), [
            'interests_other' => 'Something they typed anyway',
        ])->assertSessionHasNoErrors();

        $registration = Registration::sole();

        $this->assertSame([], $registration->interests);
        $this->assertSame('Something they typed anyway', $registration->interests_other, 'what they wrote is still kept');
    }

    #[Test]
    public function a_note_that_runs_away_with_itself_is_refused_kindly(): void
    {
        $this->openWith();

        $this->post(route('register.step.store', 'interests'), [
            'interests_other' => str_repeat('a', 1001),
        ])->assertSessionHasErrors(['interests_other' => 'That is a little long for this box — could you shorten it a bit?']);

        $this->assertNull(Registration::sole()->interests_other);
    }

    #[Test]
    public function coming_back_to_the_interests_screen_shows_what_they_chose(): void
    {
        $this->openWith();

        $this->post(route('register.step.store', 'interests'), [
            'interests' => ['animals'],
            'activity_supports' => ['clear-routines'],
            'connection_styles' => ['online'],
            'interests_other' => 'Long walks',
        ]);

        $html = $this->get(route('register.step', 'interests'))->assertOk()->getContent();

        foreach (['animals', 'clear-routines', 'online'] as $slug) {
            $this->assertMatchesRegularExpression('/value="'.$slug.'"[^>]*checked/', $html);
        }

        $this->assertStringContainsString('Long walks</textarea>', $html);
    }

    #[Test]
    public function the_last_screen_completes_the_registration(): void
    {
        $this->openWith();

        $this->assertNull(Registration::sole()->completed_at);

        $this->post(route('register.step.store', 'interests'), ['interests' => ['outdoors-nature']])
            ->assertRedirect(route('register.welcome'));

        $this->assertNotNull(Registration::sole()->completed_at);

        $this->get(route('register.welcome'))
            ->assertOk()
            ->assertSee(config('frith-content.join.welcome.title'))
            // Said back to them, because a typo here is the one mistake that
            // means they never hear from us again.
            ->assertSee('We will email you at')
            ->assertSee('sam@example.com');
    }

    #[Test]
    public function the_counter_is_six_screens_and_never_moves_sideways(): void
    {
        $this->openWith();
        $this->chooseAreas(['learning-education', 'health-wellbeing', 'identity-belonging']);

        $total = RegistrationFlow::total();

        $this->assertSame(6, $total);

        foreach (RegistrationFlow::STEPS as $i => $step) {
            $this->get(route('register.step', $step))
                ->assertOk()
                ->assertSee('Step '.($i + 1)." of {$total}");
        }
    }

    #[Test]
    public function back_walks_the_screens_in_reverse_order(): void
    {
        $this->openWith();

        // The first screen has nowhere to go back to.
        $this->get(route('register.step', 'you'))
            ->assertOk()
            ->assertDontSee('&larr; Back', false);

        foreach (RegistrationFlow::STEPS as $i => $step) {
            if ($i === 0) {
                continue;
            }

            $this->get(route('register.step', $step))
                ->assertOk()
                ->assertSee(route('register.step', RegistrationFlow::STEPS[$i - 1]), false);
        }
    }

    #[Test]
    public function you_cannot_skip_ahead_without_starting(): void
    {
        $this->get(route('register.step', 'family'))
            ->assertRedirect(route('register.step', 'you'));

        $this->get(route('register.step', 'experiences'))
            ->assertRedirect(route('register.step', 'you'));

        // And nobody is told they are a Founder until they are one.
        $this->get(route('register.welcome'))
            ->assertRedirect(route('register.start'));
    }

    #[Test]
    public function a_screen_that_does_not_exist_is_a_404(): void
    {
        $this->get('/join/whatever')->assertNotFound();
        $this->post('/join/whatever')->assertNotFound();
    }

    #[Test]
    public function coming_back_picks_up_an_unfinished_registration_rather_than_duplicating_it(): void
    {
        $this->openWith(['email' => 'sam@example.com']);

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
            'postcode_outcode' => 'ZZ9',
        ])->assertRedirect(route('register.step', 'family'));

        $this->post(route('register.step.store', 'interests'), ['interests' => ['animals']])
            ->assertRedirect(route('register.welcome'));

        $existing->refresh();

        $this->assertSame('Priya', $existing->first_name);
        $this->assertSame('M1', $existing->postcode_outcode);
        $this->assertSame([], $existing->interests ?? []);
        $this->assertDatabaseCount('registrations', 1);
    }

    #[Test]
    public function every_taxonomy_slug_in_the_form_is_one_the_validator_accepts(): void
    {
        // Guards against a label being added to the config without a slug, or
        // a category being renamed and the form quietly rejecting everything.
        $this->openWith();

        $this->post(route('register.step.store', 'family'), [
            'action' => 'continue',
            'family_structures' => Taxonomy::familyStructureSlugs(),
        ])->assertSessionHasNoErrors();

        $this->post(route('register.step.store', 'hopes'), ['hopes' => Taxonomy::hopeSlugs()])
            ->assertSessionHasNoErrors();

        $this->post(route('register.step.store', 'areas'), ['support_areas' => Taxonomy::categorySlugs()])
            ->assertSessionHasNoErrors();

        $items = [];

        foreach (Taxonomy::categorySlugs() as $category) {
            $items[$category] = Taxonomy::itemSlugs($category);
        }

        $this->post(route('register.step.store', 'experiences'), ['items' => $items])
            ->assertSessionHasNoErrors();

        $this->post(route('register.step.store', 'interests'), [
            'interests' => Taxonomy::interestSlugs(),
            'activity_supports' => Taxonomy::activitySupportSlugs(),
            'connection_styles' => Taxonomy::connectionStyleSlugs(),
        ])->assertSessionHasNoErrors();

        $registration = Registration::sole()->fresh();

        $this->assertCount(63, $registration->experiences);
        $this->assertNotNull($registration->completed_at);
    }
}
