<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Forms\Pages\EditForm;
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
        $page = Page::query()->where('slug', 'coming-soon')->sole();

        // Warm the cache first, so this also proves saving clears it. Without
        // that, an editor saves, reloads the site, sees no change, and saves again.
        $this->get(route('coming-soon'))->assertSee('A community for families with SEND');

        $this->actingAs($this->editor());

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->assertOk()
            ->fillForm(fn (array $state) => [
                'content' => [
                    ...$state['content'],
                    'eyebrow' => 'For every family navigating SEND',
                    'standfirst' => 'A new paragraph, written in the admin panel.',
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get(route('coming-soon'))
            ->assertSee('For every family navigating SEND')
            ->assertSee('A new paragraph, written in the admin panel.')
            ->assertDontSee('A community for families with SEND');
    }

    #[Test]
    public function the_follow_up_screens_are_visible_to_whoever_edits_the_form(): void
    {
        // They are not rows in form_steps — there is one per area somebody
        // picks — so without this an editor has no way of knowing the screens
        // between step five and step six exist at all.
        $this->seed(FormSeeder::class);
        $form = Form::query()->where('slug', Taxonomy::FORM)->sole();

        $this->actingAs($this->editor());

        $html = Livewire::test(EditForm::class, ['record' => $form->getRouteKey()])
            ->assertOk()
            ->html();

        $this->assertStringContainsString('The follow-up screens', $html);
        $this->assertStringContainsString('One screen per area they picked', $html);

        // Every area is listed, with its own wording and how much is on it.
        foreach (Taxonomy::categories() as $slug => $area) {
            $this->assertStringContainsString(e($area['label']), $html, "{$slug} is missing");
            $this->assertStringContainsString(e($area['description']), $html);
        }

        $this->assertStringContainsString('8 statements', $html);
        $this->assertStringContainsString('Change this wording on the Options tab', $html);
    }

    #[Test]
    public function an_archived_area_is_shown_as_one_nobody_sees(): void
    {
        $this->seed(FormSeeder::class);
        $form = Form::query()->where('slug', Taxonomy::FORM)->sole();

        $form->options()->where('group', 'support_areas')->whereNull('parent_id')
            ->where('slug', 'learning-education')->update(['archived_at' => now()]);

        $this->actingAs($this->editor());

        Livewire::test(EditForm::class, ['record' => $form->getRouteKey()])
            ->assertSee('Archived — nobody is offered this');
    }

    #[Test]
    public function an_edit_records_who_made_it(): void
    {
        $this->seed(PageSeeder::class);
        $page = Page::query()->where('slug', 'coming-soon')->sole();
        $editor = $this->editor();

        $this->actingAs($editor);

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->fillForm(fn (array $state) => ['content' => $state['content']])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($editor->id, $page->refresh()->updated_by);
    }

    #[Test]
    public function removing_a_card_actually_removes_it(): void
    {
        // Lists are replaced wholesale rather than merged with the config
        // defaults. Merging element by element would put the deleted card back.
        $this->seed(PageSeeder::class);
        $page = Page::query()->where('slug', 'coming-soon')->sole();

        $content = $page->content;
        $content['cards'] = [$content['cards'][0]];
        $page->update(['content' => $content]);

        $copy = PageContent::for('coming-soon');

        $this->assertCount(1, $copy['cards']);
        $this->get(route('coming-soon'))->assertDontSee('People who have been there');
    }

    #[Test]
    public function a_key_that_has_never_been_edited_falls_back_to_config(): void
    {
        // A deploy can add a field before anyone has opened the admin panel.
        // The page has to keep rendering words rather than blanks.
        Page::query()->create([
            'slug' => 'coming-soon',
            'name' => 'Coming soon page',
            'content' => ['eyebrow' => 'Only this key is set'],
        ]);

        $copy = PageContent::for('coming-soon');

        $this->assertSame('Only this key is set', $copy['eyebrow']);
        $this->assertSame(config('frith.coming_soon.cta.button'), $copy['cta']['button']);
        $this->assertCount(3, $copy['cards']);
    }

    #[Test]
    public function the_page_still_renders_with_no_content_row_at_all(): void
    {
        $this->assertDatabaseCount('pages', 0);

        $this->get(route('coming-soon'))
            ->assertOk()
            ->assertSee(config('frith.coming_soon.cta.button'));
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
        $this->assertStringContainsString('Games, Technology &amp; Building', $html);
        $this->assertStringContainsString('Water Activities', $html);
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
            'hopes' => ['practical-advice'],
            'family_preferences' => ['open-to-any'],
        ]);

        $this->actingAs($this->owner());

        Livewire::test(ListRegistrations::class)
            ->callAction(TestAction::make('export')->table());

        $csv = $this->captureExport();

        $this->assertStringContainsString('Quiet & Sensory-Friendly Activities', $csv);
        $this->assertStringContainsString('Smaller groups', $csv);
        $this->assertStringContainsString('Practical advice from other parents', $csv);
        $this->assertStringContainsString('I’m open to meeting any family who understands', $csv);
        $this->assertStringContainsString('Steam trains', $csv);
        $this->assertStringNotContainsString('quiet-sensory', $csv);

        // Taxonomy order, not the order they happened to be submitted in.
        $this->assertStringContainsString('Animals; Quiet & Sensory-Friendly Activities', $csv);
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
