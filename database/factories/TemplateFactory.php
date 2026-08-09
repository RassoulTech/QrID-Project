<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TemplateFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name.'-'.fake()->unique()->numberBetween(1, 9999)),
            'preview_path' => null,
            'is_premium' => false,
            'is_active' => true,
        ];
    }

    public function premium(): static
    {
        return $this->state(fn () => ['is_premium' => true]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
