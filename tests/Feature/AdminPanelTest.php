<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Forms\Pages\EditForm;
use App\Filament\Resources\Forms\Pages\ManageFormOptions;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Resources\Registrations\Pages\ListRegistrations;
use App\Filament\Resources\Registrations\RegistrationResource;
use App\Filament\Resources\Registrations\Schemas\RegistrationInfolist;
use App\Filament\Resources\Registrations\Tables\ExportRegistrationsAction;
use App\Models\Form;
use App\Models\Page;
use App\Models\Registration;
use App\Models\User;
use App\Support\PageContent;
use App\Support\Taxonomy;
use Database\Seeders\FormSeeder;
use Database\Seeders\PageSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminPanelTest extends TestCase
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

    #[Test]
    public function the_panel_is_closed_to_people_who_are_not_signed_in(): void
    {
        $this->get('/admin')->assertRedirectContains('/admin/login');
    }

    #[Test]
    public function an_editor_can_reach_the_content_but_not_the_founders(): void
    {
        // The whole point of the two roles: whoever looks after the words has
        // no business seeing what families told us about their lives.
        $this->actingAs($this->editor());

        Livewire::test(ListPages::class)->assertOk();
        Livewire::test(ListRegistrations::class)->assertForbidden();
    }

    #[Test]
    public function an_owner_can_reach_both(): void
    {
        $this->actingAs($this->owner());

        Livewire::test(ListPages::class)->assertOk();
        Livewire::test(ListRegistrations::class)->assertOk();
    }

    #[Test]
    public function the_founders_are_hidden_from_an_editors_navigation(): void
    {
        $this->actingAs($this->editor());
        $this->assertFalse(RegistrationResource::canViewAny());

        $this->actingAs($this->owner());
        $this->assertTrue(RegistrationResource::canViewAny());
    }

    #[Test]
    public function editing_the_page_changes_what_visitors_see(): void
    {
        $this->seed(PageSeeder::class);
        $page = Page::query()->where('slug', 'home')->sole();

        // Warm the cache first, so this also proves saving clears it. Without
        // that, an editor saves, reloads the site, sees no change, and saves again.
        $this->get(route('home'))->assertSee('Shared experiences. Real connection.');

        $this->actingAs($this->editor());

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->assertOk()
            ->fillForm(fn (array $state) => [
                'content' => [
                    ...$state['content'],
                    'hero' => [
                        ...$state['content']['hero'],
                        'eyebrow' => 'For every family navigating SEND',
                        'standfirst' => 'A new paragraph, written in the admin panel.',
                    ],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get(route('home'))
            ->assertSee('For every family navigating SEND')
            ->assertSee('A new paragraph, written in the admin panel.')
            ->assertDontSee('Shared experiences. Real connection.');
    }

    #[Test]
    public function a_screen_that_is_only_a_list_of_choices_has_no_empty_fields_panel(): void
    {
        // Which areas of family life is nothing but its options, which live on
        // the Options tab. An empty "Fields on this screen" panel only sends
        // somebody looking for something that was never going to be in it.
        $this->seed(FormSeeder::class);
        $form = Form::query()->where('slug', Taxonomy::FORM)->sole();

        $this->assertSame(
            0,
            $form->steps()->where('key', 'areas')->sole()->fields()->count(),
            'the areas screen should have no fields of its own',
        );

        // The screens that do have fields still show them.
        $this->assertGreaterThan(0, $form->steps()->where('key', 'you')->sole()->fields()->count());

        $this->actingAs($this->editor());

        $html = Livewire::test(EditForm::class, ['record' => $form->getRouteKey()])
            ->assertOk()
            ->html();

        // One panel per screen that has fields, and not one more.
        $withFields = $form->steps()->has('fields')->count();

        $this->assertSame(
            $withFields,
            substr_count($html, 'Fields on this screen'),
            'an empty fields panel is being rendered',
        );
    }

    #[Test]
    public function both_form_tabs_are_reachable_from_each_other(): void
    {
        // The Options page had a route and no link to it for weeks. It was only
        // openable by typing the URL, which nobody was ever going to do.
        $this->seed(FormSeeder::class);
        $form = Form::query()->where('slug', Taxonomy::FORM)->sole();

        $this->actingAs($this->editor());

        $wording = EditForm::getUrl(['record' => $form]);
        $options = ManageFormOptions::getUrl(['record' => $form]);

        $this->assertStringContainsString($options, Livewire::test(EditForm::class, ['record' => $form->getRouteKey()])->html());
        $this->assertStringContainsString($wording, Livewire::test(ManageFormOptions::class, ['record' => $form->getRouteKey()])->html());
    }

    #[Test]
    public function an_archived_option_still_shows_in_its_block_marked_as_off_the_form(): void
    {
        // Archiving is not deleting. It has to stay visible to whoever is
        // looking after the lists, or the only way to find one again is to
        // remember it existed.
        $this->seed(FormSeeder::class);
        $form = Form::query()->where('slug', Taxonomy::FORM)->sole();

        $archived = $form->optionsIn('interests')->where('slug', 'water')->sole();
        $archived->forceFill(['archived_at' => now()])->save();

        $this->actingAs($this->editor());

        Livewire::test(ManageFormOptions::class, ['record' => $form->getRouteKey()])
            ->assertOk()
            ->assertCanSeeTableRecords([$archived]);

        $this->assertTrue($archived->refresh()->isArchived());
        $this->assertArrayNotHasKey('water', Taxonomy::interests(), 'it should stop being offered');
        $this->assertSame('Water', Taxonomy::interestLabel('water'), 'and still resolve for anybody who chose it');
    }

    #[Test]
    public function an_edit_records_who_made_it(): void
    {
        $this->seed(PageSeeder::class);
        $page = Page::query()->where('slug', 'home')->sole();
        $editor = $this->editor();

        $this->actingAs($editor);

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->fillForm(fn (array $state) => ['content' => $state['content']])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($editor->id, $page->refresh()->updated_by);
    }

    #[Test]
    public function removing_a_section_item_actually_removes_it(): void
    {
        // Lists are replaced wholesale rather than merged with the config
        // defaults. Merging element by element would put the deleted one back.
        $this->seed(PageSeeder::class);
        $page = Page::query()->where('slug', 'home')->sole();

        $content = $page->content;
        $content['pillars'] = [$content['pillars'][0]];
        $page->update(['content' => $content]);

        $copy = PageContent::for('home');

        $this->assertCount(1, $copy['pillars']);
        $this->get(route('home'))->assertDontSee('People who have been there');
    }

    #[Test]
    public function a_key_that_has_never_been_edited_falls_back_to_config(): void
    {
        // A deploy can add a field before anyone has opened the admin panel.
        // The page has to keep rendering words rather than blanks.
        Page::query()->create([
            'slug' => 'home',
            'name' => 'Home page',
            'content' => ['hero' => ['eyebrow' => 'Only this key is set']],
        ]);

        $copy = PageContent::for('home');

        $this->assertSame('Only this key is set', $copy['hero']['eyebrow']);

        // The key beside it, and whole sections below it, still fall back.
        $this->assertSame(config('frith-content.home.hero.primary_cta'), $copy['hero']['primary_cta']);
        $this->assertCount(3, $copy['pillars']);
        $this->assertCount(9, $copy['faq']['items']);
    }

    #[Test]
    public function the_page_still_renders_with_no_content_row_at_all(): void
    {
        $this->assertDatabaseCount('pages', 0);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee(config('frith-content.home.hero.primary_cta'));
    }

    #[Test]
    public function an_owner_can_delete_a_registration_to_honour_an_erasure_request(): void
    {
        $registration = Registration::factory()->create();

        $this->actingAs($this->owner());

        Livewire::test(ListRegistrations::class)
            ->callAction(TestAction::make('delete')->table($registration));

        $this->assertDatabaseCount('registrations', 0);
    }

    #[Test]
    public function a_founders_interests_are_readable_in_the_panel(): void
    {
        // Naming an infolist entry after an array column makes Filament run the
        // formatter once per element. That is what is wanted for badges and
        // exactly what breaks a summary, so the new array entries are rendered
        // here rather than assumed to behave like the ones beside them.
        $registration = Registration::factory()->create([
            'interests' => ['games-technology-building', 'water'],
            'interests_other' => 'Steam trains, mostly.',
            'activity_supports' => ['smaller-groups', 'clear-routines'],
            'hopes' => ['belonging'],
            'connection_styles' => ['one-to-one'],
            'family_preferences' => ['similar-age'],
        ]);

        $html = $this->renderInfolist($registration);

        $this->assertStringContainsString('A sense of belonging and community', $html);
        $this->assertStringContainsString('One-to-one chats', $html);
        $this->assertStringContainsString('Families with children a similar age', $html);
        $this->assertStringContainsString('Games &amp; building', $html);
        $this->assertStringContainsString('Water', $html);
        $this->assertStringContainsString('Steam trains, mostly.', $html);
        $this->assertStringContainsString('Smaller groups', $html);
        $this->assertStringContainsString('Clear routines', $html);
        $this->assertStringNotContainsString('games-technology-building', $html, 'a slug should never reach the screen');
    }

    #[Test]
    public function a_founder_who_skipped_the_last_screen_reads_as_skipped(): void
    {
        $html = $this->renderInfolist(Registration::factory()->create([
            'interests' => null,
            'interests_other' => null,
            'activity_supports' => null,
        ]));

        $this->assertStringContainsString('Not answered', $html);
    }

    /**
     * Renders the read-only view of one family.
     *
     * Built against a real Livewire host rather than mounted through the table
     * action: Filament renders the modal body client-side, so the action route
     * shows nothing to assert on.
     */
    private function renderInfolist(Registration $registration): string
    {
        $this->actingAs($this->owner());

        $host = Livewire::test(ListRegistrations::class)->instance();

        return RegistrationInfolist::configure(Schema::make($host)->record($registration))->toHtml();
    }

    #[Test]
    public function the_csv_export_writes_labels_rather_than_slugs(): void
    {
        // "quiet-sensory" in a spreadsheet is not something anybody can read.
        Registration::factory()->create([
            'interests' => ['quiet-sensory', 'animals'],
            'interests_other' => 'Steam trains',
            'activity_supports' => ['smaller-groups'],
            'connection_styles' => ['one-to-one'],
            'hopes' => ['practical-advice'],
            'family_preferences' => ['open-to-any'],
        ]);

        $this->actingAs($this->owner());

        Livewire::test(ListRegistrations::class)
            ->callAction(TestAction::make('export')->table());

        $csv = $this->captureExport();

        $this->assertStringContainsString('Quiet & sensory', $csv);
        $this->assertStringContainsString('Smaller groups', $csv);
        $this->assertStringContainsString('Practical help with forms and processes', $csv);
        $this->assertStringContainsString('One-to-one chats', $csv);
        $this->assertStringContainsString('I’m open to meeting any family who understands', $csv);
        $this->assertStringContainsString('Steam trains', $csv);
        $this->assertStringNotContainsString('quiet-sensory', $csv);

        // Taxonomy order, not the order they happened to be submitted in.
        $this->assertStringContainsString('Animals; Quiet & sensory', $csv);
    }

    #[Test]
    public function the_csv_export_carries_the_consent_record_with_the_addresses(): void
    {
        // A file of personal data with no provenance is not much use if
        // somebody later asks what these people agreed to.
        Registration::factory()->create(['email' => 'sam@example.com']);

        $this->actingAs($this->owner());

        Livewire::test(ListRegistrations::class)
            ->callAction(TestAction::make('export')->table())
            ->assertFileDownloaded();

        $csv = $this->captureExport();

        $this->assertStringContainsString('sam@example.com', $csv);
        $this->assertStringContainsString('Consent wording', $csv);
        $this->assertStringContainsString(config('frith.consent.version'), $csv);
        $this->assertStringContainsString(config('frith.consent.text'), $csv);
    }

    /** Runs the streaming callback and captures what it writes. */
    private function captureExport(): string
    {
        ob_start();
        ExportRegistrationsAction::response()->sendContent();

        return (string) ob_get_clean();
    }
}
