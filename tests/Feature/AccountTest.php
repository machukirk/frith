<?php

namespace Tests\Feature;

use App\Models\Block;
use App\Models\Connection;
use App\Models\Journey;
use App\Models\Message;
use App\Models\Registration;
use App\Models\Report;
use App\Support\Badges;
use App\Support\NotificationSettings;
use App\Support\ProfileSections;
use Database\Seeders\FormSeeder;
use Database\Seeders\JourneySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    private function family(string $name = 'Fun House', array $overrides = []): Registration
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

    #[Test]
    public function the_account_is_only_for_families_who_are_logged_in(): void
    {
        foreach (['account', 'account.badges', 'account.notifications', 'account.data'] as $name) {
            $this->get(route($name))->assertRedirect(route('login'));
        }
    }

    #[Test]
    public function the_profile_lists_every_section_with_a_way_back_in(): void
    {
        $this->seed(FormSeeder::class);
        $family = $this->family();

        $response = $this->actingAs($family, 'founder')->get(route('account'))->assertOk();

        // Every section the profile claims to list has an edit link that
        // actually goes somewhere, or the list is decoration.
        foreach (ProfileSections::for($family) as $section) {
            $response->assertSee($section['title']);
            $response->assertSee(route('account.edit', $section['step']), false);
        }
    }

    #[Test]
    public function the_profile_says_which_section_other_families_never_see(): void
    {
        $this->seed(FormSeeder::class);
        $family = $this->family();

        // The experiences are the one answer that never crosses between two
        // families as written. The row has to say so where the worry is.
        $experiences = ProfileSections::for($family)->firstWhere('step', 'experiences');

        $this->assertTrue($experiences['private']);

        $this->actingAs($family, 'founder')
            ->get(route('account'))
            ->assertOk()
            ->assertSee('Private');
    }

    #[Test]
    public function the_tally_counts_what_is_filled_in_and_nothing_is_required(): void
    {
        $this->seed(FormSeeder::class);
        $family = $this->family();

        // A name, an area and a child, and nothing else.
        $this->assertSame('2 of 6 sections complete', ProfileSections::tally($family));

        $this->actingAs($family, 'founder')
            ->get(route('account'))
            ->assertOk()
            ->assertSee('Nothing here is required');
    }

    #[Test]
    public function changing_the_name_and_area_changes_what_other_families_see(): void
    {
        $family = $this->family();

        $this->actingAs($family, 'founder')
            ->get(route('account.edit', 'name'))
            ->assertOk()
            // The email is shown rather than editable: it is the account, and
            // changing it needs proof the new address works.
            ->assertSee($family->email)
            ->assertSee('not something we change from here');

        $this->actingAs($family, 'founder')
            ->post(route('account.edit.store', 'name'), [
                'display_name' => '  The Okonkwos  ',
                'postcode_outcode' => 'tn13 ',
            ])
            ->assertRedirect(route('account'));

        $family->refresh();

        $this->assertSame('The Okonkwos', $family->display_name);
        $this->assertSame('TN13', $family->postcode_outcode);
        $this->assertSame('Sevenoaks area', $family->area());
    }

    #[Test]
    public function the_email_address_cannot_be_changed_by_posting_one(): void
    {
        $family = $this->family();

        $this->actingAs($family, 'founder')
            ->post(route('account.edit.store', 'name'), [
                'display_name' => 'Fun House',
                'postcode_outcode' => 'SS9',
                'email' => 'somebody-else@example.com',
            ])
            ->assertRedirect(route('account'));

        $this->assertSame('fun-house@example.com', $family->fresh()->email);
    }

    #[Test]
    public function editing_a_section_reuses_the_registration_screen(): void
    {
        $this->seed(FormSeeder::class);
        $family = $this->family();

        // The same words, the same options, the same help text, and a button
        // that says Save rather than Continue, because this is not a journey
        // through six screens.
        $this->actingAs($family, 'founder')
            ->get(route('account.edit', 'hopes'))
            ->assertOk()
            ->assertSee('What are you hoping to find through Frith?')
            ->assertSee('>Save<', false)
            ->assertDontSee('Step 3 of 6')
            ->assertSee(route('account.edit.store', 'hopes'), false);
    }

    #[Test]
    public function an_edit_saves_through_the_same_rules_as_registration(): void
    {
        $this->seed(FormSeeder::class);
        $family = $this->family();

        $this->actingAs($family, 'founder')
            ->post(route('account.edit.store', 'hopes'), [
                'hopes' => ['local-families', 'made-up-slug'],
            ])
            ->assertSessionHasErrors('hopes.1');

        $this->assertNull($family->fresh()->hopes);

        $this->actingAs($family, 'founder')
            ->post(route('account.edit.store', 'hopes'), ['hopes' => ['local-families']])
            ->assertRedirect(route('account'));

        $this->assertSame(['local-families'], $family->fresh()->hopes);
    }

    #[Test]
    public function dropping_an_area_takes_its_statements_with_it(): void
    {
        $this->seed(FormSeeder::class);
        $family = $this->family();

        $family->forceFill(['support_areas' => ['learning-education', 'child-development']])->save();
        $family->experiences()->create(['category' => 'learning-education', 'item' => 'school-anxiety']);
        $family->experiences()->create(['category' => 'child-development', 'item' => 'communication-speech']);

        // Otherwise a family keeps matching on something they have just said
        // is no longer part of their life.
        $this->actingAs($family, 'founder')
            ->post(route('account.edit.store', 'areas'), ['support_areas' => ['learning-education']])
            ->assertRedirect(route('account'));

        $this->assertSame(['learning-education'], $family->fresh()->support_areas);
        $this->assertSame(['school-anxiety'], $family->fresh()->experiences->pluck('item')->all());
    }

    #[Test]
    public function taking_every_area_off_clears_the_statements_under_them(): void
    {
        $this->seed(FormSeeder::class);
        $family = $this->family();

        $family->forceFill(['support_areas' => ['learning-education']])->save();
        $family->experiences()->create(['category' => 'learning-education', 'item' => 'school-anxiety']);

        $this->actingAs($family, 'founder')
            ->post(route('account.edit.store', 'areas'), ['action' => 'skip'])
            ->assertRedirect(route('account'));

        $this->assertSame([], $family->fresh()->support_areas);
        $this->assertCount(0, $family->fresh()->experiences);
    }

    #[Test]
    public function a_section_that_is_not_one_of_the_six_is_a_404(): void
    {
        $family = $this->family();

        // Including the registration screen the account replaced: 'you' asks
        // for an email and a first name, which is not what this edits.
        foreach (['you', 'anything', 'welcome'] as $step) {
            $this->actingAs($family, 'founder')
                ->get(route('account.edit', $step))
                ->assertNotFound();
        }
    }

    // --- Badges --------------------------------------------------------------

    #[Test]
    public function a_founder_has_the_one_badge_nobody_can_get_again(): void
    {
        $founder = $this->family('Fun House', ['founder_number' => 4]);
        $later = $this->family('The Ahmeds');

        $this->assertTrue(Badges::held($founder)['first-frith-family']);
        $this->assertFalse(Badges::held($later)['first-frith-family']);

        $this->actingAs($founder, 'founder')
            ->get(route('account.badges'))
            ->assertOk()
            ->assertSee('First Frith Family')
            ->assertSee('It will never be awarded again');
    }

    #[Test]
    public function nothing_on_the_badges_screen_is_a_score_or_a_nudge(): void
    {
        $this->seed(FormSeeder::class);
        $family = $this->family();

        $this->actingAs($family, 'founder')
            ->get(route('account.badges'))
            ->assertOk()
            ->assertSee('There is no score, no leaderboard, and nothing expires')
            ->assertSee('Badges are descriptive, not rewards')
            // The not-yet ones say what would bring them about, not what to
            // go and do about it.
            ->assertSee('Not yet');
    }

    #[Test]
    public function a_badge_can_be_hidden_and_hiding_it_tells_nobody(): void
    {
        $this->seed(FormSeeder::class);
        $family = $this->family('Fun House', ['founder_number' => 7]);

        $this->assertSame(['first-frith-family'], Badges::visible($family)->pluck('slug')->all());

        // The form posts what to show. Everything held and not posted is
        // hidden, so an untouched checkbox cannot change what others see.
        $this->actingAs($family, 'founder')
            ->post(route('account.badges.update'), ['shown' => []])
            ->assertRedirect(route('account.badges'));

        $this->assertSame(['first-frith-family'], $family->fresh()->hidden_badges);
        $this->assertCount(0, Badges::visible($family->fresh()));
    }

    #[Test]
    public function the_helping_hand_is_chosen_rather_than_earned(): void
    {
        $this->seed(FormSeeder::class);
        $family = $this->family();

        $this->assertFalse(Badges::held($family)['helping-hand']);

        $this->actingAs($family, 'founder')
            ->post(route('account.badges.update'), [
                'helping_hand' => '1',
                'shown' => ['helping-hand'],
            ])
            ->assertRedirect(route('account.badges'));

        $this->assertTrue((bool) $family->fresh()->helping_hand);
        $this->assertContains('helping-hand', Badges::visible($family->fresh())->pluck('slug')->all());
    }

    #[Test]
    public function another_family_never_sees_who_your_first_connection_was_with(): void
    {
        $this->seed(FormSeeder::class);
        $mine = $this->family('Fun House');
        $theirs = $this->family('The Ahmeds');

        Connection::query()->create([
            'requester_id' => $mine->id,
            'addressee_id' => $theirs->id,
            'status' => Connection::ACCEPTED,
            'responded_at' => now(),
        ]);

        // Their own screen names the family. The shape that crosses between
        // two families carries a slug and a title and nothing else.
        $this->assertStringContainsString(
            'The Ahmeds',
            Badges::all($mine)->firstWhere('slug', 'first-connection')['note'],
        );

        $this->assertSame(
            ['slug', 'title'],
            array_keys(Badges::visible($mine)->firstWhere('slug', 'first-connection')),
        );
    }

    // --- Notifications -------------------------------------------------------

    #[Test]
    public function the_free_plan_says_which_emails_it_does_not_send(): void
    {
        $family = $this->family();

        $this->assertFalse($family->hasPlus());

        $this->actingAs($family, 'founder')
            ->get(route('account.notifications'))
            ->assertOk()
            ->assertSee('Frith is quiet on the free plan')
            // Said rather than left to be discovered: a request that is never
            // emailed about will sit unseen, so the screen says where.
            ->assertSee('will be waiting in')
            ->assertSee('It is worth looking in every week or so');
    }

    #[Test]
    public function a_frith_plus_switch_cannot_be_turned_on_without_frith_plus(): void
    {
        $family = $this->family();

        $this->actingAs($family, 'founder')
            ->post(route('account.notifications.update'), [
                'prefs' => ['any_asks' => '1', 'journeys' => '1', 'news' => '1'],
            ])
            ->assertRedirect(route('account.notifications'));

        $family->refresh();

        // The two paid ones are refused, the free one is kept.
        $this->assertFalse(NotificationSettings::sends($family, 'any_asks'));
        $this->assertFalse(NotificationSettings::sends($family, 'journeys'));
        $this->assertTrue(NotificationSettings::sends($family, 'news'));
    }

    #[Test]
    public function a_founder_gets_frith_plus_and_the_switches_that_come_with_it(): void
    {
        // The Founders cap means Frith+ is a Founder number, for now. One
        // method decides it, so there is one place to change when payments
        // arrive.
        $family = $this->family('Fun House', ['founder_number' => 12]);

        $this->assertTrue($family->hasPlus());

        $this->actingAs($family, 'founder')
            ->get(route('account.notifications'))
            ->assertOk()
            ->assertSee('How many requests you would like');

        $this->actingAs($family, 'founder')
            ->post(route('account.notifications.update'), [
                'prefs' => ['any_asks' => '1'],
                'pace' => 'week',
            ])
            ->assertRedirect(route('account.notifications'));

        $family->refresh();

        $this->assertTrue(NotificationSettings::sends($family, 'any_asks'));
        $this->assertSame('week', $family->request_pace);
    }

    #[Test]
    public function the_one_notification_nobody_can_turn_off_stays_on(): void
    {
        $family = $this->family();

        // A family who could switch off everything would have a request sit
        // unseen for weeks, and the family who asked would read that as
        // being ignored.
        $this->actingAs($family, 'founder')
            ->post(route('account.notifications.update'), ['prefs' => []])
            ->assertRedirect(route('account.notifications'));

        $this->assertTrue(NotificationSettings::sends($family->fresh(), 'plus_asks'));
    }

    #[Test]
    public function a_paid_preference_survives_a_membership_ending(): void
    {
        $family = $this->family('Fun House', ['founder_number' => 3]);

        $this->actingAs($family, 'founder')
            ->post(route('account.notifications.update'), ['prefs' => ['any_asks' => '1']]);

        $this->assertTrue(NotificationSettings::sends($family->fresh(), 'any_asks'));

        // Stored, but not acted on. Kept so somebody rejoining does not have
        // to set it all again.
        $family->forceFill(['founder_number' => null])->save();

        $this->assertFalse(NotificationSettings::sends($family->fresh(), 'any_asks'));
        $this->assertTrue(($family->fresh()->notification_prefs)['any_asks']);
    }

    #[Test]
    public function there_is_nowhere_to_configure_a_streak(): void
    {
        // Not a feature test so much as a guard: the design is explicit that
        // Frith sends no streaks, no "you have been away" reminders and no
        // counts of what somebody has missed, on either plan.
        $switches = array_keys(collect(config('frith-notifications'))->except('pace')->all());

        foreach ($switches as $key) {
            $this->assertStringNotContainsString('streak', $key);
            $this->assertStringNotContainsString('reminder', $key);
        }

        $family = $this->family();

        $this->actingAs($family, 'founder')
            ->get(route('account.notifications'))
            ->assertOk()
            ->assertSee('We do not send streaks');
    }

    // --- Does this still sound like your family? ----------------------------

    #[Test]
    public function the_review_reads_their_own_answers_back(): void
    {
        $this->seed(FormSeeder::class);
        $family = $this->family();

        $family->forceFill(['support_areas' => ['learning-education']])->save();
        $family->experiences()->create(['category' => 'learning-education', 'item' => 'school-anxiety']);

        $this->actingAs($family->fresh(), 'founder')
            ->get(route('account.experiences.review'))
            ->assertOk()
            ->assertSee('Does this still sound like your family?')
            ->assertSee('Learning')
            ->assertSee('Yes, this is still us')
            ->assertSee('Something has changed');
    }

    #[Test]
    public function both_answers_to_the_review_buy_the_same_quiet(): void
    {
        $this->seed(FormSeeder::class);

        foreach (['confirm', 'snooze'] as $answer) {
            $family = $this->family('Family '.$answer);
            $family->forceFill(['completed_at' => now()->subMonths(9)])->save();
            $family->experiences()->create(['category' => 'learning-education', 'item' => 'school-anxiety']);

            $this->assertTrue($family->fresh()->load('experiences')->needsExperienceReview());

            $this->actingAs($family, 'founder')->post(route('account.experiences.'.$answer));

            // Ignoring it must not be the cheaper answer, or the question
            // becomes a nag, which the screen promises it is not.
            $this->assertFalse($family->fresh()->load('experiences')->needsExperienceReview());
        }
    }

    #[Test]
    public function the_review_is_not_asked_of_a_family_with_nothing_to_review(): void
    {
        $family = $this->family();
        $family->forceFill(['completed_at' => now()->subYear()])->save();

        $this->assertFalse($family->fresh()->load('experiences')->needsExperienceReview());
    }

    #[Test]
    public function changing_the_answers_counts_as_answering_the_review(): void
    {
        $this->seed(FormSeeder::class);
        $family = $this->family();
        $family->forceFill(['completed_at' => now()->subMonths(9)])->save();
        $family->experiences()->create(['category' => 'learning-education', 'item' => 'school-anxiety']);

        $this->actingAs($family->fresh(), 'founder')
            ->post(route('account.edit.store', 'areas'), ['support_areas' => ['learning-education']]);

        // Somebody who has just updated it should not then be asked whether
        // it is still current.
        $this->assertFalse($family->fresh()->load('experiences')->needsExperienceReview());
    }

    // --- Your data -----------------------------------------------------------

    #[Test]
    public function asking_for_a_copy_of_your_data_is_recorded_once(): void
    {
        $family = $this->family();

        $this->actingAs($family, 'founder')
            ->post(route('account.data.request'))
            ->assertRedirect(route('account.data'));

        $first = $family->fresh()->data_requested_at;
        $this->assertNotNull($first);

        // Pressing again must not reset the clock on a request somebody is
        // already working through.
        $this->travel(2)->hours();
        $this->actingAs($family->fresh(), 'founder')->post(route('account.data.request'));

        $this->assertEquals($first, $family->fresh()->data_requested_at);
    }

    #[Test]
    public function leaving_says_what_it_cannot_undo_before_it_happens(): void
    {
        $family = $this->family();

        $this->actingAs($family, 'founder')
            ->get(route('account.delete'))
            ->assertOk()
            ->assertSee('stay in the other family')
            // The alternative, offered rather than buried: somebody who wants
            // quiet does not have to delete everything to get it.
            ->assertSee('If you would rather just be left alone');
    }

    #[Test]
    public function leaving_needs_the_box_ticked(): void
    {
        $family = $this->family();

        $this->actingAs($family, 'founder')
            ->post(route('account.delete.store'))
            ->assertSessionHasErrors('understood');

        $this->assertNull($family->fresh()->deletion_requested_at);
    }

    #[Test]
    public function leaving_closes_what_is_open_and_hides_the_profile_at_once(): void
    {
        $this->seed(JourneySeeder::class);
        $mine = $this->family('Fun House');
        $theirs = $this->family('The Ahmeds');

        $journey = Journey::query()->where('slug', 'sleep')->sole();
        $journey->members()->attach([$mine->id, $theirs->id]);

        $pending = Connection::query()->create([
            'requester_id' => $theirs->id,
            'addressee_id' => $mine->id,
            'status' => Connection::PENDING,
        ]);

        $this->actingAs($mine, 'founder')
            ->post(route('account.delete.store'), ['understood' => '1'])
            ->assertRedirect(route('home'));

        $mine->refresh();

        $this->assertNotNull($mine->deletion_requested_at);
        $this->assertTrue($mine->hasLeft());
        $this->assertCount(0, $mine->journeys);

        // Nobody is left waiting on a family who has gone.
        $this->assertSame(Connection::DECLINED, $pending->fresh()->status);

        // And they are gone from everything that puts one family in front of
        // another, whatever is still waiting to be deleted behind the scenes.
        $this->assertFalse(Registration::query()->present()->whereKey($mine->id)->exists());

        $this->actingAs($theirs, 'founder')
            ->get(route('family', $mine->public_id))
            ->assertNotFound();
    }

    #[Test]
    public function what_frith_holds_is_gone_once_the_grace_period_is_up(): void
    {
        $this->seed(JourneySeeder::class);
        $mine = $this->family('Fun House');
        $theirs = $this->family('The Ahmeds');

        Journey::query()->where('slug', 'sleep')->sole()->members()->attach($mine->id);
        $mine->forceFill(['support_areas' => ['learning-education'], 'hopes' => ['local-families']])->save();
        $mine->experiences()->create(['category' => 'learning-education', 'item' => 'school-anxiety']);

        $connection = Connection::query()->create([
            'requester_id' => $mine->id, 'addressee_id' => $theirs->id,
            'status' => Connection::ACCEPTED, 'responded_at' => now(),
        ]);
        Message::query()->create([
            'connection_id' => $connection->id, 'sender_id' => $mine->id, 'body' => 'Hello.',
        ]);

        $this->actingAs($mine, 'founder')->post(route('account.delete.store'), ['understood' => '1']);

        // Inside the grace period, nothing has actually gone: somebody who
        // left in a bad week can still be put back by a person.
        $this->artisan('frith:purge-departed')->expectsOutput('Nobody to remove.');
        $this->assertNotNull($mine->fresh()->email);

        $this->travel(31)->days();
        $this->artisan('frith:purge-departed')->assertSuccessful();

        $mine->refresh();

        foreach (['email', 'first_name', 'display_name', 'postcode_outcode', 'hopes', 'support_areas', 'consent_ip'] as $column) {
            $this->assertNull($mine->{$column}, $column.' should be gone');
        }

        $this->assertCount(0, $mine->children()->get());
        $this->assertCount(0, $mine->experiences()->get());
        $this->assertCount(0, $mine->journeys);

        // What stays, and why: the words are in another family's inbox, and
        // taking them out is not ours to do.
        $this->assertDatabaseCount('messages', 1);
        $this->assertSame('A family who has left', $mine->displayName());

        // Run twice: a scrubbed row has no email, so it is never picked up
        // again and nothing is scrubbed to death.
        $this->artisan('frith:purge-departed')->expectsOutput('Nobody to remove.');
    }

    #[Test]
    public function the_other_family_can_still_read_what_was_said(): void
    {
        $mine = $this->family('Fun House');
        $theirs = $this->family('The Ahmeds');

        $connection = Connection::query()->create([
            'requester_id' => $mine->id, 'addressee_id' => $theirs->id,
            'status' => Connection::ACCEPTED, 'responded_at' => now(),
        ]);
        Message::query()->create([
            'connection_id' => $connection->id, 'sender_id' => $mine->id,
            'body' => 'It does get easier, honestly.',
        ]);

        $this->actingAs($mine, 'founder')->post(route('account.delete.store'), ['understood' => '1']);
        $this->travel(31)->days();
        $this->artisan('frith:purge-departed');

        // The leaving screen promises this in as many words, so it is the
        // one thing deleting an account must not take away.
        $this->actingAs($theirs, 'founder')
            ->get(route('messages.show', $connection))
            ->assertOk()
            ->assertSee('It does get easier, honestly.')
            ->assertSee('A family who has left');
    }

    #[Test]
    public function the_blocked_list_is_only_ever_your_own(): void
    {
        $mine = $this->family('Fun House');
        $theirs = $this->family('The Ahmeds');
        $stranger = $this->family('Willow Cottage');

        Block::query()->create(['blocker_id' => $mine->id, 'blocked_id' => $theirs->id]);
        Block::query()->create(['blocker_id' => $stranger->id, 'blocked_id' => $theirs->id]);

        $this->actingAs($mine, 'founder')
            ->get(route('account.data'))
            ->assertOk()
            ->assertSee('The Ahmeds')
            ->assertSee(route('account.blocked', $theirs->public_id), false);

        // Somebody else's block is not theirs to look at, even for the same
        // family.
        $this->actingAs($theirs, 'founder')
            ->get(route('account.blocked', $stranger->public_id))
            ->assertNotFound();
    }

    #[Test]
    public function the_block_details_keep_what_you_said_at_the_time(): void
    {
        $mine = $this->family('Fun House');
        $theirs = $this->family('The Ahmeds');

        Block::query()->create(['blocker_id' => $mine->id, 'blocked_id' => $theirs->id]);
        Report::query()->create([
            'reporter_id' => $mine->id,
            'reported_id' => $theirs->id,
            'reported_name' => 'The Ahmeds',
            'reason' => array_key_first(config('frith-safety.reasons')),
            'note' => 'They would not take no for an answer.',
        ]);

        $this->actingAs($mine, 'founder')
            ->get(route('account.blocked', $theirs->public_id))
            ->assertOk()
            ->assertSee('They would not take no for an answer.')
            ->assertSee('Not yet');
    }

    #[Test]
    public function unblocking_is_as_silent_as_the_block_was(): void
    {
        $mine = $this->family('Fun House');
        $theirs = $this->family('The Ahmeds');

        Block::query()->create(['blocker_id' => $mine->id, 'blocked_id' => $theirs->id]);

        $this->assertTrue($mine->isSeparatedFrom($theirs));

        $this->actingAs($mine, 'founder')
            ->post(route('account.unblock', $theirs->public_id))
            ->assertRedirect(route('account.data'));

        $this->assertFalse($mine->fresh()->isSeparatedFrom($theirs));
        $this->assertDatabaseCount('blocks', 0);
    }
}
