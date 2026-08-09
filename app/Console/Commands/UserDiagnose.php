<?php

namespace App\Console\Commands;

use App\Models\PendingRegistration;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * Pourquoi ce compte ne se connecte-t-il pas ?
 *
 * Sept causes possibles, toutes vérifiées d'un coup. Sans cela, on avance à
 * tâtons dans phpMyAdmin en devinant laquelle bloque.
 */
class UserDiagnose extends Command
{
    protected $signature = 'user:diagnose {email} {--password= : Tester ce mot de passe contre le haché stocké}';

    protected $description = 'Diagnostique un compte qui ne peut pas se connecter';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('Aucun compte pour cette adresse.');

            // Un compte peut n'être qu'une inscription non confirmée.
            $enAttente = PendingRegistration::where('email', $this->argument('email'))->first();

            if ($enAttente) {
                $this->warn('En revanche, une inscription est EN ATTENTE de confirmation.');
                $this->line('  Créée le  : '.$enAttente->created_at);
                $this->line('  Expire le : '.$enAttente->expires_at);
                $this->newLine();
                $this->line('→ Confirmer sans e-mail : php artisan dev:confirm '.$this->argument('email'));
            }

            return self::FAILURE;
        }

        $hache = (string) $user->getAuthPassword();

        // Un hachage bcrypt commence par $2y$ et fait 60 caractères.
        $estBcrypt = preg_match('/^\$2[aby]\$\d{2}\$/', $hache) === 1 && strlen($hache) === 60;

        $lignes = [
            ['Identifiant', $user->id],
            ['Nom', $user->name],
            ['Adresse', $user->email],
            ['Téléphone', $user->phone ?: '—'],
            ['Rôle', $user->role ?? 'user'],
            ['Mot de passe haché en bcrypt', $estBcrypt ? 'OUI' : 'NON — STOCKÉ EN CLAIR OU CORROMPU'],
            ['Coût du hachage', $estBcrypt ? substr($hache, 4, 2) : '—'],
            ['E-mail vérifié', $user->email_verified_at ? 'OUI ('.$user->email_verified_at.')' : 'NON — le middleware « verified » bloque'],
            ['Compte suspendu', $user->is_blocked ? 'OUI — connexion refusée' : 'non'],
            ['Profil professionnel', $user->profile ? 'oui (#'.$user->profile->id.')' : 'aucun'],
            ['Abonnement actif', $user->hasActiveSubscription() ? 'oui' : 'aucun'],
        ];

        if ($mdp = $this->option('password')) {
            $lignes[] = ['Ce mot de passe correspond', Hash::check($mdp, $hache) ? 'OUI' : 'NON'];
        }

        $this->table(['Contrôle', 'Résultat'], $lignes);

        // Verdict.
        $bloquants = [];

        if (! $estBcrypt) {
            $bloquants[] = 'Le mot de passe n\'est pas un haché bcrypt valide. Auth::attempt échouera '
                .'toujours, sans erreur explicite. Cause habituelle : un seeder qui a écrit '
                .'Hash::make() sur un modèle dont le cast « hashed » a re-haché la valeur.';
        }

        if (! $user->email_verified_at) {
            $bloquants[] = 'E-mail non vérifié : la connexion réussit mais toute page protégée renvoie '
                .'sur l\'écran de vérification.';
        }

        if ($user->is_blocked) {
            $bloquants[] = 'Compte suspendu : la connexion est refusée avec un message explicite.';
        }

        if ($bloquants === []) {
            $this->info('Aucun blocage côté compte. Si la connexion échoue, le mot de passe saisi '
                .'ne correspond pas — vérifiez-le avec --password=…');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->error('Blocages identifiés :');
        foreach ($bloquants as $i => $b) {
            $this->line('  '.($i + 1).'. '.$b);
        }
        $this->newLine();
        $this->line('→ Tout corriger d\'un coup : php artisan user:repair '.$user->email.' --password=MonNouveauMotDePasse');

        return self::FAILURE;
    }
}
