<?php

namespace App\Console\Commands\Dev;

use App\Events\UserRegistered;
use App\Models\PendingRegistration;
use App\Models\User;
use App\Rules\SenegalPhone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Crée directement un utilisateur vérifié, en court-circuitant tout le flux
 * d'inscription (formulaire, e-mail, confirmation). Déclenche l'essai gratuit.
 */
class DevUser extends DevCommand
{
    protected $signature = 'dev:user {email}
                            {--name= : Nom complet (défaut : dérivé de l\'e-mail)}
                            {--phone=770000000 : Numéro sénégalais}
                            {--password=password : Mot de passe en clair}';

    protected $description = '[LOCAL] Crée un utilisateur vérifié sans passer par le flux d\'inscription.';

    public function handle(): int
    {
        if (! $this->guardLocal()) {
            return self::FAILURE;
        }

        $email = mb_strtolower(trim($this->argument('email')));
        $password = (string) $this->option('password');
        $name = (string) ($this->option('name') ?: ucfirst(strstr($email, '@', true) ?: 'Utilisateur'));

        $phone = SenegalPhone::normalize($this->option('phone'));

        if ($phone === null) {
            $this->error('Numéro invalide. Exemple : 77 383 13 64.');

            return self::FAILURE;
        }

        if (User::where('email', $email)->exists()) {
            $this->warn("Un compte existe déjà pour {$email}.");
            $this->line("Connexion : {$email} / (mot de passe existant)");

            return self::SUCCESS;
        }

        $user = DB::transaction(function () use ($name, $email, $phone, $password) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'password' => Hash::make($password),
            ]);

            $user->forceFill(['email_verified_at' => now()])->save();

            // Nettoie une éventuelle demande en attente sur la même adresse.
            PendingRegistration::where('email', $email)->delete();

            return $user;
        });

        event(new UserRegistered($user));

        $this->newLine();
        $this->info('Utilisateur créé et vérifié.');
        $this->table(
            ['Champ', 'Valeur'],
            [
                ['ID', $user->id],
                ['Nom', $user->name],
                ['E-mail', $user->email],
                ['Téléphone', $user->formatted_phone],
                ['Mot de passe', $password],
                ['Vérifié le', $user->email_verified_at],
            ]
        );
        $this->line('Connexion : '.route('login'));
        $this->newLine();

        return self::SUCCESS;
    }
}
