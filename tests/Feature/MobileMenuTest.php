<?php

namespace Tests\Feature;

use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The phone menu. It is a native <details>, so these assertions are about what
 * is in the markup rather than about what a script does with it.
 */
class MobileMenuTest extends TestCase
{
    use RefreshDatabase;

    private function founder(): Registration
    {
        return Registration::query()->create([
            'email' => 'sarah@example.com',
            'first_name' => 'Sarah',
            'postcode_outcode' => 'TN13',
            'founder_number' => 1,
            'email_verified_at' => now(),
            'consent_version' => config('frith.consent.version'),
            'consent_text' => config('frith.consent.text'),
            'consented_at' => now(),
        ]);
    }

    #[Test]
    public function it_opens_without_any_javascript(): void
    {
        // The whole point of the disclosure. If this ever becomes a scripted
        // panel, a phone with a blocked script loses the navigation entirely.
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('<details class="menu__disclosure"', $html);
        $this->assertStringContainsString('<summary class="menu__toggle"', $html);
        $this->assertStringNotContainsString('menu__panel" hidden', $html);
    }

    #[Test]
    public function the_wordmark_goes_home_rather_than_opening_the_menu(): void
    {
        // It used to sit inside the <summary>, which made the whole bar the
        // toggle: tapping the logo opened the menu instead of going home.
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<a class="menu__home" href="'.preg_quote(route('home'), '/').'"/',
            $html,
        );

        // And it is outside the control, not within it.
        $summary = mb_substr($html, mb_strpos($html, '<summary class="menu__toggle"'));
        $summary = mb_substr($summary, 0, mb_strpos($summary, '</summary>'));

        $this->assertStringNotContainsString('menu__home', $summary);
        $this->assertStringNotContainsString('<img', $summary);
    }

    #[Test]
    public function a_visitor_sees_the_way_in(): void
    {
        $response = $this->get(route('home'))->assertOk();

        foreach (['Home', 'How it works', 'Journeys', 'Safety', 'Frith+'] as $label) {
            $response->assertSee($label);
        }

        $response->assertSee('Nine groups of families')
            ->assertSee(route('register.start'), false)
            ->assertSee(route('login'), false);

        $html = $response->getContent();

        $this->assertStringContainsString('menu__cta', $html);
        $this->assertStringNotContainsString('menu__profile', $html);
        $this->assertStringNotContainsString('menu__panel--founder', $html);
    }

    #[Test]
    public function a_family_who_is_logged_in_sees_their_own_menu(): void
    {
        $this->actingAs($this->founder(), 'founder');

        $response = $this->get(route('home'))->assertOk();
        $html = $response->getContent();

        $response->assertSee('Sarah')
            ->assertSee('TN13 area')
            ->assertSee('Your village')
            ->assertSee('Messages')
            ->assertSee('Log out');

        $this->assertStringContainsString('menu__panel--founder', $html);
        $this->assertStringContainsString('>S<', $html, 'the avatar carries their initial');

        // Nobody who is already in needs telling to register.
        $this->assertStringNotContainsString('menu__cta', $html);
    }

    #[Test]
    public function the_page_you_are_on_is_marked_rather_than_linked_away_from(): void
    {
        // Said with aria-current, so it is not colour alone carrying it.
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/href="'.preg_quote(route('home'), '/').'"[^>]*aria-current="page"/',
            $html,
        );
    }

    #[Test]
    public function the_counts_the_design_shows_are_left_out_until_they_are_true(): void
    {
        // The hi-fi puts "2 joined" under Journeys and "1 new" under Messages.
        // Neither feature exists, and a menu that says "1 new" when nothing is
        // new is worse than one that says nothing.
        $this->actingAs($this->founder(), 'founder');

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('2 joined')
            ->assertDontSee('1 new');
    }
}
