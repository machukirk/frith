<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\FormOption;
use App\Models\Registration;
use App\Support\FormDefinition;
use App\Support\RegistrationFlow;
use App\Support\Taxonomy;
use Database\Seeders\FormSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FormDefinitionTest extends TestCase
{
    use RefreshDatabase;

    private function seedForm(): Form
    {
        $this->seed(FormSeeder::class);

        return Form::query()->where('slug', Taxonomy::FORM)->sole();
    }

    #[Test]
    public function the_form_still_works_with_nothing_in_the_database(): void
    {
        // A fresh install, or a form nobody has opened in the admin yet.
        $this->assertDatabaseCount('forms', 0);

        $this->assertCount(8, Taxonomy::categories());
        $this->assertCount(6, Taxonomy::familyStructures());
        $this->get(route('register.step', 'you'))->assertOk()->assertSee('What should we call you?');
    }

    #[Test]
    public function seeding_moves_the_whole_taxonomy_into_the_database_unchanged(): void
    {
        $form = $this->seedForm();

        $this->assertCount(count(RegistrationFlow::STEPS), $form->steps);
        $this->assertCount(8, $form->options()->whereNull('parent_id')->where('group', 'support_areas')->get());
        $this->assertCount(63, $form->options()->whereNotNull('parent_id')->get());
        $this->assertCount(6, $form->options()->where('group', 'family_structures')->get());
        $this->assertCount(12, $form->options()->where('group', 'interests')->get());
        $this->assertCount(7, $form->options()->where('group', 'activity_supports')->get());

        // Every list the form offers has to be one the options screen can name,
        // or an editor gets a row labelled with a raw database string.
        foreach ($form->options->pluck('group')->unique() as $group) {
            $this->assertArrayHasKey($group, FormOption::GROUPS, "{$group} is not a named list");
            $this->assertArrayHasKey($group, FormOption::GROUP_BADGES, "{$group} has no short label");
        }

        // The slugs are what registrations already point at, so they must not move.
        $this->assertSame(
            array_keys(config('frith-taxonomy.categories')),
            Taxonomy::categorySlugs(),
        );
        $this->assertSame(
            array_keys(config('frith-taxonomy.interests')),
            Taxonomy::interestSlugs(),
        );
    }

    #[Test]
    public function seeding_again_never_overwrites_an_editors_wording(): void
    {
        $form = $this->seedForm();

        $step = $form->steps()->where('key', 'you')->sole();
        $step->update(['heading' => 'What shall we call you?']);

        $this->seed(FormSeeder::class);

        $this->assertSame('What shall we call you?', $step->fresh()->heading);
    }

    /** Later screens need a registration in the session to be reachable. */
    private function startRegistration(): void
    {
        $this->post(route('register.step.store', 'you'), [
            'first_name' => 'Sam', 'email' => 'sam@example.com',
        ]);
    }

    #[Test]
    public function editing_a_heading_changes_the_live_form(): void
    {
        $form = $this->seedForm();
        $this->startRegistration();

        $this->get(route('register.step', 'location'))->assertSee('Where are you based?');

        $form->steps()->where('key', 'location')->sole()->update([
            'heading' => 'Which part of the country are you in?',
            'standfirst' => 'Just the first few characters.',
        ]);

        $this->get(route('register.step', 'location'))
            ->assertSee('Which part of the country are you in?')
            ->assertSee('Just the first few characters.')
            ->assertDontSee('Where are you based?');
    }

    #[Test]
    public function editing_a_field_label_and_help_changes_the_live_form(): void
    {
        $form = $this->seedForm();
        $this->startRegistration();

        $form->steps()->where('key', 'location')->sole()
            ->fields()->where('key', 'postcode_outcode')->sole()
            ->update(['label' => 'Your postcode area', 'help' => 'We never ask for the rest.']);

        $this->get(route('register.step', 'location'))
            ->assertSee('Your postcode area')
            ->assertSee('We never ask for the rest.');
    }

    #[Test]
    public function rewording_an_option_leaves_everyone_who_chose_it_attached(): void
    {
        // The whole reason slugs are stored rather than labels.
        $form = $this->seedForm();

        $this->startRegistration();
        $this->post(route('register.step.store', 'support'), ['support_areas' => ['identity-belonging']]);
        $this->post(route('register.experiences.store', 'identity-belonging'), [
            'action' => 'continue', 'items' => ['masking'],
        ]);

        $form->options()->where('slug', 'masking')->sole()
            ->update(['label' => 'My child masks how they feel to fit in']);

        $experience = Registration::sole()->experiences->sole();

        $this->assertSame('masking', $experience->item);
        $this->assertSame('My child masks how they feel to fit in', $experience->label());
    }

    #[Test]
    public function an_archived_option_stops_being_offered(): void
    {
        $form = $this->seedForm();

        $this->startRegistration();

        $this->get(route('register.step', 'support'))->assertSee('Identity &amp; Belonging', false);

        $form->options()->where('slug', 'identity-belonging')->whereNull('parent_id')->sole()
            ->update(['archived_at' => now()]);

        $this->get(route('register.step', 'support'))->assertDontSee('Identity &amp; Belonging', false);
    }

    #[Test]
    public function an_archived_option_stops_being_accepted(): void
    {
        // Not just hidden. Somebody replaying an old form post must not be able
        // to write a choice that is no longer offered.
        $form = $this->seedForm();

        $this->startRegistration();

        $form->options()->where('slug', 'identity-belonging')->whereNull('parent_id')->sole()
            ->update(['archived_at' => now()]);

        $this->post(route('register.step.store', 'support'), ['support_areas' => ['identity-belonging']])
            ->assertSessionHasErrors('support_areas.0');
    }

    #[Test]
    public function an_archived_option_still_resolves_for_everyone_who_chose_it(): void
    {
        // The point of archiving rather than deleting. Their answer keeps
        // meaning something in the admin panel and in the export.
        $form = $this->seedForm();

        $this->startRegistration();
        $this->post(route('register.step.store', 'support'), ['support_areas' => ['identity-belonging']]);
        $this->post(route('register.experiences.store', 'identity-belonging'), [
            'action' => 'continue', 'items' => ['masking'],
        ]);

        $form->options()->where('slug', 'masking')->sole()->update(['archived_at' => now()]);
        $form->options()->where('slug', 'identity-belonging')->whereNull('parent_id')->sole()
            ->update(['archived_at' => now()]);

        $experience = Registration::sole()->experiences->sole();

        // Read from the taxonomy rather than repeated here, so rewording a
        // label is one edit rather than two.
        $this->assertSame(config('frith-taxonomy.categories.identity-belonging.items.masking'), $experience->label());
        $this->assertSame(config('frith-taxonomy.categories.identity-belonging.label'), $experience->categoryLabel());
    }

    #[Test]
    public function usage_counts_are_what_make_the_consequence_visible(): void
    {
        $form = $this->seedForm();

        $this->startRegistration();
        $this->post(route('register.step.store', 'support'), ['support_areas' => ['identity-belonging']]);
        $this->post(route('register.experiences.store', 'identity-belonging'), [
            'action' => 'continue', 'items' => ['masking'],
        ]);

        $category = $form->options()->where('slug', 'identity-belonging')->whereNull('parent_id')->sole();
        $item = $form->options()->where('slug', 'masking')->sole();
        $unused = $form->options()->where('slug', 'confidence')->sole();

        $this->assertSame(1, $category->usageCount());
        $this->assertSame(1, $item->usageCount());
        $this->assertSame(0, $unused->usageCount());
    }

    #[Test]
    public function a_new_option_is_offered_and_accepted_straight_away(): void
    {
        $form = $this->seedForm();

        $parent = $form->options()->where('slug', 'identity-belonging')->whereNull('parent_id')->sole();

        FormOption::query()->create([
            'form_id' => $form->id,
            'group' => 'support_areas',
            'parent_id' => $parent->id,
            'slug' => 'proud-of-who-they-are',
            'label' => 'We want our child to feel proud of who they are',
            'position' => 99,
        ]);

        $this->startRegistration();
        $this->post(route('register.step.store', 'support'), ['support_areas' => ['identity-belonging']]);

        $this->get(route('register.experiences.show', 'identity-belonging'))
            ->assertSee('We want our child to feel proud of who they are');

        $this->post(route('register.experiences.store', 'identity-belonging'), [
            'action' => 'continue', 'items' => ['proud-of-who-they-are'],
        ])->assertSessionHasNoErrors();

        $this->assertSame('proud-of-who-they-are', Registration::sole()->experiences->sole()->item);
    }

    #[Test]
    public function saving_the_form_clears_the_cache_so_edits_show_immediately(): void
    {
        // Without this an editor saves, reloads the site, sees no change, and
        // saves again.
        $form = $this->seedForm();

        FormDefinition::for(Taxonomy::FORM);

        $form->steps()->where('key', 'family')->sole()->update(['heading' => 'Who lives with you?']);

        $this->assertSame('Who lives with you?', FormDefinition::for(Taxonomy::FORM)['steps']['family']['heading']);
    }
}
