<?php

namespace Tests\Feature;

use App\Models\Connection;
use App\Models\Journey;
use App\Models\Message;
use App\Models\Registration;
use App\Models\Report;
use Database\Seeders\JourneySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConnectingTest extends TestCase
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

    #[Test]
    public function a_journey_only_shows_its_families_to_a_family_in_it(): void
    {
        // Joining is what makes you visible to the others, so it is also what
        // lets you see them.
        $mine = $this->family('Fun House');
        $theirs = $this->family('The Ahmeds');
        $journey = $this->journeyWith($theirs);

        $this->actingAs($mine, 'founder')
            ->get(route('journeys.show', $journey))
            ->assertForbidden();

        $journey->members()->attach($mine->id);

        // Checked by profile link rather than by name: the header carries the
        // reading family's own name on every page.
        $this->actingAs($mine->fresh(), 'founder')
            ->get(route('journeys.show', $journey))
            ->assertOk()
            ->assertSee(route('family', $theirs->public_id), false)
            ->assertDontSee(route('family', $mine->public_id), false);
    }

    #[Test]
    public function the_profile_shows_only_what_the_home_page_promises(): void
    {
        $mine = $this->family('Fun House');
        $theirs = $this->family('The Ahmeds', ['founder_number' => 3]);
        $theirs->update(['support_areas' => ['learning-education']]);
        $mine->update(['support_areas' => ['learning-education']]);

        $response = $this->actingAs($mine->fresh()->load(['children', 'experiences']), 'founder')
            ->get(route('family', $theirs->public_id))
            ->assertOk();

        $response->assertSee('The Ahmeds')
            // The post town, not the outcode. TN13 is Sevenoaks.
            ->assertSee('Sevenoaks area')
            ->assertSee('Aged '.$theirs->children->first()->age())
            ->assertSee('First Frith Family')
            ->assertSee('things in common');

        // What they are navigating is never shown. Only the count is.
        $response->assertDontSee('learning-education');
    }

    #[Test]
    public function asking_to_connect_is_a_request_until_it_is_answered(): void
    {
        $mine = $this->family('Fun House');
        $theirs = $this->family('The Ahmeds');
        $journey = $this->journeyWith($mine, $theirs);

        $this->actingAs($mine, 'founder')
            ->post(route('connections.ask', $theirs->public_id), ['journey' => $journey->slug])
            ->assertRedirect();

        $connection = Connection::sole();

        $this->assertSame(Connection::PENDING, $connection->status);
        $this->assertSame($journey->id, $connection->journey_id, 'where they found each other');

        // They see it, with the Journey it came through.
        $this->actingAs($theirs, 'founder')
            ->get(route('requests'))
            ->assertOk()
            ->assertSee('Fun House')
            ->assertSee('via '.$journey->title);
    }

    #[Test]
    public function only_the_family_who_was_asked_can_answer(): void
    {
        $mine = $this->family('Fun House');
        $theirs = $this->family('The Ahmeds');

        $connection = Connection::query()->create([
            'requester_id' => $mine->id, 'addressee_id' => $theirs->id, 'status' => Connection::PENDING,
        ]);

        $this->actingAs($mine, 'founder')
            ->post(route('connections.accept', $connection))
            ->assertForbidden();

        $this->assertSame(Connection::PENDING, $connection->fresh()->status);
    }

    #[Test]
    public function saying_hello_back_opens_the_conversation(): void
    {
        $mine = $this->family('Fun House');
        $theirs = $this->family('The Ahmeds');

        $connection = Connection::query()->create([
            'requester_id' => $mine->id, 'addressee_id' => $theirs->id, 'status' => Connection::PENDING,
        ]);

        $this->actingAs($theirs, 'founder')
            ->post(route('connections.accept', $connection))
            ->assertRedirect(route('messages.show', $connection));

        $this->assertTrue($connection->fresh()->isAccepted());
        $this->assertNotNull($connection->fresh()->responded_at);
    }

    #[Test]
    public function saying_no_is_kept_so_nobody_is_asked_twice(): void
    {
        // Deleting the row would put the same family back in front of
        // somebody who has already said no once.
        $mine = $this->family('Fun House');
        $theirs = $this->family('The Ahmeds');

        $connection = Connection::query()->create([
            'requester_id' => $mine->id, 'addressee_id' => $theirs->id, 'status' => Connection::PENDING,
        ]);

        $this->actingAs($theirs, 'founder')->post(route('connections.decline', $connection));

        $this->assertSame(Connection::DECLINED, $connection->fresh()->status);
        $this->assertDatabaseCount('connections', 1);

        // And asking again does not make a second one.
        $this->actingAs($mine, 'founder')->post(route('connections.ask', $theirs->public_id));
        $this->assertDatabaseCount('connections', 1);
    }

    #[Test]
    public function a_message_needs_an_accepted_connection_behind_it(): void
    {
        $mine = $this->family('Fun House');
        $theirs = $this->family('The Ahmeds');

        $connection = Connection::query()->create([
            'requester_id' => $mine->id, 'addressee_id' => $theirs->id, 'status' => Connection::PENDING,
        ]);

        $this->actingAs($mine, 'founder')
            ->post(route('messages.send', $connection), ['body' => 'Hello'])
            ->assertNotFound();

        $this->assertDatabaseCount('messages', 0);

        $connection->update(['status' => Connection::ACCEPTED]);

        $this->actingAs($mine, 'founder')
            ->post(route('messages.send', $connection), ['body' => 'Hello'])
            ->assertRedirect(route('messages.show', $connection));

        $this->assertSame('Hello', Message::sole()->body);
    }

    #[Test]
    public function a_stranger_cannot_read_somebody_elses_conversation(): void
    {
        $mine = $this->family('Fun House');
        $theirs = $this->family('The Ahmeds');
        $nosy = $this->family('Willow Cottage');

        $connection = Connection::query()->create([
            'requester_id' => $mine->id, 'addressee_id' => $theirs->id, 'status' => Connection::ACCEPTED,
        ]);

        $this->actingAs($nosy, 'founder')
            ->get(route('messages.show', $connection))
            ->assertNotFound();
    }

    #[Test]
    public function reading_a_conversation_marks_only_their_lines_read(): void
    {
        $mine = $this->family('Fun House');
        $theirs = $this->family('The Ahmeds');

        $connection = Connection::query()->create([
            'requester_id' => $mine->id, 'addressee_id' => $theirs->id, 'status' => Connection::ACCEPTED,
        ]);

        $fromThem = Message::query()->create(['connection_id' => $connection->id, 'sender_id' => $theirs->id, 'body' => 'Hi']);
        $fromMe = Message::query()->create(['connection_id' => $connection->id, 'sender_id' => $mine->id, 'body' => 'Hello']);

        $this->actingAs($mine, 'founder')->get(route('messages.show', $connection))->assertOk();

        $this->assertNotNull($fromThem->fresh()->read_at);
        $this->assertNull($fromMe->fresh()->read_at, 'your own line is not read by you');
    }

    #[Test]
    public function blocking_hides_both_families_from_each_other_and_tells_nobody(): void
    {
        $mine = $this->family('Fun House');
        $theirs = $this->family('Willow Cottage');
        $journey = $this->journeyWith($mine, $theirs);

        $this->actingAs($mine, 'founder')
            ->post(route('block.store', $theirs->public_id))
            ->assertRedirect(route('village'));

        $this->assertDatabaseCount('blocks', 1);

        // Gone from the Journey, and their profile is a 404 rather than a
        // message saying they blocked you.
        $this->actingAs($mine->fresh()->load(['children', 'experiences']), 'founder')
            ->get(route('journeys.show', $journey))
            ->assertOk()
            ->assertDontSee(route('family', $theirs->public_id), false);

        $this->actingAs($mine->fresh()->load(['children', 'experiences']), 'founder')
            ->get(route('family', $theirs->public_id))
            ->assertNotFound();

        // And it works the other way too, without the blocked family being
        // told anything at all.
        $this->actingAs($theirs->fresh()->load(['children', 'experiences']), 'founder')
            ->get(route('family', $mine->public_id))
            ->assertNotFound();

        $this->actingAs($theirs->fresh()->load(['children', 'experiences']), 'founder')
            ->get(route('journeys.show', $journey))
            ->assertOk()
            ->assertDontSee(route('family', $mine->public_id), false);
    }

    #[Test]
    public function reporting_keeps_a_record_and_blocks_at_the_same_time(): void
    {
        // Nobody should have to do it twice.
        $mine = $this->family('Fun House');
        $theirs = $this->family('Willow Cottage');

        $this->actingAs($mine, 'founder')
            ->post(route('report.store', $theirs->public_id), [
                'reason' => 'uncomfortable',
                'note' => 'Asked where my child goes to school.',
            ])
            ->assertRedirect(route('village'));

        $report = Report::sole();

        $this->assertSame('uncomfortable', $report->reason);
        $this->assertSame('Willow Cottage', $report->reported_name, 'kept as text so it outlives the account');
        $this->assertNull($report->handled_at);
        $this->assertDatabaseCount('blocks', 1);
    }

    #[Test]
    public function a_report_needs_a_reason_we_offered(): void
    {
        $mine = $this->family('Fun House');
        $theirs = $this->family('Willow Cottage');

        $this->actingAs($mine, 'founder')
            ->post(route('report.store', $theirs->public_id), ['reason' => 'because'])
            ->assertSessionHasErrors('reason');

        $this->assertDatabaseCount('reports', 0);
    }

    #[Test]
    public function families_are_ordered_by_how_much_they_have_in_common(): void
    {
        $mine = $this->family('Fun House', ['postcode_outcode' => 'TN13']);
        $mine->update(['support_areas' => ['learning-education'], 'interests' => ['animals']]);
        $mine->experiences()->create(['category' => 'learning-education', 'item' => 'school-attendance']);

        $close = $this->family('The Ahmeds', ['postcode_outcode' => 'TN13']);
        $close->update(['support_areas' => ['learning-education'], 'interests' => ['animals']]);
        $close->experiences()->create(['category' => 'learning-education', 'item' => 'school-attendance']);

        $far = $this->family('Willow Cottage', ['postcode_outcode' => 'M1']);

        $journey = $this->journeyWith($mine, $close, $far);

        $html = $this->actingAs($mine->fresh()->load(['children', 'experiences']), 'founder')
            ->get(route('journeys.show', $journey))
            ->assertOk()
            ->getContent();

        $this->assertLessThan(
            strpos($html, 'Willow Cottage'),
            strpos($html, 'The Ahmeds'),
            'the closer match should come first',
        );
    }
}
