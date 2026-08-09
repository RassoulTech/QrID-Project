<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'subscription_id' => Subscription::factory(),
            'provider' => 'paydunya',
            'provider_ref' => 'REF-'.Str::upper(Str::random(12)),
            'method' => fake()->randomElement(array_keys(Payment::METHODS)),
            'amount_fcfa' => 2500,
            'status' => Payment::STATUS_PENDING,
            'payload' => null,
        ];
    }

    public function successful(): static
    {
        return $this->state(fn () => [
            'status' => Payment::STATUS_SUCCESS,
            'payload' => ['message' => 'Transaction acceptée', 'code' => '00'],
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => Payment::STATUS_FAILED,
            'payload' => ['message' => 'Solde insuffisant', 'code' => '51'],
        ]);
    }

    public function wave(): static
    {
        return $this->state(fn () => ['method' => Payment::METHOD_WAVE]);
    }

    public function orangeMoney(): static
    {
        return $this->state(fn () => ['method' => Payment::METHOD_ORANGE_MONEY]);
    }

    /** Paiement orphelin : le compte a été supprimé, la pièce comptable subsiste. */
    public function orphaned(): static
    {
        return $this->state(fn () => [
            'user_id' => null,
            'subscription_id' => null,
        ]);
    }
}
