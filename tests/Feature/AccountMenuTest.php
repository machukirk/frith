<?php

namespace Tests\Feature;

use App\Models\Block;
use App\Models\Connection;
use App\Models\Registration;
use App\Support\AccountMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The menu behind the avatar in the desktop header.
 *
 * The header used to carry the family's name as a plain link and nothing
 * else. The designs put a menu there, and two of its rows carry a number that
 * is only true at the moment it is read.
 */
class AccountMenuTest extends TestCase
{
    use RefreshDatabase;

    private function family(string $name, array $overrides = []): Registration
    {
        return Registration::query()->create(array_merge([
            'email' => Str::slug($name).'@example.com',
            'first_name' => $name,
            'display_name' => $name,
            'postcode_outcode' => 'SS9',
            'email_verified_at' => now(),
            'completed_at' => now(),
            'consent_version' => config('frith.consent.version'),
            'consent_text' => config('frith.consent.text'),
            'consented_at' => now(),
        ], $overrides));
    }

    private function asks(Registration $from, Registration $to): Connection
    {
        return Connection::query()->create([
            'requester_id' => $from->id,
            'addressee_id' => $to->id,
            'status' => Connection::PENDING,
        ]);
    }

    #[Test]
    public function a_visitor_gets_no_account_menu(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('account-menu', false)
            ->assertSee(route('login'), false);
    }

    #[Test]
    public function a_family_gets_the_menu_with_their_name_and_area(): void
    {
        $family = $this->family('Fun House');

        $this->actingAs($family, 'founder')
            ->get(route('home'))
            ->assertOk()
            ->assertSee('account-menu', false)
            ->assertSee('Fun House')
            ->assertSee('Leigh-on-Sea area');
    }

    #[Test]
    public function it_opens_without_javascript(): void
    {
        // A scripted dropdown would be a menu some people could not open at
        // all. There is no JavaScript on this site, so it has to be a native
        // disclosure, which a keyboard and a screen reader already understand.
        $family = $this->family('Fun House');

        $html = $this->actingAs($family, 'founder')->get(route('home'))->getContent();

        $this->assertStringContainsString('<details class="account-menu"', $html);
        $this->assertStringContainsString('account-menu__toggle', $html);

        // The structured-data block is a <script> and is not JavaScript, so
        // the assertion is about executable script specifically rather than
        // about the tag.
        preg_match_all('/<script\b(?![^>]*type="application\/ld\+json")[^>]*>/i', $html, $scripts);

        $this->assertSame([], $scripts[0], 'the header must work with no JavaScript at all');
    }

    #[Test]
    public function the_requests_row_says_how_many_are_waiting(): void
    {
        $me = $this->family('Fun House');
        $one = $this->family('The Ahmeds');
        $two = $this->family('The Okonkwos');

        $this->assertNull(AccountMenu::waiting($me));

        $this->asks($one, $me);
        $this->assertSame('1 family is waiting for you', AccountMenu::waiting($me->fresh()));

        $this->asks($two, $me);
        $this->assertSame('2 families are waiting for you', AccountMenu::waiting($me->fresh()));
    }

    #[Test]
    public function nobody_waiting_means_no_line_rather_than_a_zero(): void
    {
        // "0 waiting for you" is a worse sentence than no sentence, and this
        // product says twice over that it does not do counts of what you have
        // missed.
        $me = $this->family('Fun House');

        $rows = AccountMenu::rows($me);

        $this->assertNull($rows->firstWhere('label', 'Requests')['meta']);
    }

    #[Test]
    public function a_request_from_a_blocked_family_is_not_counted(): void
    {
        $me = $this->family('Fun House');
        $them = $this->family('The Ahmeds');

        $this->asks($them, $me);
        $this->assertSame(1, AccountMenu::waitingCount($me->fresh()));

        Block::query()->create(['blocker_id' => $me->id, 'blocked_id' => $them->id]);

        $this->assertSame(0, AccountMenu::waitingCount($me->fresh()));
    }

    #[Test]
    public function a_request_from_a_family_who_has_left_is_not_counted(): void
    {
        $me = $this->family('Fun House');
        $them = $this->family('The Ahmeds');

        $this->asks($them, $me);
        $them->forceFill(['deletion_requested_at' => now()])->save();

        $this->assertSame(0, AccountMenu::waitingCount($me->fresh()));
    }

    #[Test]
    public function every_row_goes_somewhere_real(): void
    {
        $family = $this->family('Fun House');

        foreach (AccountMenu::rows($family) as $row) {
            $this->assertNotSame('#', $row['url'], $row['label'].' is a dead anchor');

            $this->actingAs($family, 'founder')
                ->get($row['url'])
                ->assertSuccessful();
        }
    }

    #[Test]
    public function the_row_you_are_on_is_marked_current(): void
    {
        $family = $this->family('Fun House', ['display_name' => 'Fun House']);

        $rows = AccountMenu::rows($family, route('requests'));

        $this->assertTrue($rows->firstWhere('label', 'Requests')['current']);
        $this->assertFalse($rows->firstWhere('label', 'Badges')['current']);
    }

    #[Test]
    public function the_menu_carries_a_way_out_and_a_way_to_help(): void
    {
        $family = $this->family('Fun House');

        $this->actingAs($family, 'founder')
            ->get(route('home'))
            ->assertOk()
            ->assertSee(route('logout'), false)
            ->assertSee(route('help'), false)
            ->assertSee(route('frith-plus'), false)
            ->assertSee(route('account.notifications'), false);
    }
}
