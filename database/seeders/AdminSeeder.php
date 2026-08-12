<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * COMPTES ADMINISTRATEURS.
 *
 * Deux sources, cumulables :
 *
 *   ADMIN_EMAIL / ADMIN_PASSWORD  le compte principal, historique
 *   ADMIN_TEAM / ADMIN_TEAM_PASSWORD  les autres membres de l'équipe
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LE MOT DE PASSE N'EST POSÉ QU'À LA CRÉATION
 * ═══════════════════════════════════════════════════════════════════════
 * C'est le point important de ce fichier, et l'ancienne version s'y trompait.
 *
 * Elle appelait updateOrCreate() en plaçant le mot de passe dans les valeurs
 * mises à jour. Or ce seeder tourne à CHAQUE démarrage du conteneur : le mot
 * de passe était donc réécrit à chaque déploiement. Quelqu'un qui changeait
 * le sien depuis « Mon compte » le retrouvait remplacé par le mot de passe
 * par défaut à la mise en ligne suivante — sans aucun message.
 *
 * Autrement dit : la promesse « modifiable dès la première connexion » était
 * fausse, et le défaut n'apparaissait qu'après un déploiement, longtemps
 * après le changement.
 *
 * Le rôle et la vérification d'adresse, eux, SONT réappliqués à chaque
 * passage : ils ne sont pas censés être modifiés par l'intéressé, et les
 * réaffirmer répare un compte dégradé à la main.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI DES VARIABLES ET NON UNE COMMANDE
 * ═══════════════════════════════════════════════════════════════════════
 * Le plan gratuit de Render n'ouvre ni accès SSH ni tâche ponctuelle. Le
 * seul moment où l'on peut écrire en base est le démarrage du conteneur —
 * donc ce seeder. Ajouter une adresse à ADMIN_TEAM puis redéployer est le
 * chemin praticable ; `php artisan admin:create` ne serait exécutable nulle
 * part.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $crees = 0;
        $existants = 0;

        // --- Le compte principal -------------------------------------------
        $email = config('registration.admin.email');
        $motDePasse = config('registration.admin.password');

        if ($email && $motDePasse) {
            $this->poser(
                $email,
                config('registration.admin.name') ?: 'Administrateur',
                $motDePasse,
                config('registration.admin.phone'),
                $crees,
                $existants
            );
        } else {
            $this->command?->warn(
                'AdminSeeder : ADMIN_EMAIL ou ADMIN_PASSWORD absent, compte principal ignoré.'
            );
        }

        // --- L'équipe -------------------------------------------------------
        foreach ($this->equipe() as [$adresse, $nom]) {
            $this->poser($adresse, $nom, $this->motDePasseEquipe(), null, $crees, $existants);
        }

        $this->command?->info(sprintf(
            'AdminSeeder : %d compte(s) créé(s), %d déjà en place (mot de passe intact).',
            $crees,
            $existants
        ));
    }

    /**
     * Analyse ADMIN_TEAM.
     *
     *   "a@b.sn|Prénom Nom, c@d.sn|Autre Nom"
     *
     * Une entrée mal formée est SIGNALÉE et ignorée, jamais silencieusement
     * écartée : un administrateur qui n'apparaît pas sans explication se
     * découvre le jour où il essaie de se connecter.
     *
     * @return list<array{string, string}>
     */
    private function equipe(): array
    {
        $brut = config('registration.team.members');

        if (! $brut) {
            return [];
        }

        $membres = [];

        foreach (explode(',', $brut) as $entree) {
            $entree = trim($entree);

            if ($entree === '') {
                continue;
            }

            [$adresse, $nom] = array_pad(array_map('trim', explode('|', $entree, 2)), 2, null);

            if (! $adresse || ! filter_var($adresse, FILTER_VALIDATE_EMAIL)) {
                $this->command?->warn("AdminSeeder : entrée ignorée, adresse invalide — « {$entree} »");

                continue;
            }

            $membres[] = [$adresse, $nom ?: 'Administrateur'];
        }

        return $membres;
    }

    /**
     * Le mot de passe initial de l'équipe, refusé s'il est trop court.
     *
     * On échoue FORT plutôt que de créer des comptes d'administration avec un
     * secret faible : ces comptes peuvent bloquer un client, désactiver un
     * profil et changer les tarifs.
     */
    private function motDePasseEquipe(): string
    {
        $motDePasse = config('registration.team.password');
        $minimum = config('registration.team.longueur_minimale');

        if (! $motDePasse) {
            throw new \RuntimeException(
                'ADMIN_TEAM est renseignée mais ADMIN_TEAM_PASSWORD est absente. '
                .'Aucun compte d\'équipe ne peut être créé sans mot de passe initial.'
            );
        }

        if (mb_strlen($motDePasse) < $minimum) {
            throw new \RuntimeException(sprintf(
                'ADMIN_TEAM_PASSWORD fait %d caractères, le minimum est %d. '
                .'Un compte d\'administration peut bloquer un client et modifier les tarifs.',
                mb_strlen($motDePasse),
                $minimum
            ));
        }

        return $motDePasse;
    }

    /**
     * Crée le compte s'il n'existe pas ; sinon, ne touche PAS au mot de passe.
     */
    private function poser(
        string $email,
        string $nom,
        string $motDePasse,
        ?string $telephone,
        int &$crees,
        int &$existants
    ): void {
        $email = mb_strtolower(trim($email));

        $compte = User::where('email', $email)->first();

        if ($compte === null) {
            User::create([
                'email' => $email,
                'name' => $nom,
                'phone' => $telephone,
                'role' => User::ROLE_ADMIN,
                'password' => Hash::make($motDePasse),
                // Sans cela, le middleware `verified` refuserait l'accès à
                // l'espace d'administration à son propre administrateur.
                'email_verified_at' => now(),
            ]);

            $crees++;
            $this->command?->info("  + créé   : {$email}");

            return;
        }

        // Le compte existe : on réaffirme le rôle et la vérification, jamais
        // le mot de passe.
        $compte->forceFill([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => $compte->email_verified_at ?? now(),
            'is_blocked' => false,
        ])->save();

        $existants++;
        $this->command?->line("  = en place : {$email} (mot de passe inchangé)");
    }
}
