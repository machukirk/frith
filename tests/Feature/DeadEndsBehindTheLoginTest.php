<?php

namespace Tests\Feature;

use App\Models\Block;
use App\Models\Connection;
use App\Models\Journey;
use App\Models\Registration;
use App\Models\Report;
use App\Support\Suggestions;
use Database\Seeders\JourneySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The flows behind the login that did not join up.
 *
 * An ask nobody could take back, a block screen nothing linked to, a profile
 * with no way off it, a Journey with no way out, a limit the copy promised
 * and nothing enforced, and a data request no person was ever told about.
 */
class DeadEndsBehindTheLoginTest extends TestCase
{
    use RefreshDatabase;

    private function family(string $name, array $overrides = []): Registration
    {
        $family = Registration::query()->create(array_merge([
            'email' => Str::slug($name).'@example.com',
            'first_name' => $name,
            'display_name' => $name,
            'postcode_outcode' => 'TN13',
            'email_verified_at' => now(),
            'consent_version' => config('frith.consent.version'),
            'consent_text' => config('frith.consent.text'),
            'consented_at' => now(),
        ], $overrides));

        $family->children()->create(['birth_month' => 3, 'birth_year' => 2017, 'position' => 0]);

        return $family->load(['children', 'experiences']);
    }

    private function journeyWith(Registration ...$families): Journey
    {
        $this->seed(JourneySeeder::class);
        $journey = Journey::query()->where('slug', 'sleep')->sole();

        foreach ($families as $family) {
            $journey->members()->attach($family->id);
        }

        return $journey;
    }

    /** What the array mailer actually sent. Mail::fake does not record raw mail. */
    private function sentMail(): Collection
    {
        return Mail::mailer()->getSymfonyTransport()->messages();
    }

    #[Test]
    public function an_ask_nobody_has_answered_can_be_taken_back(): void
    {
        $mine = $this->family('Fun House');
        $theirs = $this->family('The Ahmeds');

        $this->actingAs($mine, 'founder')->post(route('connections.ask', $theirs->public_id));

        $connection = Connection::sole();

        // Offered on the profile as well as on the Requests screen, because
        // that profile used to say there was nothing to do but wait.
        $this->actingAs($mine, 'founder')
            ->get(route('family', $theirs->public_id))
            ->assertOk()
            ->assertSee(route('connections.withdraw', $connection), false);

        $this->actingAs($mine, 'founder')
            ->from(route('requests'))
            ->post(route('connections.withdraw', $connection))
            ->assertRedirect(route('requests'))
            ->assertSessionHas('status', fn (string $status) => str_contains($status, 'never told'));

        // The row goes rather than closing. Nothing was refused, so nothing
        // should read later as a no from the family who was asked.
        $this->assertSame(0, Connection::query()->count());

        // And asking again is allowed, which is the point of deleting it.
        $this->actingAs($mine, 'founder')->post(route('connections.ask', $theirs->public_id));

        $this->assertSame(Connection::PENDING, Connection::sole()->status);
    }

    #[Test]
    public function only_the_family_who_asked_can_take_it_back_and_only_until_it_is_answered(): void
    {
        $mine = $this->family('Fun House');
        $theirs = $this->family('The Ahmeds');

        $this->actingAs($mine, 'founder')->post(route('connections.ask', $theirs->public_id));
        $connection = Connection::sole();

        $this->actingAs($theirs, 'founder')
            ->post(route('connections.withdraw', $connection))
            ->assertForbidden();

        $connection->update(['status' => Connection::ACCEPTED, 'responded_at' => now()]);

        $this->actingAs($mine, 'founder')
            ->post(route('connections.withdraw', $connection))
            ->assertStatus(409);

        $this->assertSame(1, Connection::query()->count());
    }

    #[Test]
    public function the_requests_screen_offers_the_way_out_of_your_own_asks(): void
    {
        $mine = $this->family('Fun House');
        $theirs = $this->family('The Ahmeds');

        $this->actingAs($mine, 'founder')->post(route('connections.ask', $theirs->public_id));
        $connection = Connection::sole();

        $this->actingAs($mine, 'founder')
            ->get(route('requests'))
            ->assertOk()
            ->assertSee(route('connections.withdraw', $connection), false)
            ->assertSee('Withdraw')
            ->assertSee('1 of 10 waiting for a reply');
    }

    #[Test]
    public function ten_asks_can_be_waiting_at_once_and_the_eleventh_is_told_who_to_chase(): void
    {
        $mine = $this->family('Fun House');

        foreach (range(1, 10) as $i) {
            $other = $this->family('Family '.$i);
            $this->actingAs($mine, 'founder')->post(route('connections.ask', $other->public_id));
        }

        $eleventh = $this->family('The Ahmeds');

        $this->actingAs($mine, 'founder')
            ->post(route('connections.ask', $eleventh->public_id))
            ->assertSessionHas('status', fn (string $status) => str_contains($status, 'Family 1')
                && str_contains($status, 'Family 10')
                && str_contains($status, 'Withdraw one'));

        $this->assertSame(10, Connection::query()->count(), 'the eleventh is not written');
    }

