<?php

namespace Tests\Feature;

use App\Models\Journey;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * What /frith-plus is allowed to say.
 *
 * The page sells a plan that is not open yet, which is fine, and it used to
 * sell three things the code does not do, which is not. Each test here pairs
 * a sentence that was on the page with the code that proves it was wrong, so
 * putting the sentence back fails rather than ships.
 */
class FrithPlusClaimsTest extends TestCase
{
    use RefreshDatabase;

    private function family(string $email = 'sarah@example.com', string $name = 'Fun House'): Registration
    {
        return Registration::query()->create([
            'email' => $email,
            'first_name' => 'Sarah',
            'display_name' => $name,
            'postcode_outcode' => 'TN13',
            'email_verified_at' => now(),
            'consent_version' => config('frith.consent.version'),
            'consent_text' => config('frith.consent.text'),
            'consented_at' => now(),
        ]);
    }

    #[Test]
    public function the_one_button_on_the_page_goes_somewhere(): void
    {
        // It pointed at frith-plus.choose, which has never been registered,
        // so SiteNavigation::url() answered '#' and the only control on the
        // page that sells the paid plan did nothing.
        $this->get(route('frith-plus'))
            ->assertOk()
            ->assertDontSee('href="#"', false)
            ->assertSee('href="'.route('register.start').'"', false);
    }

    #[Test]
    public function it_says_plainly_that_there_is_nothing_to_buy_yet(): void
    {
        $this->get(route('frith-plus'))
            ->assertOk()
            ->assertSee('Frith+ is not open yet');
    }

    #[Test]
    public function it_does_not_sell_specialist_journeys(): void
    {
        // There is no such thing to withhold. A Journey has a slug, a title,
        // a blurb, a position and an archived_at, and nothing that could mark
        // one as specialist or paid.
        $columns = DB::getSchemaBuilder()->getColumnListing('journeys');

        foreach (['tier', 'plus', 'specialist', 'plan'] as $gate) {
            $this->assertNotContains($gate, $columns);
        }

        $html = $this->get(route('frith-plus'))->assertOk()->getContent();

        $this->assertStringNotContainsStringIgnoringCase('specialist', $html);
    }

    #[Test]
    public function it_does_not_sell_a_count_that_every_family_already_sees(): void
    {
        $html = $this->get(route('frith-plus'))->assertOk()->getContent();

        $this->assertStringNotContainsStringIgnoringCase('things you have in common', $html);
    }

    #[Test]
    public function the_things_in_common_count_is_shown_to_a_family_with_no_membership(): void
    {
        // The proof behind the test above. Both of these are free families:
        // hasPlus() is founder_number, and neither has one.
        $journey = Journey::query()->create([
            'slug' => 'sleep',
            'title' => 'Sleep',
            'blurb' => 'Families who are not getting much.',
            'position' => 1,
        ]);

        $mine = $this->family();
        $theirs = $this->family('priya@example.com', 'The Shahs');

        foreach ([$mine, $theirs] as $family) {
            $family->journeys()->attach($journey->id, ['joined_at' => now()]);
        }

        $this->assertFalse($mine->hasPlus(), 'this family must not have Frith+ for the test to mean anything');

        $this->actingAs($mine, 'founder')
            ->get(route('journeys.show', $journey))
            ->assertOk()
            ->assertSee('things in common');
    }

    #[Test]
    public function it_only_offers_the_notifications_the_code_really_holds_back(): void
    {
        // Whatever the page lists has to be enforced somewhere. Today the only
        // enforcement is NotificationSettings, driven by the 'plus' flags in
        // this config, so the page may not promise more than these.
        $gated = collect(config('frith-notifications'))
            ->except('pace')
            ->filter(fn (array $switch) => $switch['plus'])
            ->keys();

        $this->assertSame(['any_asks', 'journeys'], $gated->all());

        // The fourth thing the plan holds back is the pace of those emails,
        // which is not a switch. AccountNotificationController only writes
        // request_pace behind a hasPlus() check, so it counts.
        $this->assertStringContainsString(
            '$family->hasPlus() && $request->filled(\'pace\')',
            file_get_contents(app_path('Http/Controllers/AccountNotificationController.php')),
            'the page offers the pace of the emails as a Frith+ thing, so something has to gate it',
        );

        // Counted off the rendered page, not off the config floor. A stored
        // row wins over config, so counting the config would pass while a
        // visitor read a longer list.
        $html = $this->get(route('frith-plus'))->assertOk()->getContent();

        $this->assertSame(
            $gated->count() + 1,
            substr_count($html, 'class="benefit-list__item"'),
            'one item per gated switch, plus the pace of the emails, and nothing else',
        );
    }

    #[Test]
    public function the_search_snippet_makes_the_same_promise_the_page_does(): void
    {
        // The meta description is the version of this page most people read,
        // and it went on saying Frith+ "gives you more ways to find and
        // connect with families" after the page stopped.
        $html = $this->get(route('frith-plus'))->assertOk()->getContent();

        preg_match('/<meta name="description" content="([^"]*)"/', $html, $found);

        $this->assertNotEmpty($found, 'the page has no meta description');
        $this->assertStringNotContainsString('more ways to find and connect', $found[1]);
        $this->assertStringContainsString('not open yet', $found[1]);
    }

    #[Test]
    public function a_stored_row_from_before_this_release_is_reworded_too(): void
    {
        // config/content is only the floor. Without the migration a site with
        // a stored row would go on selling the old claims for ever.
        DB::table('pages')->insert([
            'slug' => 'frith-plus',
            'name' => 'Frith+',
            'content' => json_encode([
                'benefits' => [
                    'label' => 'With Frith+ you can:',
                    'items' => [
                        ['title' => 'Explore more families', 'detail' => '- look beyond your Frith suggestions.'],
                        ['title' => 'Spot stronger matches', 'detail' => '- see how many things you have in common as you browse.'],
                        ['title' => 'Join more Journeys', 'detail' => '- access specialist Journeys.'],
                        ['title' => 'See who wants to connect', 'detail' => '- view requests and choose who to connect with.'],
                    ],
                ],
                'cta' => ['button' => 'Explore Frith+'],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // `require` re-executes, so this is a fresh instance of the anonymous
        // class rather than the one the migrator already ran.
        $migration = require database_path('migrations/2026_11_12_140000_put_the_cookies_page_in_and_make_frith_plus_honest.php');

        $migration->up();

        $html = $this->get(route('frith-plus'))->assertOk()->getContent();

        $this->assertStringNotContainsStringIgnoringCase('specialist', $html);
        $this->assertStringNotContainsStringIgnoringCase('things you have in common', $html);
        $this->assertStringNotContainsString('Explore Frith+', $html);
    }

    #[Test]
    public function an_editors_own_wording_is_never_reverted(): void
    {
        DB::table('pages')->insert([
            'slug' => 'frith-plus',
            'name' => 'Frith+',
            'content' => json_encode(['cta' => ['button' => 'Our own words']]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_11_12_140000_put_the_cookies_page_in_and_make_frith_plus_honest.php');

        $migration->up();

        $stored = json_decode(DB::table('pages')->where('slug', 'frith-plus')->value('content'), true);

        $this->assertSame('Our own words', $stored['cta']['button']);
    }
}
