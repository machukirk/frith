<?php

namespace Tests\Feature;

use App\Http\Controllers\ConnectionController;
use App\Support\PageContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The numbers /how-it-works puts in front of a visitor.
 *
 * A promise with a number in it is the kind most easily broken by a change
 * somewhere else, because nothing connects the sentence to the code. The page
 * said "Up to 10 open requests at a time" for a while when nothing anywhere
 * enforced a cap at all.
 */
class HowItWorksClaimsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_cap_on_open_requests_is_the_one_the_page_promises(): void
    {
        $note = collect(PageContent::get('how-it-works', 'steps'))
            ->pluck('note')
            ->filter()
            ->first(fn (string $note) => str_contains($note, 'open requests'));

        $this->assertNotNull($note, '/how-it-works no longer explains the cap on open requests');

        $this->assertStringContainsString(
            'Up to '.ConnectionController::PENDING_LIMIT.' open requests',
            $note,
            'the page and the code disagree about how many asks a family may have waiting',
        );
    }

    #[Test]
    public function the_page_still_says_it(): void
    {
        $this->get(route('how-it-works'))
            ->assertOk()
            ->assertSee('Up to '.ConnectionController::PENDING_LIMIT.' open requests');
    }
}
