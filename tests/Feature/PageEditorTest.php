<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The page editor, held to one rule above all others.
 *
 * Opening a page and pressing Save without typing anything must leave it
 * exactly as it was. The first version of this form was hand-written for the
 * home page's shape, so doing that to any other page replaced its content with
 * the home page's empty fields — the Terms page was one Save away from being
 * blank, and nothing would have said so.
 */
class PageEditorTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{0: string}> */
    public static function pages(): array
    {
        return collect(PageSeeder::pages())
            ->keys()
            ->mapWithKeys(fn (string $slug) => [$slug => [$slug]])
            ->all();
    }

    private function open(string $slug): Page
    {
        $this->seed(PageSeeder::class);
        $this->actingAs(User::factory()->create(['role' => UserRole::Editor]));

        return Page::query()->where('slug', $slug)->sole();
    }

    #[Test]
    #[DataProvider('pages')]
    public function saving_it_untouched_changes_nothing(string $slug): void
    {
        $page = $this->open($slug);
        $before = $page->content;

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->assertOk()
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(
            json_decode(json_encode($this->sorted($before)), true),
            json_decode(json_encode($this->sorted($page->refresh()->content)), true),
            "saving {$slug} without editing it changed its content",
        );
    }

    #[Test]
    #[DataProvider('pages')]
    public function its_editor_is_built_from_its_own_shape(string $slug): void
    {
        // A tab per section, named after it. If the editor were built from
        // another page's shape this is what would give it away.
        $page = $this->open($slug);

        $html = Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])->html();

        // One tab per top-level section, and no tab for a section this page
        // does not have — which is exactly what went wrong before.
        $this->assertSame(
            count($page->content),
            substr_count($html, 'fi-tabs-item-label'),
            "{$slug}'s editor does not have one tab per section",
        );

        // And it is this page's sections, not another page's.
        $this->assertStringContainsString('Search &amp; sharing', $html);
    }

    #[Test]
    public function a_page_never_gets_another_pages_fields(): void
    {
        // The specific failure this replaces: the Terms editor showing the home
        // page's hero, pillars and Journeys, and writing them over Terms.
        $page = $this->open('terms');

        $html = Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])->html();

        foreach (['content.hero', 'content.pillars', 'content.journeys', 'content.visibility'] as $foreign) {
            $this->assertStringNotContainsString($foreign, $html, "the Terms editor is offering {$foreign}");
        }
    }

    #[Test]
    public function an_edit_reaches_the_page(): void
    {
        $page = $this->open('terms');

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->fillForm(fn (array $state) => [
                'content' => [
                    ...$state['content'],
                    'head' => [...$state['content']['head'], 'title' => 'Terms, rewritten'],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get(route('terms'))->assertSee('Terms, rewritten');
    }

    /** Key order is not content, so it is not what this is comparing. */
    private function sorted(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $value = array_map(fn ($child) => $this->sorted($child), $value);

        if (! array_is_list($value)) {
            ksort($value);
        }

        return $value;
    }
}
