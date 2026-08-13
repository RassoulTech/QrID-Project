<?php

namespace Database\Factories;

use App\Enums\VarianteCarte;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProfileFactory extends Factory
{
    public function definition(): array
    {
        $first = fake()->firstName();
        $last = fake()->lastName();

        return [
            'user_id' => User::factory(),
            'slug' => Str::slug($first.'-'.$last.'-'.fake()->unique()->numberBetween(1, 99999)),
            'first_name' => $first,
            'last_name' => $last,
            'job_title' => fake()->jobTitle(),
            'company' => fake()->company(),
            'bio' => fake()->sentences(2, true),
            'phone' => $this->senegalPhone(),
            'whatsapp' => $this->senegalPhone(),
            'public_email' => fake()->unique()->safeEmail(),
            'website' => null,
            'address' => fake()->city(),
            'photo_path' => null,
            'template_id' => Template::factory(),
            // Toujours une variante réelle : une fabrique qui produirait une
            // teinte inexistante ferait passer des tests sur un état
            // impossible en production.
            'primary_color' => VarianteCarte::DEFAUT->value,
            'is_active' => false,
            'slug_changed_at' => null,
        ];
    }

    /** Profil publié (drapeau actif). */
    public function published(): static
    {
        return $this->state(fn () => ['is_active' => true]);
    }

    /** Profil brouillon, non publié. */
    public function draft(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    /** Slug déjà modifié : ne peut plus l'être. */
    public function slugAlreadyChanged(): static
    {
        return $this->state(fn () => ['slug_changed_at' => now()->subDays(3)]);
    }

    private function senegalPhone(): string
    {
        return '+2217'.fake()->randomElement(['0', '5', '6', '7', '8']).fake()->numerify('#######');
    }
}
