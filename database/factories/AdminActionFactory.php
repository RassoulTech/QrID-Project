<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdminActionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'admin_id' => User::factory()->state(['role' => User::ROLE_ADMIN]),
            'action' => fake()->randomElement([
                'suspend_profile', 'restore_profile', 'refund_payment', 'extend_subscription',
            ]),
            'target_type' => null,
            'target_id' => null,
            'reason' => fake()->sentence(),
            'created_at' => now(),
        ];
    }
}
