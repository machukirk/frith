<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Http\Controllers\PagePreviewController;
use App\Models\Page;
use App\Models\User;
use App\Support\PageContent;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PagePreviewTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): User
    {
        return User::factory()->create(['role' => UserRole::Editor]);
    }

    #[Test]
    public function a_draft_is_not_public(): void
    {
        // It is somebody's unsaved work, and it is reached by a URL that is
        // easy to guess.
        $this->seed(PageSeeder::class);

        $this->get(route('admin.preview', 'home'))
            ->assertRedirect(route('filament.admin.auth.login'))
            ->assertDontSee('Find your people');
    }

    #[Test]
    public function it_renders_the_real_page(): void
    {
        $this->seed(PageSeeder::class);
        $this->actingAs($this->editor());

        $this->get(route('admin.preview', 'terms'))
            ->assertOk()
            // The same layout and the same words as the page itself.
            ->assertSee('Terms of Service')
            ->assertSee('Policies')
            ->assertSee('/build/assets/main-', false)
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    #[Test]
    public function it_shows_what_has_been_typed_but_not_saved(): void
    {
        $this->seed(PageSeeder::class);
        $page = Page::query()->where('slug', 'terms')->sole();
        $this->actingAs($this->editor());

        $draft = $page->content;
        $draft['head']['title'] = 'A heading that has not been saved';

        $this->withSession([PagePreviewController::key('terms') => $draft])
            ->get(route('admin.preview', 'terms'))
            ->assertOk()
            ->assertSee('A heading that has not been saved');

        // And the page itself is untouched by having been previewed.
        $this->assertSame('Terms of Service', PageContent::get('terms', 'head.title'));
        $this->get(route('terms'))->assertDontSee('A heading that has not been saved');
    }

    #[Test]
    public function typing_in_the_form_feeds_the_preview(): void
    {
        $this->seed(PageSeeder::class);
        $page = Page::query()->where('slug', 'about')->sole();
        $this->actingAs($this->editor());

        // set(), not fillForm(): the preview follows the updated hook, which
        // is what a real keystroke-then-blur triggers.
        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->set('data.content.meta.title', 'Draft title')
            ->assertDispatched('preview-changed');

        $this->get(route('admin.preview', 'about'))->assertOk()->assertSee('Draft title', false);
    }

    #[Test]
    public function saving_clears_the_draft(): void
    {
        // Otherwise the preview would keep showing a copy of the page rather
        // than the page, and the two would drift apart silently.
        $this->seed(PageSeeder::class);
        $page = Page::query()->where('slug', 'about')->sole();
        $this->actingAs($this->editor());

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->fillForm(fn (array $state) => ['content' => $state['content']])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull(session(PagePreviewController::key('about')));
    }

    #[Test]
    public function the_editor_shows_the_page_beside_the_form(): void
    {
        $this->seed(PageSeeder::class);
        $page = Page::query()->where('slug', 'home')->sole();
        $this->actingAs($this->editor());

        $html = Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])->html();

        $this->assertStringContainsString('page-editor--split', $html);
        $this->assertStringContainsString(route('admin.preview', 'home'), $html);
    }

    #[Test]
    public function a_page_with_no_view_is_not_previewable(): void
    {
        Page::query()->create(['slug' => 'ghost', 'name' => 'Ghost', 'content' => ['meta' => ['title' => 'x']]]);
        $this->actingAs($this->editor());

        $this->get(route('admin.preview', 'ghost'))->assertNotFound();
    }
}
