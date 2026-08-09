<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\SocialLink;
use Illuminate\Database\Eloquent\Factories\Factory;

class SocialLinkFactory extends Factory
{
    public function definition(): array
    {
        $platform = fake()->randomElement(array_keys(SocialLink::PLATFORMS));

        return [
            'profile_id' => Profile::factory(),
            'platform' => $platform,
            'url' => 'https://'.$platform.'.com/'.fake()->userName(),
            'position' => fake()->numberBetween(0, 5),
        ];
    }
}
