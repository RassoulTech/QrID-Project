<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PlanFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name.'-'.fake()->unique()->numberBetween(1, 9999)),
            'price_fcfa' => 2500,
            'duration_days' => 30,
            'features' => ['Profil professionnel', 'QR Code illimité'],
            'is_active' => true,
        ];
    }

    /** Formule d'essai gratuit : 0 FCFA, 15 jours. */
    public function trial(): static
    {
        return $this->state(fn () => [
            'name' => 'Essai gratuit',
            'price_fcfa' => 0,
            'duration_days' => 15,
        ]);
    }

    public function yearly(): static
    {
        return $this->state(fn () => [
            'name' => 'Annuel',
            'price_fcfa' => 25000,
            'duration_days' => 365,
        ]);
    }
}
