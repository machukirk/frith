<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Registration;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * What the last screen of registration tells a family who came late.
 *
 * Only the first hundred registrations are issued a founder number. After
 * that assignFounderNumber() returns without writing one, and the family is
 * perfectly registered with no number against them. /join/welcome used to
 * tell all of them they were a First Frith Family and promise them Frith+ for
 * good, which hasPlus() would then refuse, while the very next screen in the
 * same flow quietly disagreed.
 *
 * The cap is settled and the badge means what it has always meant. These are
 * about the words.
 */
class WelcomeAfterTheCapTest extends TestCase
{
    use RefreshDatabase;

    /** Through the real flow, because the number is written on the way past. */
    private function register(string $email): Registration
    {
        $this->post(route('register.step.store', 'you'), [
            'first_name' => 'Sam',
            'email' => $email,
            'postcode_outcode' => 'SS9',
        ]);

        return Registration::query()->where('email', $email)->sole();
    }

    /** One number issued, so the second family registering is past the cap. */
    private function registerPastTheCap(): Registration
    {
        config(['frith.founders.limit' => 1]);

        $this->register('first@example.com');
        $this->flushSession();

        $second = $this->register('second@example.com');

        $this->assertNull($second->founder_number, 'the second family was issued a number');

        return $second;
    }

    #[Test]
    public function a_family_past_the_cap_is_not_told_it_is_a_first_frith_family(): void
    {
        $this->registerPastTheCap();

        $this->get(route('register.welcome'))
            ->assertOk()
            ->assertDontSee('First Frith Family')
            ->assertSee(config('frith-content.join.welcome.title_if_not_a_founder'));
    }

    #[Test]
    public function a_family_past_the_cap_is_promised_nothing_frith_plus_will_refuse(): void
    {
        // hasPlus() is isFounder(), so the lifetime promise belongs only to a
        // family with a number. Everybody else gets the thing that is true:
        // the basics are free and stay free.
        $registration = $this->registerPastTheCap();

        $this->assertFalse($registration->hasPlus());

        $this->get(route('register.welcome'))
            ->assertOk()
            ->assertDontSee(config('frith-content.join.welcome.footnote'))
            ->assertSee(config('frith-content.join.welcome.footnote_if_not_a_founder'));
    }

    #[Test]
    public function a_founder_is_still_told_so(): void
    {
        $registration = $this->register('first@example.com');

        $this->assertSame(1, $registration->founder_number);

        $this->get(route('register.welcome'))
            ->assertOk()
            ->assertSee(config('frith-content.join.welcome.title'))
            ->assertSee(config('frith-content.join.welcome.footnote'))
            ->assertDontSee(config('frith-content.join.welcome.title_if_not_a_founder'));
    }

    #[Test]
    public function the_browser_tab_says_the_same_thing_the_heading_does(): void
    {
        // The h1 was guarded and the <title> was not for long enough to be
        // worth a test of its own. Both come from the same column.
        $this->registerPastTheCap();

        $html = $this->get(route('register.welcome'))->assertOk()->getContent();

        $this->assertStringContainsString(
            '<title>'.e(config('frith-content.join.meta.title_if_not_a_founder')).'</title>',
            $html,
        );
    }

    #[Test]
    public function the_two_screens_in_the_flow_agree_about_the_same_family(): void
    {
        // This is the bug underneath the copy. register/verified.blade.php has
        // always guarded on founder_number. /join/welcome did not, so one
        // family walked through two screens that said different things about
        // them, minutes apart.
        $registration = $this->registerPastTheCap();

        $this->get(route('register.welcome'))
            ->assertOk()
            ->assertDontSee('First Frith Family');

        $this->get(URL::signedRoute('register.verify', ['registration' => $registration->public_id]))
            ->assertOk()
            ->assertDontSee('Frith Founder');
    }

    #[Test]
    public function both_endings_are_editable(): void
    {
        $this->seed(PageSeeder::class);

        $page = Page::query()->where('slug', 'join')->sole();
        $content = $page->content;
        $content['welcome']['title'] = 'The founder ending, rewritten';
        $content['welcome']['title_if_not_a_founder'] = 'The other ending, rewritten';
        $page->update(['content' => $content]);

        config(['frith.founders.limit' => 1]);

        $this->register('first@example.com');
        $this->get(route('register.welcome'))
            ->assertOk()
            ->assertSee('The founder ending, rewritten');

        $this->flushSession();

        $this->register('second@example.com');
        $this->get(route('register.welcome'))
            ->assertOk()
            ->assertSee('The other ending, rewritten');
    }
}
