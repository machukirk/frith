<?php

namespace Tests\Feature;

use App\Mail\SomebodyAsked;
use App\Models\Block;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * "A family has asked to connect."
 *
 * The one email the free plan sends about somebody else, and the reason the
 * notification design hangs together: a request nobody is told about sits
 * unseen for weeks, and the family who asked reads that as being ignored.
 * It was designed and did not exist.
 */
class SomebodyAskedEmailTest extends TestCase
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

    private function ask(Registration $from, Registration $to): TestResponse
    {
        return $this->actingAs($from->fresh(), 'founder')
            ->post(route('connections.ask', $to->public_id));
    }

    #[Test]
    public function a_paid_ask_always_reaches_the_other_family(): void
    {
        // The sender's plan decides, not the recipient's. That is what a
        // Frith+ membership buys: your requests are seen.
        Mail::fake();

        $asker = $this->family('Fun House', ['founder_number' => 3]);
        $other = $this->family('The Ahmeds');

        $this->assertTrue($asker->hasPlus());
        $this->assertFalse($other->hasPlus());

        $this->ask($asker, $other);

        Mail::assertQueued(SomebodyAsked::class, fn ($mail) => $mail->hasTo('the-ahmeds@example.com'));
    }

    #[Test]
    public function a_free_ask_waits_on_the_requests_screen_instead(): void
    {
        // Frith is quiet on the free plan, and the screen says where the
        // request will be waiting instead of pretending one was sent.
        Mail::fake();

        $asker = $this->family('Fun House');
        $other = $this->family('The Ahmeds');

        $this->ask($asker, $other)
            ->assertSessionHas('status', fn ($status) => str_contains($status, 'when they next look'));

        Mail::assertNothingQueued();
    }

    #[Test]
    public function a_free_ask_reaches_a_recipient_who_pays_for_every_ask(): void
    {
        Mail::fake();

        $asker = $this->family('Fun House');
        $other = $this->family('The Ahmeds', ['founder_number' => 7]);

        $other->forceFill(['notification_prefs' => ['any_asks' => true]])->save();

        $this->ask($asker, $other);

        Mail::assertQueued(SomebodyAsked::class);
    }

    #[Test]
    public function the_screen_does_not_claim_somebody_was_told_when_they_were_not(): void
    {
        Mail::fake();

        $paid = $this->family('Fun House', ['founder_number' => 3]);
        $free = $this->family('Willow Cottage');
        $other = $this->family('The Ahmeds');
        $second = $this->family('The Okonkwos');

        $this->ask($paid, $other)
            ->assertSessionHas('status', fn ($s) => str_contains($s, 'has been told'));

        $this->ask($free, $second)
            ->assertSessionHas('status', fn ($s) => str_contains($s, 'will see it when they next look'));
    }

    #[Test]
    public function it_says_no_more_about_the_asker_than_a_profile_does(): void
    {
        // Everything in it comes from FamilyCard, the one place that decides
        // what crosses between two families before they agree to talk.
        $asker = $this->family('Fun House', ['founder_number' => 3]);
        $other = $this->family('The Ahmeds');

        $asker->forceFill(['support_areas' => ['learning-education']])->save();
        $asker->experiences()->create(['category' => 'learning-education', 'item' => 'school-anxiety']);

        $html = (new SomebodyAsked($other->fresh(), $asker->fresh()))->render();

        $this->assertStringContainsString('Fun House', $html);
        $this->assertStringContainsString('Leigh-on-Sea area', $html);
        // 'in common', not 'things in common': one shared answer reads as
        // '1 thing in common', and the singular is the easier case to break.
        $this->assertStringContainsString('in common', $html);

        // What either family is navigating never crosses, in any channel.
        $this->assertStringNotContainsString('school-anxiety', $html);
        $this->assertStringNotContainsString('Learning', $html);
    }

    #[Test]
    public function the_subject_line_names_nobody(): void
    {
        // A subject is readable from a lock screen by whoever picks the phone
        // up, and who is asking belongs inside.
        $asker = $this->family('Fun House');
        $other = $this->family('The Ahmeds');

        $envelope = (new SomebodyAsked($other, $asker))->envelope();

        $this->assertSame('A family has asked to connect', $envelope->subject);
        $this->assertStringNotContainsString('Fun House', $envelope->subject);
    }

    #[Test]
    public function it_has_a_plain_text_twin(): void
    {
        $asker = $this->family('Fun House');
        $other = $this->family('The Ahmeds');

        $mail = new SomebodyAsked($other, $asker);

        $this->assertSame('emails.somebody-asked-text', $mail->content()->text);
    }

    #[Test]
    public function nothing_is_sent_when_the_ask_is_refused(): void
    {
        // At the limit, blocked, or already in hand: no row, so no email.
        Mail::fake();

        $asker = $this->family('Fun House', ['founder_number' => 3]);
        $other = $this->family('The Ahmeds');

        Block::query()->create(['blocker_id' => $other->id, 'blocked_id' => $asker->id]);

        $this->ask($asker, $other)->assertNotFound();

        Mail::assertNothingQueued();
    }
}
