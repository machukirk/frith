<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Support\PageContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * /cookies, which the footer has been linking to since before it existed.
 *
 * A legal page is only worth having if it is true, so most of this holds the
 * page against the code it describes rather than against itself. If somebody
 * renames the session cookie or adds a third party script, these fail.
 */
class CookiesPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renders_with_the_house_title(): void
    {
        $this->get(route('cookies'))
            ->assertOk()
            ->assertSee('<title>Cookies · Frith</title>', false)
            ->assertSee('Cookies');
    }

    #[Test]
    public function it_names_every_cookie_the_app_really_sets(): void
    {
        $html = $this->get(route('cookies'))->assertOk()->getContent();

        // The session cookie, read from the config that names it rather than
        // typed again here. Renaming it in config/session.php without
        // rewriting the page is exactly the drift this catches.
        $this->assertStringContainsString(
            (string) config('session.cookie'),
            $html,
            'the page does not name the session cookie the app actually sets',
        );

        // Laravel's CSRF middleware sets this on the way out.
        $this->assertStringContainsString('XSRF-TOKEN', $html);

        // Both login paths call login($family, remember: true), so the guard
        // queues a recaller. Its full name carries a hash of the guard class;
        // the prefix is the part a person can recognise in their browser.
        $this->assertStringContainsString('remember_founder', $html);
    }

    #[Test]
    public function the_claim_that_nothing_third_party_is_loaded_holds(): void
    {
        // The page says no third party is loaded, so there is nothing to
        // watch you. That is a promise about the layout, not about this page,
        // so the home page is the one to check.
        $html = $this->get(route('home'))->assertOk()->getContent();

        preg_match_all(
            '/<(?:script|link|img|iframe)[^>]*\b(?:src|href)="(https?:\/\/[^"]+)"/i',
            $html,
            $matches,
        );

        foreach ($matches[1] as $url) {
            $this->assertStringStartsWith(
                url('/'),
                $url,
                "{$url} is loaded from another company, which the Cookies page says nothing is.",
            );
        }
    }

    #[Test]
    public function its_last_updated_line_comes_from_its_own_row(): void
    {
        // The same rule as the other policies: generated from pages.updated_at,
        // never typed into the copy where it would go stale on the first edit.
        Page::query()->where('slug', 'cookies')->update([
            'updated_at' => Carbon::parse('2027-03-03 09:00:00'),
        ]);

        $this->get(route('cookies'))
            ->assertOk()
            ->assertSee('Last updated 3 March 2027')
            ->assertDontSee('minutes to read');
    }

    #[Test]
    public function it_sends_people_on_to_the_privacy_policy(): void
    {
        $this->get(route('cookies'))
            ->assertOk()
            ->assertSee('href="'.route('privacy').'"', false);
    }

    #[Test]
    public function it_carries_the_other_policies_beside_it(): void
    {
        $html = $this->get(route('cookies'))->assertOk()->getContent();

        foreach (['terms', 'privacy', 'community-guidelines'] as $policy) {
            $this->assertStringContainsString(route($policy), $html);
        }
    }

    #[Test]
    public function it_has_a_stored_row_so_an_editor_can_reach_it(): void
    {
        // Put there by the migration rather than the seeder, because seeding
        // is opt in on a deploy and most releases do not run it.
        $this->assertDatabaseHas('pages', ['slug' => 'cookies']);
    }

    #[Test]
    public function its_words_are_editable_in_the_admin(): void
    {
        $page = Page::query()->where('slug', 'cookies')->sole();

        $content = $page->content;
        $content['head']['title'] = 'Edited in the admin panel';
        $page->update(['content' => $content]);

        $this->get(route('cookies'))->assertSee('Edited in the admin panel', false);
    }

    #[Test]
    public function a_key_nobody_has_edited_still_falls_back_to_the_config_floor(): void
    {
        Page::query()->where('slug', 'cookies')->delete();

        Page::query()->create([
            'slug' => 'cookies',
            'name' => 'Cookies',
            'content' => ['head' => ['title' => 'Only this key is set']],
        ]);

        $copy = PageContent::for('cookies');

        $this->assertSame('Only this key is set', $copy['head']['title']);
        $this->assertCount(3, $copy['cookies']['rows'], 'the table should still come from config');

        $this->get(route('cookies'))->assertOk();
    }
}
