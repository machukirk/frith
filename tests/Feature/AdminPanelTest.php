<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Resources\WaitlistSignups\Pages\ListWaitlistSignups;
use App\Filament\Resources\WaitlistSignups\Tables\ExportWaitlistAction;
use App\Filament\Resources\WaitlistSignups\WaitlistSignupResource;
use App\Models\Page;
use App\Models\User;
use App\Models\WaitlistSignup;
use App\Support\PageContent;
use Database\Seeders\PageSeeder;
use Filament\Actions\Testing\TestAction;
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
    public function an_editor_can_reach_the_content_but_not_the_waiting_list(): void
    {
        // The whole point of the two roles: whoever looks after the words has
        // no business seeing the email addresses of families who signed up.
        $this->actingAs($this->editor());

        Livewire::test(ListPages::class)->assertOk();
        Livewire::test(ListWaitlistSignups::class)->assertForbidden();
    }

    #[Test]
    public function an_owner_can_reach_both(): void
    {
        $this->actingAs($this->owner());

        Livewire::test(ListPages::class)->assertOk();
        Livewire::test(ListWaitlistSignups::class)->assertOk();
    }

    #[Test]
    public function the_waiting_list_is_hidden_from_an_editors_navigation(): void
    {
        $this->actingAs($this->editor());
        $this->assertFalse(WaitlistSignupResource::canViewAny());

        $this->actingAs($this->owner());
        $this->assertTrue(WaitlistSignupResource::canViewAny());
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
        $this->assertSame(config('frith.coming_soon.form.button'), $copy['form']['button']);
        $this->assertCount(3, $copy['cards']);
    }

    #[Test]
    public function the_page_still_renders_with_no_content_row_at_all(): void
    {
        $this->assertDatabaseCount('pages', 0);

        $this->get(route('coming-soon'))
            ->assertOk()
            ->assertSee(config('frith.coming_soon.form.button'));
    }

    #[Test]
    public function an_owner_can_delete_a_signup_to_honour_an_erasure_request(): void
    {
        $signup = WaitlistSignup::factory()->confirmed()->create();

        $this->actingAs($this->owner());

        Livewire::test(ListWaitlistSignups::class)
            ->callAction(TestAction::make('delete')->table($signup));

        $this->assertDatabaseCount('waitlist_signups', 0);
    }

    #[Test]
    public function the_csv_export_carries_the_consent_record_with_the_addresses(): void
    {
        // A file of email addresses with no provenance is not much use if
        // somebody later asks what these people agreed to.
        WaitlistSignup::factory()->confirmed()->create(['email' => 'sam@example.com']);

        $this->actingAs($this->owner());

        Livewire::test(ListWaitlistSignups::class)
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
        ExportWaitlistAction::response()->sendContent();

        return (string) ob_get_clean();
    }
}
