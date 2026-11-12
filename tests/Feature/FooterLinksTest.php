<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The links in the header, the phone menu and the footer.
 *
 * SiteNavigation::url() answers '#' for a route that is not registered, which
 * keeps a half-built sitemap from taking the site down. The cost is that a
 * mistake is completely silent: the page renders, the link is there, and it
 * goes nowhere. Blog and Cookies sat in the footer like that, on every page of
 * the site and on the error pages too.
 *
 * So the safety net stays and this is what makes falling into it loud.
 */
class FooterLinksTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every route named anywhere in the nav config.
     *
     * Read from the file rather than through config(), because a data provider
     * runs before the application is booted.
     *
     * @return array<string, array{0: string}>
     */
    public static function navRoutes(): array
    {
        $nav = require __DIR__.'/../../config/frith-nav.php';

        $names = [];

        // Only leaves are visited, and 'route' => 'home' is a leaf.
        array_walk_recursive($nav, function ($value, $key) use (&$names) {
            if ($key === 'route') {
                $names[$value] = [$value];
            }
        });

        return $names;
    }

    #[Test]
    #[DataProvider('navRoutes')]
    public function every_link_in_the_chrome_has_a_route_behind_it(string $name): void
    {
        $this->assertTrue(
            Route::has($name),
            "config/frith-nav.php offers '{$name}', which has no route, so it renders as a dead anchor on every page.",
        );
    }

    #[Test]
    public function nothing_on_a_public_page_renders_as_a_dead_anchor(): void
    {
        // "#main" is the skip link and is a real target, so the quote matters.
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('href="#"', false);
    }

    #[Test]
    public function the_footer_no_longer_offers_a_blog_that_was_never_written(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('>Blog<', false);
    }

    #[Test]
    public function the_legal_column_links_to_the_cookies_page(): void
    {
        // The one people go looking for, and the one that was dead longest.
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('href="'.route('cookies').'"', false);
    }
}
