<?php

namespace Database\Factories;

use App\Models\WaitlistSignup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WaitlistSignup>
 */
class WaitlistSignupFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'source' => 'coming-soon',
            'consent_version' => config('frith.consent.version'),
            'consent_text' => config('frith.consent.text'),
            'consented_at' => now(),
            'consent_ip' => $this->faker->ipv4(),
            'consent_user_agent' => $this->faker->userAgent(),
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn () => [
            'confirmation_sent_at' => now()->subDay(),
            'confirmed_at' => now()->subDay(),
        ]);
    }

    public function unsubscribed(): static
    {
        return $this->state(fn () => [
            'confirmation_sent_at' => now()->subDays(2),
            'confirmed_at' => now()->subDays(2),
            'unsubscribed_at' => now()->subDay(),
        ]);
    }
}
