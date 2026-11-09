<?php

namespace Tests\Feature;

use App\Models\Block;
use App\Models\Connection;
use App\Models\Journey;
use App\Models\Message;
use App\Models\Registration;
use App\Support\Suggestions;
use Database\Seeders\FormSeeder;
use Database\Seeders\JourneySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The village's four blocks below the journey list: the nudge, what is
 * waiting, who they are connected with, and who else is there.
 */
class VillageBlocksTest extends TestCase
{
    use RefreshDatabase;

    private function family(string $name, array $overrides = []): Registration
    {
        $family = Registration::query()->create(array_merge([
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

    // --- Suggestions ---------------------------------------------------------

    #[Test]
    public function suggestions_only_ever_come_from_a_shared_journey(): void
    {
        // The promise on the Journeys screen is that joining one makes a
        // profile visible to the families in it "and nowhere else". A family
        // who shares nothing with you is not a suggestion, however alike.
        $mine = $this->family('Fun House');
        $stranger = $this->family('The Ahmeds');

        $this->assertCount(0, Suggestions::for($mine));

        $this->journeyWith($mine, $stranger);

        $this->assertSame(
            [$stranger->id],
            Suggestions::for($mine->fresh())->pluck('family.id')->all(),
        );
    }

    #[Test]
    public function a_family_in_no_journeys_is_told_why_there_is_nobody(): void
    {
        $this->seed(JourneySeeder::class);
        $mine = $this->family('Fun House');

        $this->actingAs($mine, 'founder')
            ->get(route('village'))
            ->assertOk()
            ->assertSee('suggestions come from the Journeys you are in');
    }

    #[Test]
    public function somebody_already_answered_is_not_suggested_again(): void
    {
        $mine = $this->family('Fun House');
        $theirs = $this->family('The Ahmeds');
        $this->journeyWith($mine, $theirs);

        $this->assertCount(1, Suggestions::for($mine->fresh()));

        // Declined, which is kept rather than deleted precisely so that the
        // first screen a family opens does not put somebody they have said
        // no to back in front of them.
        Connection::query()->create([
            'requester_id' => $theirs->id,
            'addressee_id' => $mine->id,
            'status' => Connection::DECLINED,
            'responded_at' => now(),
        ]);

        $this->assertCount(0, Suggestions::for($mine->fresh()));
    }

    #[Test]
    public function a_blocked_family_is_never_suggested(): void
    {
        $mine = $this->family('Fun House');
        $theirs = $this->family('The Ahmeds');
        $this->journeyWith($mine, $theirs);

        Block::query()->create(['blocker_id' => $theirs->id, 'blocked_id' => $mine->id]);

        // Symmetrical: the family who was blocked stops seeing them too, and
        // is never told why.
        $this->assertCount(0, Suggestions::for($mine->fresh()));
        $this->assertCount(0, Suggestions::for($theirs->fresh()));
    }

    #[Test]
    public function a_family_who_has_left_is_never_suggested(): void
    {
        $mine = $this->family('Fun House');
        $gone = $this->family('The Ahmeds', ['deletion_requested_at' => now()]);
        $this->journeyWith($mine, $gone);

        $this->assertCount(0, Suggestions::for($mine->fresh()));
    }

    #[Test]
    public function a_family_who_has_not_named_themselves_is_not_suggested(): void
    {
        // Everything on a card reads from the name they chose. Somebody who
        // has not been through the village's one-off setup has no name to put
        // on one, so they wait rather than appearing as a blank.
        $mine = $this->family('Fun House');
        $unnamed = $this->family('Nameless', ['display_name' => null]);
        $this->journeyWith($mine, $unnamed);

        $this->assertCount(0, Suggestions::for($mine->fresh()));
    }

    #[Test]
    public function suggestions_are_ordered_by_the_score_and_capped(): void
    {
        $mine = $this->family('Fun House', ['interests' => ['outdoors', 'animals']]);
        $near = $this->family('Near', ['postcode_outcode' => 'SS9', 'interests' => ['outdoors', 'animals']]);
        $far = $this->family('Far', ['postcode_outcode' => 'M1', 'interests' => []]);

        $this->journeyWith($mine, $near, $far);

        $order = Suggestions::for($mine->fresh())->pluck('family.id')->all();

        $this->assertSame([$near->id, $far->id], $order);
        $this->assertCount(1, Suggestions::for($mine->fresh(), limit: 1));
    }

    // --- What is waiting -----------------------------------------------------

    #[Test]
    public function the_village_counts_what_is_waiting_on_you_separately(): void
    {
        $mine = $this->family('Fun House');
        $asked = $this->family('The Ahmeds');
        $waiting = $this->family('Willow Cottage');
        $this->journeyWith($mine, $asked, $waiting);

        // One family is waiting on this one…
        Connection::query()->create([
            'requester_id' => $asked->id,
            'addressee_id' => $mine->id,
            'status' => Connection::PENDING,
        ]);

        // …and this one is waiting on another. Two different facts, and only
        // the first is anybody's to act on.
        Connection::query()->create([
            'requester_id' => $mine->id,
            'addressee_id' => $waiting->id,
            'status' => Connection::PENDING,
        ]);

        $this->actingAs($mine->fresh(), 'founder')
            ->get(route('village'))
            ->assertOk()
            ->assertSee('1 family is waiting to hear from you')
            ->assertSee('And 1 of your requests has not been answered yet.')
            ->assertSee(route('requests'), false);
    }

    #[Test]
    public function the_waiting_card_is_absent_when_nothing_is_waiting(): void
    {
        $mine = $this->family('Fun House');
        $this->journeyWith($mine);

        $this->actingAs($mine->fresh(), 'founder')
            ->get(route('village'))
            ->assertOk()
            ->assertDontSee('Go to Requests');
    }

    // --- Who they are connected with -----------------------------------------

    #[Test]
    public function a_connected_family_shows_where_the_conversation_got_to(): void
    {
        $mine = $this->family('Fun House');
        $theirs = $this->family('The Ahmeds');
        $this->journeyWith($mine, $theirs);

        $connection = Connection::query()->create([
            'requester_id' => $mine->id,
            'addressee_id' => $theirs->id,
            'status' => Connection::ACCEPTED,
            'responded_at' => now(),
        ]);

        // Nothing said yet.
        $this->actingAs($mine->fresh(), 'founder')
            ->get(route('village'))
            ->assertOk()
            ->assertSee('Families you are connected with')
            ->assertSee('No messages yet');

        // Said hello, no answer. The useful thing to say, because the other
        // family may simply not have looked yet.
        Message::query()->create([
            'connection_id' => $connection->id,
            'sender_id' => $mine->id,
            'body' => 'Hello.',
        ]);

        $this->actingAs($mine->fresh(), 'founder')
            ->get(route('village'))
            ->assertOk()
            ->assertSee('You said hello, no reply yet');

        // Answered.
        Message::query()->create([
            'connection_id' => $connection->id,
            'sender_id' => $theirs->id,
            'body' => 'Hello back.',
        ]);

        $this->actingAs($mine->fresh(), 'founder')
            ->get(route('village'))
            ->assertOk()
            ->assertSee('Last spoke today');
    }

    #[Test]
    public function a_connected_family_is_a_link_into_the_conversation(): void
    {
        $mine = $this->family('Fun House');
        $theirs = $this->family('The Ahmeds');
        $this->journeyWith($mine, $theirs);

        $connection = Connection::query()->create([
            'requester_id' => $mine->id,
            'addressee_id' => $theirs->id,
            'status' => Connection::ACCEPTED,
            'responded_at' => now(),
        ]);

        $this->actingAs($mine->fresh(), 'founder')
            ->get(route('village'))
            ->assertOk()
            ->assertSee(route('messages.show', $connection), false);
    }

    // --- The nudge -----------------------------------------------------------

    #[Test]
    public function the_nudge_appears_only_when_the_answers_have_gone_stale(): void
    {
        $this->seed(FormSeeder::class);
        $mine = $this->family('Fun House');
        $mine->experiences()->create(['category' => 'learning-education', 'item' => 'school-anxiety']);

        $this->actingAs($mine->fresh(), 'founder')
            ->get(route('village'))
            ->assertOk()
            ->assertDontSee('Has anything changed?');

        $mine->forceFill(['completed_at' => now()->subMonths(9)])->save();

        $this->actingAs($mine->fresh(), 'founder')
            ->get(route('village'))
            ->assertOk()
            ->assertSee('Has anything changed?')
            ->assertSee('Take a look')
            ->assertSee('Not now');
    }

    #[Test]
    public function putting_the_nudge_down_puts_it_down(): void
    {
        $this->seed(FormSeeder::class);
        $mine = $this->family('Fun House', ['completed_at' => now()->subMonths(9)]);
        $mine->experiences()->create(['category' => 'learning-education', 'item' => 'school-anxiety']);

        $this->actingAs($mine->fresh(), 'founder')
            ->post(route('account.experiences.snooze'))
            ->assertRedirect(route('village'));

        $this->actingAs($mine->fresh(), 'founder')
            ->get(route('village'))
            ->assertOk()
            ->assertDontSee('Has anything changed?');
    }

    // --- The journey cards ---------------------------------------------------

    #[Test]
    public function a_journey_card_leads_into_that_journey(): void
    {
        $mine = $this->family('Fun House');
        $journey = $this->journeyWith($mine);

        // It used to point at the directory, which is a list of nine things
        // including the one somebody had just pressed.
        $this->actingAs($mine->fresh(), 'founder')
            ->get(route('village'))
            ->assertOk()
            ->assertSee(route('journeys.show', $journey), false);
    }
}
