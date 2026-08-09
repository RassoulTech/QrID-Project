<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'plan_id' => Plan::factory(),
            'starts_at' => now(),
            'ends_at' => now()->addDays(30),
            'status' => Subscription::STATUS_PENDING,
        ];
    }

    /** Abonnement actif, échéance dans 30 jours. */
    public function active(): static
    {
        return $this->state(fn () => [
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->addDays(25),
        ]);
    }

    /** Abonnement expiré depuis 10 jours. */
    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => Subscription::STATUS_EXPIRED,
            'starts_at' => now()->subDays(40),
            'ends_at' => now()->subDays(10),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => Subscription::STATUS_CANCELLED]);
    }

    /** Essai gratuit en cours. */
    public function trial(): static
    {
        return $this->state(fn () => [
            'plan_id' => Plan::factory()->trial(),
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->addDays(13),
        ]);
    }

    /** Actif mais arrivant à échéance dans N jours (test des relances). */
    public function expiringInDays(int $days): static
    {
        return $this->state(fn () => [
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now()->subDays(30 - $days),
            'ends_at' => now()->addDays($days),
        ]);
    }
}
