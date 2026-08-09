<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\ProfileEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProfileEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'profile_id' => Profile::factory(),
            'type' => fake()->randomElement([
                ProfileEvent::TYPE_VIEW,
                ProfileEvent::TYPE_SCAN,
                ProfileEvent::TYPE_SAVE,
            ]),
            'ip_hash' => hash('sha256', fake()->ipv4().config('app.key')),
            'user_agent' => fake()->userAgent(),
            'referer' => null,
            'created_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function view(): static
    {
        return $this->state(fn () => ['type' => ProfileEvent::TYPE_VIEW]);
    }

    /** Arrivée par scan de QR Code : pas de référent. */
    public function scan(): static
    {
        return $this->state(fn () => [
            'type' => ProfileEvent::TYPE_SCAN,
            'referer' => null,
        ]);
    }

    public function save(): static
    {
        return $this->state(fn () => ['type' => ProfileEvent::TYPE_SAVE]);
    }
}
