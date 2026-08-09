<?php

namespace App\Console\Commands\Dev;

use App\Models\User;
use Illuminate\Support\Facades\Password;

/**
 * Génère et affiche l'URL de réinitialisation de mot de passe d'un utilisateur.
 */
class DevReset extends DevCommand
{
    protected $signature = 'dev:reset {email}';

    protected $description = '[LOCAL] Affiche l\'URL de réinitialisation de mot de passe.';

    public function handle(): int
    {
        if (! $this->guardLocal()) {
            return self::FAILURE;
        }

        $email = mb_strtolower(trim($this->argument('email')));

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("Aucun compte pour {$email}.");
            $this->line('Créer un compte de test : php artisan dev:user '.$email);

            return self::FAILURE;
        }

        // Crée un jeton valide dans password_reset_tokens.
        $token = Password::broker()->createToken($user);

        $url = route('password.reset', ['token' => $token, 'email' => $user->email]);

        $this->newLine();
        $this->info('URL de réinitialisation pour '.$user->email.' :');
        $this->newLine();
        $this->line('<comment>'.$url.'</comment>');
        $this->newLine();
        $this->line('Valable '.config('auth.passwords.users.expire', 60).' minutes.');
        $this->newLine();

        return self::SUCCESS;
    }
}
