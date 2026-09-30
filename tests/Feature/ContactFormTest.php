<?php

namespace Tests\Feature;

use App\Mail\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function message(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Sam',
            'email' => 'sam@example.com',
            'topic' => 'A general question',
            'message' => 'How do I change the Journeys I am in?',
        ], $overrides);
    }

    #[Test]
    public function the_help_page_offers_a_way_to_write_to_a_person(): void
    {
        $this->get(route('help'))
            ->assertOk()
            ->assertSee(route('help.contact'), false)
            ->assertSee('Send us a message');
    }

    #[Test]
    public function a_message_reaches_the_team(): void
    {
        Mail::fake();

        $this->post(route('help.contact'), $this->message())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('help').'#contact');

        Mail::assertSent(ContactMessage::class, fn ($mail) => $mail->hasTo(config('frith.company.contact_email')));
    }

    #[Test]
    public function a_safety_concern_goes_to_the_address_the_page_promises(): void
    {
        // The page says safety goes to support@. Somebody writing about a
        // worry should not sit behind a week of general enquiries.
        Mail::fake();

        $this->post(route('help.contact'), $this->message(['topic' => 'A safety concern']));

        Mail::assertSent(ContactMessage::class, fn ($mail) => $mail->hasTo('support@frith.community'));
    }

    #[Test]
    public function the_reply_goes_back_to_whoever_wrote_it(): void
    {
        $mail = new ContactMessage($this->message());

        $this->assertSame('sam@example.com', $mail->envelope()->replyTo[0]->address);
        $this->assertStringContainsString('A general question', $mail->envelope()->subject);
    }

    #[Test]
    public function it_says_what_is_wrong_rather_than_just_refusing(): void
    {
        Mail::fake();

        $this->post(route('help.contact'), $this->message(['name' => '', 'email' => 'not-an-email', 'message' => 'hi']))
            ->assertSessionHasErrors(['name', 'email', 'message']);

        Mail::assertNothingSent();
    }

    #[Test]
    public function a_topic_that_was_never_offered_is_refused(): void
    {
        Mail::fake();

        $this->post(route('help.contact'), $this->message(['topic' => 'Free watches']))
            ->assertSessionHasErrors('topic');

        Mail::assertNothingSent();
    }

    #[Test]
    public function nothing_about_the_message_is_kept(): void
    {
        // The page promises there is no ticket and no record. That is a claim
        // about the database, so it is checked against the database.
        Mail::fake();

        $this->post(route('help.contact'), $this->message());

        foreach (['registrations', 'pages', 'jobs'] as $table) {
            $this->assertDatabaseMissing($table, ['id' => null]);
        }

        $this->assertSame(
            0,
            DB::table('registrations')->where('email', 'sam@example.com')->count(),
            'a contact message should not create a registration',
        );
    }
}
