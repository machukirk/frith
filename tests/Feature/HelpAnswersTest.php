<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Support\HelpSearch;
use App\Support\PageContent;
use App\Support\SiteNavigation;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The help centre, held to one rule: a topic somebody presses has to do
 * something.
 *
 * Sixteen of the twenty-five links carried `route => null`, which
 * App\Support\SiteNavigation turns into '#', and the search handed back those
 * same sixteen as results. A family looking for how to block somebody, or why
 * nothing was showing near them, pressed the link and the page did not move.
 * They are answered on the page itself now, as a <details> each.
 *
 * The content tests read through PageContent rather than config on purpose.
 * config/content is only the floor, so a test that reads the file would pass
 * while every visitor still saw the broken version.
 */
class HelpAnswersTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = '2026_11_12_120000_answer_the_help_topics_that_had_no_page.php';

    /**
     * Every topic link on the page, whichever card it sits in.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function links(): Collection
    {
        return collect(PageContent::get('help', 'topics', []))
            ->flatMap(fn (array $card) => $card['links'] ?? []);
    }

    #[Test]
    public function every_topic_either_has_a_page_or_an_answer(): void
    {
        $this->seed(PageSeeder::class);

        $this->assertCount(25, $this->links(), 'the help centre has changed size');

        foreach ($this->links() as $link) {
            $this->assertTrue(
                SiteNavigation::url($link) !== '#' || filled($link['answer'] ?? null),
                "the help topic '{$link['label']}' goes nowhere",
            );
        }
    }

    #[Test]
    public function an_answered_topic_is_rendered_as_a_disclosure(): void
    {
        $this->seed(PageSeeder::class);

        $answered = $this->links()->filter(fn (array $link) => filled($link['answer'] ?? null));

        $this->assertCount(16, $answered, 'the number of answered topics has changed');

        $page = $this->get(route('help'))->assertOk();

        foreach ($answered as $link) {
            $page->assertSee('id="'.HelpSearch::anchorFor($link).'"', false)
                ->assertSee($link['label'], false)
                ->assertSee($link['answer'], false);
        }
    }

    #[Test]
    public function a_topic_with_a_page_behind_it_is_still_a_plain_link(): void
    {
        $this->seed(PageSeeder::class);

        $this->get(route('help'))
            ->assertSee('href="'.route('meet-up-safety').'"', false)
            ->assertSee('href="'.route('frith-plus').'"', false);
    }

    #[Test]
    public function no_search_result_is_a_dead_anchor(): void
    {
        $this->seed(PageSeeder::class);

        foreach ($this->links() as $link) {
            $results = HelpSearch::run($link['label']);

            $this->assertNotEmpty($results, "searching for '{$link['label']}' finds nothing");

            foreach ($results as $result) {
                $this->assertNotSame('#', $result['url'], "'{$result['title']}' searches to a dead anchor");
            }
        }
    }

    #[Test]
    public function a_search_result_points_at_the_answer_it_found(): void
    {
        $this->seed(PageSeeder::class);

        $result = HelpSearch::run('Blocking someone')->firstWhere('title', 'Blocking someone');

        $this->assertStringEndsWith('#blocking-someone', $result['url']);
        $this->assertStringContainsString('open=blocking-someone', $result['url']);
    }

    #[Test]
    public function a_search_result_with_a_page_behind_it_still_goes_to_the_page(): void
    {
        $this->seed(PageSeeder::class);

        $this->assertSame(
            route('meet-up-safety'),
            // Not sole(): the search reads the answers too now, so a phrase
            // can legitimately appear inside another topic's answer. Pick the
            // result this test is about by its title.
            HelpSearch::run('Meet-up safety guide')->firstWhere('title', 'Meet-up safety guide')['url'],
        );
    }

    #[Test]
    public function the_answer_a_search_result_points_at_arrives_open(): void
    {
        $this->seed(PageSeeder::class);

        $this->get(HelpSearch::run('Blocking someone')->firstWhere('title', 'Blocking someone')['url'])
            ->assertOk()
            ->assertSee('id="blocking-someone" open>', false)
            // And only that one. Sixteen open accordions is not an answer.
            ->assertDontSee('id="cancelling" open', false)
            ->assertDontSee('id="leaving-frith" open', false);
    }

    #[Test]
    public function following_a_search_result_keeps_the_results_on_screen(): void
    {
        $this->seed(PageSeeder::class);

        // Several topics match, and the search a family ran is carried
        // through, so reading one answer does not throw away the rest.
        //
        // The count is read rather than typed: the search reads the answers
        // too now, so how many match "connect" is a fact about the copy and
        // changes whenever somebody edits it. What must hold is that the
        // number on screen is the number found.
        $results = HelpSearch::run('connect');

        $this->assertGreaterThan(1, $results->count());

        $this->get($results->firstWhere('title', 'Asking to connect')['url'])
            ->assertOk()
            ->assertSee('search-results', false)
            ->assertSee($results->count().' results for')
            ->assertSee('id="asking-to-connect" open>', false);
    }

    #[Test]
    public function nothing_opens_itself_without_being_asked(): void
    {
        $this->seed(PageSeeder::class);

        $this->get(route('help'))->assertOk()->assertDontSee(' open>', false);
    }

    #[Test]
    public function the_migration_puts_the_answers_into_the_stored_page(): void
    {
        $this->legacyHelpPage();

        // The page as it was: the link is there and does nothing.
        $this->get(route('help'))->assertOk()->assertDontSee('id="blocking-someone"', false);

        $this->migration()->up();

        $stored = $this->storedLinks();

        $this->assertSame('blocking-someone', $stored['Blocking someone']['anchor']);
        $this->assertStringContainsString('It needs no reason', $stored['Blocking someone']['answer']);
        $this->assertSame('nothing-is-showing-near-me', $stored['Nothing is showing near me']['anchor']);

        $this->get(route('help'))
            ->assertOk()
            ->assertSee('id="blocking-someone"', false)
            ->assertSee('It needs no reason', false);
    }

    #[Test]
    public function the_migration_leaves_an_editors_own_work_alone(): void
    {
        $page = $this->legacyHelpPage();

        $this->editStoredLink($page, 'Blocking someone', fn (array $link) => [
            ...$link,
            'label' => 'Blocking a family',
        ]);

        $this->editStoredLink($page, 'Something looks broken', fn (array $link) => [
            ...$link,
            'route' => 'reporting',
        ]);

        $this->migration()->up();

        $stored = $this->storedLinks();

        // Renamed, so it is no longer the link that shipped.
        $this->assertArrayNotHasKey('answer', $stored['Blocking a family']);

        // Pointed at a real page, which is a better answer than ours.
        $this->assertSame('reporting', $stored['Something looks broken']['route']);
        $this->assertArrayNotHasKey('answer', $stored['Something looks broken']);

        // The untouched ones still get theirs.
        $this->assertArrayHasKey('answer', $stored['Leaving Frith']);
    }

    #[Test]
    public function rolling_the_migration_back_restores_the_links(): void
    {
        $page = $this->legacyHelpPage();

        $this->migration()->up();
        $this->migration()->down();

        $this->assertSame(
            $page->refresh()->content['topics'],
            $this->storedPage()->content['topics'],
        );
    }

    #[Test]
    public function rolling_back_does_not_take_an_editors_rewrite_with_it(): void
    {
        $page = $this->legacyHelpPage();

        $this->migration()->up();

        $this->editStoredLink($page->refresh(), 'Cancelling', fn (array $link) => [
            ...$link,
            'answer' => 'Nothing to cancel. Ring us if you are stuck.',
        ]);

        $this->migration()->down();

        $this->assertSame(
            'Nothing to cancel. Ring us if you are stuck.',
            $this->storedLinks()['Cancelling']['answer'],
        );
    }

    /**
     * The migration, loaded once.
     *
     * The file returns an anonymous class instance, and require caches by
     * path, so asking a second time in the same run hands back true instead.
     */
    private function migration(): object
    {
        static $migration;

        return $migration ??= require database_path('migrations/'.self::MIGRATION);
    }

    /** The help page as it was stored before any of this: no answers. */
    private function legacyHelpPage(): Page
    {
        $content = config('frith-content.help');

        foreach ($content['topics'] as $card => $topic) {
            foreach ($topic['links'] as $i => $link) {
                if (filled($link['answer'] ?? null)) {
                    $content['topics'][$card]['links'][$i] = ['label' => $link['label'], 'route' => null];
                }
            }
        }

        return Page::query()->create([
            'slug' => 'help',
            'name' => 'Help centre',
            'content' => $content,
        ]);
    }

    private function storedPage(): Page
    {
        return Page::query()->where('slug', 'help')->sole();
    }

    /** @return Collection<string, array<string, mixed>> */
    private function storedLinks(): Collection
    {
        return collect($this->storedPage()->content['topics'])
            ->flatMap(fn (array $card) => $card['links'])
            ->keyBy('label');
    }

    /** Stands in for somebody editing one link in the admin panel. */
    private function editStoredLink(Page $page, string $label, callable $change): void
    {
        $content = $page->content;

        foreach ($content['topics'] as $card => $topic) {
            foreach ($topic['links'] as $i => $link) {
                if (($link['label'] ?? null) === $label) {
                    $content['topics'][$card]['links'][$i] = $change($link);
                }
            }
        }

        $page->forceFill(['content' => $content])->save();
    }
}
