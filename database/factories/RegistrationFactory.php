<?php

namespace Database\Factories;

use App\Models\Registration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Registration>
 */
class RegistrationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'first_name' => $this->faker->firstName(),
            'postcode_outcode' => strtoupper($this->faker->randomLetter().$this->faker->randomLetter().$this->faker->numberBetween(1, 20)),
            'source' => 'coming-soon',
            'furthest_step' => 6,
            'completed_at' => now(),
            'consent_version' => config('frith.consent.version'),
            'consent_text' => config('frith.consent.text'),
            'consented_at' => now(),
            'consent_ip' => $this->faker->ipv4(),
            'consent_user_agent' => $this->faker->userAgent(),
        ];
    }

    /** Registered but not yet through the whole of section one. */
    public function partial(): static
    {
        return $this->state(fn () => [
            'furthest_step' => 3,
            'completed_at' => null,
        ]);
    }

    /** Address proved to belong to them — the gate on any MailerLite sync. */
    public function verified(): static
    {
        return $this->state(fn () => ['email_verified_at' => now()]);
    }

    public function unsubscribed(): static
    {
        return $this->state(fn () => [
            'email_verified_at' => now()->subDay(),
            'unsubscribed_at' => now(),
        ]);
    }
}
