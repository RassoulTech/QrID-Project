<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Crée le compte administrateur depuis le .env.
 *
 * Variables attendues :
 *   ADMIN_EMAIL, ADMIN_PASSWORD, ADMIN_NAME (facultatif), ADMIN_PHONE (facultatif)
 *
 * Sans ADMIN_EMAIL, le seeder ne fait rien : aucun compte privilégié n'est
 * créé « par surprise » avec un mot de passe deviné.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('registration.admin.email');
        $password = config('registration.admin.password');

        if (! $email || ! $password) {
            $this->command?->warn(
                'AdminSeeder ignoré : ADMIN_EMAIL et ADMIN_PASSWORD absents du .env.'
            );

            return;
        }

        $user = User::updateOrCreate(
            ['email' => mb_strtolower($email)],
            [
                'name' => config('registration.admin.name'),
                'phone' => config('registration.admin.phone'),
                'role' => User::ROLE_ADMIN,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );

        $this->command?->info("Administrateur prêt : {$user->email}");
    }
}
