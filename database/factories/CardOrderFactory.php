<?php

namespace Database\Factories;

use App\Models\CardOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CardOrder>
 */
class CardOrderFactory extends Factory
{
    protected $model = CardOrder::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'profile_id' => null,
            'status' => CardOrder::STATUS_PENDING,
            'recipient_name' => 'Awa Ndiaye',
            'phone' => '+221773831364',
            'address_line' => 'Cité Keur Gorgui, villa 42',
            'city' => 'Dakar',
            'region' => 'Dakar',
        ];
    }

    /** Une commande payée dont le client n'a jamais renseigné la livraison. */
    public function sansAdresse(): static
    {
        return $this->state(fn () => [
            'address_line' => null,
            'city' => null,
        ]);
    }

    public function statut(string $statut): static
    {
        return $this->state(fn () => ['status' => $statut]);
    }
}