    #[Test]
    public function answering_one_frees_a_place(): void
    {
        $mine = $this->family('Fun House');
        $first = null;

        foreach (range(1, 10) as $i) {
            $other = $this->family('Family '.$i);
            $this->actingAs($mine, 'founder')->post(route('connections.ask', $other->public_id));
            $first ??= $other;
        }

        $answered = Connection::query()->where('addressee_id', $first->id)->sole();

        $this->actingAs($first, 'founder')->post(route('connections.decline', $answered));

        $eleventh = $this->family('The Ahmeds');

        $this->actingAs($mine, 'founder')->post(route('connections.ask', $eleventh->public_id));

        $this->assertSame(
            Connection::PENDING,
            Connection::query()->where('addressee_id', $eleventh->id)->sole()->status
        );
    }

    #[Test]
    public function not_for_us_closes_a_family_without_reporting_them(): void
    {
        $mine = $this->family('Fun House');
        $theirs = $this->family('The Ahmeds');
        $this->journeyWith($mine, $theirs);

        $mine = $mine->fresh()->load(['children', 'experiences', 'journeys']);

        $this->assertCount(1, Suggestions::for($mine), 'they are suggested to begin with');

        $this->actingAs($mine, 'founder')
            ->from(route('family', $theirs->public_id))
            ->post(route('connections.ask', $theirs->public_id), ['action' => 'not-for-us'])
            ->assertRedirect(route('family', $theirs->public_id))
            ->assertSessionHas('status', fn (string $status) => str_contains($status, 'not told'));

        $this->assertSame(Connection::DECLINED, Connection::sole()->status);

        // Saying no to a suggestion is not a safeguarding matter, and must
        // not file one.
        $this->assertSame(0, Report::query()->count());
        $this->assertSame(0, Block::query()->count());

        $this->assertCount(0, Suggestions::for($mine->fresh()->load(['children', 'experiences', 'journeys'])));
    }

    #[Test]
    public function a_family_profile_offers_blocking_not_for_us_and_a_way_back(): void
    {
        $mine = $this->family('Fun House');
        $theirs = $this->family('The Ahmeds');
        $this->journeyWith($mine, $theirs);

        $this->actingAs($mine->fresh()->load(['children', 'experiences']), 'founder')
            ->get(route('family', $theirs->public_id))
            ->assertOk()
            ->assertSee('Not for us')
            // Blocking and reporting are offered as two different things.
            ->assertSee(route('block', $theirs->public_id), false)
            ->assertSee(route('report', $theirs->public_id), false)
            ->assertSee('No reason needed')
            ->assertSee('Back to your village');
    }

    #[Test]
    public function a_journey_can_be_left_from_its_own_page_and_lands_somewhere_that_says_so(): void
    {
        $mine = $this->family('Fun House');
        $journey = $this->journeyWith($mine);

        $this->actingAs($mine->fresh(), 'founder')
            ->get(route('journeys.show', $journey))
            ->assertOk()
            ->assertSee(route('journeys.leave', $journey), false)
            ->assertSee('stays connected');

        // Back would be the Journey page, which a family who has just left
        // is no longer allowed to see.
        $this->actingAs($mine->fresh(), 'founder')
            ->from(route('journeys.show', $journey))
            ->post(route('journeys.leave', $journey))
            ->assertRedirect(route('journeys'));

        $this->actingAs($mine->fresh(), 'founder')
            ->get(route('journeys'))
            ->assertOk()
            ->assertSee('You have left '.$journey->title);
    }

    #[Test]
    public function asking_for_your_data_reaches_a_person(): void
    {
        $family = $this->family('Fun House');

        $this->actingAs($family, 'founder')
            ->post(route('account.data.request'))
            ->assertRedirect(route('account.data'));

        $this->assertNotNull($family->fresh()->data_requested_at);

        $sent = $this->sentMail();

        $this->assertCount(1, $sent);

        $message = $sent->first()->getOriginalMessage();

        $this->assertSame(config('frith.company.contact_email'), $message->getTo()[0]->getAddress());
        $this->assertStringContainsString('data request', $message->getSubject());
        $this->assertStringContainsString($family->email, $message->getTextBody());

        // Asked twice is asked once, for the inbox as well as the column.
        $this->actingAs($family->fresh(), 'founder')->post(route('account.data.request'));

        $this->assertCount(1, $this->sentMail());
    }
}
