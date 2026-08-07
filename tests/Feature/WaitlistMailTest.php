<?php

namespace Tests\Feature;

use App\Mail\AlreadyOnTheList;
use App\Mail\ConfirmWaitlistSignup;
use App\Models\WaitlistSignup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WaitlistMailTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_confirmation_email_renders_with_a_working_link_and_logo(): void
    {
        $signup = WaitlistSignup::factory()->create(['email' => 'sam@example.com']);

        $mail = new ConfirmWaitlistSignup($signup);
        $html = $mail->render();

        // No client renders SVG in email, so the logo has to be the PNG export.
        $this->assertStringContainsString('frith-logo-horizontal-fullcolour-email.png', $html);
        $this->assertStringNotContainsString('.svg', $html);
        $this->assertStringContainsString('Yes, add me to the list', $html);
        $this->assertStringContainsString($signup->public_id, $html);
        $this->assertStringContainsString('Poppins', $html);
        // Guidelines §09: degrade to Segoe UI, because Poppins will not load in Outlook.
        $this->assertStringContainsString('Segoe UI', $html);
        // Linen on Eucalyptus, never white on coral.
        $this->assertStringContainsString('#274B44', $html);
        $this->assertStringNotContainsString('#E56A4E', $html);
    }

    #[Test]
    public function the_confirmation_email_carries_one_click_unsubscribe_headers(): void
    {
        $signup = WaitlistSignup::factory()->create();

        $headers = (new ConfirmWaitlistSignup($signup))->headers()->text;

        $this->assertArrayHasKey('List-Unsubscribe', $headers);
        $this->assertStringStartsWith('<http', $headers['List-Unsubscribe']);
        $this->assertStringContainsString('signature=', $headers['List-Unsubscribe']);
        $this->assertSame('List-Unsubscribe=One-Click', $headers['List-Unsubscribe-Post']);
    }

    #[Test]
    public function the_already_on_the_list_email_renders(): void
    {
        $signup = WaitlistSignup::factory()->confirmed()->create();

        $html = (new AlreadyOnTheList($signup))->render();

        $this->assertStringContainsString('You’re already on the list', $html);
        $this->assertStringContainsString('frith-logo-horizontal-fullcolour-email.png', $html);
        $this->assertStringContainsString(config('frith.company.name'), $html);
    }

    #[Test]
    public function every_email_names_the_company_and_offers_a_way_out(): void
    {
        $signup = WaitlistSignup::factory()->create();

        foreach ([new ConfirmWaitlistSignup($signup), new AlreadyOnTheList($signup)] as $mail) {
            $html = $mail->render();

            $this->assertStringContainsString('Frith Community Ltd', $html);
            $this->assertStringContainsString('Unsubscribe', $html);
            $this->assertStringContainsString(config('frith.company.contact_email'), $html);
        }
    }
}
