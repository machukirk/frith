<?php

namespace Tests\Feature;

use App\Support\HelpSearch;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The help search, after most of the help centre moved into answers.
 *
 * Sixteen topics stopped being links to pages and became disclosures on the
 * help page. The search went on matching labels only, so the words a family
 * would actually type were sitting on the page and finding nothing.
 */
class HelpSearchReachesAnswersTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_finds_a_word_that_appears_only_in_an_answer(): void
    {
        $this->seed(PageSeeder::class);

        // None of these is in a topic label. All are in an answer, which is
        // where a stuck family's words will land.
        foreach (['password', 'delete', 'unsubscribe'] as $word) {
            $this->assertGreaterThan(
                0,
                HelpSearch::run($word)->count(),
                "searching for \"{$word}\" found nothing, though the help page says it",
            );
        }
    }

    #[Test]
    public function a_result_never_shows_the_answer_text_as_its_title(): void
    {
        $this->seed(PageSeeder::class);

        foreach (HelpSearch::run('password') as $result) {
            $this->assertArrayNotHasKey('body', $result, 'the answer is searched, not displayed');
            $this->assertNotSame('', $result['title']);
        }
    }

    #[Test]
    public function every_result_leads_somewhere_that_opens(): void
    {
        $this->seed(PageSeeder::class);

        foreach (['journey', 'block', 'badges', 'connect'] as $word) {
            foreach (HelpSearch::run($word) as $result) {
                $this->assertStringNotContainsString('#', parse_url($result['url'], PHP_URL_PATH) ?? '');
                $this->assertNotSame('#', $result['url'], 'a result must not be a dead anchor');
            }
        }
    }

    #[Test]
    public function a_topic_with_no_label_is_dropped_rather_than_listed_blank(): void
    {
        // A blank row in the admin repeater used to produce a result with an
        // empty title, which looks like a broken link.
        $this->seed(PageSeeder::class);

        foreach (HelpSearch::run('e') as $result) {
            $this->assertNotSame('', trim($result['title']));
        }
    }

    #[Test]
    public function searching_from_the_page_shows_what_it_found(): void
    {
        $this->seed(PageSeeder::class);

        $this->get(route('help', ['q' => 'password']))
            ->assertOk()
            ->assertDontSee('Nothing matches');
    }
}
