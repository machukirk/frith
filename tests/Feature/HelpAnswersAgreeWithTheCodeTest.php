<?php

namespace Tests\Feature;

use App\Http\Controllers\ConnectionController;
use App\Support\HelpSearch;
use App\Support\PageContent;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The help centre against the code it describes.
 *
 * The answers on /help are written by hand from what the code does, which
 * means they are only true until somebody changes the code. That happened
 * once already: "The 10-request limit" was answered with "There is no limit",
 * correctly, and then the cap was built in the same week and the answer
 * shipped as a lie to the one family most likely to read it.
 *
 * So the facts a visitor is told are asserted against the things that decide
 * them, rather than against a copy of the words. A test that only checked the
 * sentence would have gone green on the wrong sentence.
 *
 * Read through PageContent, not config, because config is the floor and the
 * stored page is what a visitor is actually served.
 */
class HelpAnswersAgreeWithTheCodeTest extends TestCase
{
    use RefreshDatabase;

    /** @return Collection<string, array<string, mixed>> */
    private function links(): Collection
    {
        return collect(PageContent::get('help', 'topics', []))
            ->flatMap(fn (array $card) => $card['links'] ?? [])
            ->keyBy('label');
    }

    #[Test]
    public function the_request_limit_answer_says_the_number_the_code_enforces(): void
    {
        $this->seed(PageSeeder::class);

        $answer = $this->links()['The 10-request limit']['answer'] ?? '';

        $this->assertStringContainsString(
            (string) ConnectionController::PENDING_LIMIT,
            $this->links()['The 10-request limit']['label'],
            'the help label and the cap have drifted apart',
        );

        // The sentence that shipped. Pinned so that a future rewrite which
        // reintroduces it fails here rather than on a family's screen.
        $this->assertStringNotContainsStringIgnoringCase(
            'there is no limit',
            $answer,
            'the help centre denies a limit the code enforces',
        );

        $this->assertStringNotContainsStringIgnoringCase('nothing stops you at ten', $answer);
    }

    #[Test]
    public function a_family_at_the_limit_is_told_the_same_thing_the_help_page_says(): void
    {
        $this->seed(PageSeeder::class);

        $answer = $this->links()['The 10-request limit']['answer'] ?? '';

        // Both halves of the real rule, because either on its own misleads:
        // the cap is on asks waiting rather than asks ever made, and the way
        // out of it is to withdraw one from the Requests screen.
        $this->assertStringContainsStringIgnoringCase('waiting for a reply', $answer);
        $this->assertStringContainsStringIgnoringCase('requests screen', $answer);
    }

    #[Test]
    public function every_word_the_search_box_suggests_finds_something(): void
    {
        $this->seed(PageSeeder::class);

        $placeholder = PageContent::get('help', 'head.search_placeholder', '');

        preg_match_all('/"([^"]+)"/', $placeholder, $matches);

        $this->assertNotEmpty($matches[1], 'the search placeholder offers no example words');

        foreach ($matches[1] as $word) {
            $this->assertNotEmpty(
                HelpSearch::run($word),
                "the search box suggests '{$word}' and it finds nothing",
            );
        }
    }

    #[Test]
    public function the_correction_reaches_the_stored_page_and_goes_back_cleanly(): void
    {
        $this->seed(PageSeeder::class);

        $migration = require database_path(
            'migrations/2026_11_12_150000_say_what_the_request_limit_actually_is.php',
        );

        // The seeder writes config, which is already correct, so roll back
        // first to get the words a server that has only run 120000 holds.
        $migration->down();

        $this->get(route('help'))->assertOk()->assertSee('There is no limit', false);

        $migration->up();

        $this->get(route('help'))
            ->assertOk()
            ->assertDontSee('There is no limit', false)
            ->assertSee('not the asks you have ever made', false);
    }

    #[Test]
    public function the_answers_a_visitor_is_served_carry_no_dashes(): void
    {
        $this->seed(PageSeeder::class);

        foreach ($this->links() as $label => $link) {
            foreach (["\u{2014}", "\u{2013}"] as $dash) {
                $this->assertStringNotContainsString($dash, $link['answer'] ?? '', "'{$label}' carries a dash");
                $this->assertStringNotContainsString($dash, (string) $label);
            }
        }
    }
}
