<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Support\PageContent;
use App\Support\PolicyPages;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Every public page, held to the same standard.
 *
 * Driven off the seeder's list rather than a list written here, so a page added
 * to the site is a page covered by these tests. Nobody has to remember.
 */
class MarketingPagesTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{0: string}> */
    public static function pages(): array
    {
        return collect(self::routed())
            ->mapWithKeys(fn (string $slug) => [$slug => [$slug]])
            ->all();
    }

    /**
     * The seeder's slugs that are a public page at a URL.
     *
     * Not every editable row is one. The log-in screens, the screen at the end
     * of registration and the site footer all have copy an editor owns and no
     * route of their own, and the footer is not even a page: it has no meta
     * block, because nothing ever puts it in a search result. Everything in
     * this file is about a public page, so it asks which of them are.
     * SitemapController draws exactly the same line, for the same reason.
     * Those three are covered in EditableCopyTest instead.
     *
     * Route::has() would say the same thing and cannot be used: a data
     * provider runs before the application boots, and a facade there throws.
     *
     * @return array<int, string>
     */
    private static function routed(): array
    {
        return collect(PageSeeder::pages())
            ->keys()
            ->reject(fn (string $slug) => in_array($slug, PageSeeder::WITHOUT_A_ROUTE, true))
            ->values()
            ->all();
    }

    #[Test]
    #[DataProvider('pages')]
    public function it_renders(string $slug): void
    {
        $this->assertTrue(Route::has($slug), "no route named {$slug}");

        $this->get(route($slug))
            ->assertOk()
            ->assertSee('</html>', false);
    }

    #[Test]
    #[DataProvider('pages')]
    public function it_has_copy_behind_it(string $slug): void
    {
        $copy = PageContent::for($slug);

        $this->assertNotEmpty($copy, "{$slug} has no content file");
        $this->assertArrayHasKey('meta', $copy, "{$slug} has no meta block");
        $this->assertNotEmpty($copy['meta']['title'] ?? '', "{$slug} has no title");
        $this->assertNotEmpty($copy['meta']['description'] ?? '', "{$slug} has no description");
    }

    #[Test]
    #[DataProvider('pages')]
    public function it_says_what_a_search_result_should_look_like(string $slug): void
    {
        $html = $this->get(route($slug))->getContent();
        $meta = PageContent::get($slug, 'meta');

        $this->assertStringContainsString('<title>'.e($meta['title']).'</title>', $html);
        $this->assertStringContainsString('rel="canonical"', $html);
        $this->assertStringContainsString('property="og:title"', $html);
        $this->assertStringContainsString('name="robots" content="index, follow', $html);
    }

    #[Test]
    #[DataProvider('pages')]
    public function its_words_are_editable_in_the_admin(string $slug): void
    {
        // The whole point of the content layer: what a page says is a row an
        // editor owns, not a string in a Blade file.
        $this->seed(PageSeeder::class);

        $page = Page::query()->where('slug', $slug)->sole();
        $content = $page->content;
        $content['meta']['title'] = 'Edited in the admin panel';
        $page->update(['content' => $content]);

        $this->get(route($slug))->assertSee('Edited in the admin panel', false);
    }

    #[Test]
    public function every_page_is_in_the_sitemap(): void
    {
        $this->seed(PageSeeder::class);

        $xml = $this->get(route('sitemap'))->assertOk()->getContent();

        foreach (self::routed() as $slug) {
            $this->assertStringContainsString('<loc>'.e(route($slug)).'</loc>', $xml, "{$slug} is missing from the sitemap");
        }
    }

    #[Test]
    public function the_policies_all_link_to_each_other(): void
    {
        // Four pages carry the same sidebar. A policy that appears on three of
        // them is the kind of thing nobody notices for a year.
        foreach (array_keys(PolicyPages::PAGES) as $slug) {
            $html = $this->get(route($slug))->assertOk()->getContent();

            foreach (PolicyPages::PAGES as $other => $label) {
                $this->assertStringContainsString(
                    route($other),
                    $html,
                    "{$slug} does not link to {$other}",
                );
            }

            $this->assertStringContainsString('aria-current="page"', $html, "{$slug} does not mark itself current");
        }
    }
}
