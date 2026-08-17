<?php

namespace App\Listeners;

use App\Enums\MotifAlerte;
use App\Events\UserRegistered;
use App\Mail\WelcomeMail;
use App\Models\Plan;
use App\Services\AdminNotifier;
use App\Support\Courrier;

/**
 * Compte confirmé : on souhaite la bienvenue au client, on prévient l'équipe.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * AUCUNE DÉPENDANCE À L'ORDRE DES LISTENERS
 * ═══════════════════════════════════════════════════════════════════════
 * StartFreeTrial écoute le MÊME événement et ouvre l'essai. Ce message aimerait
 * donc en connaître la date de fin — mais Laravel ne garantit aucun ordre entre
 * deux listeners découverts automatiquement, et un ordre qui tient par hasard
 * sur le nom des fichiers finit toujours par se briser lors d'un renommage.
 *
 * On ne suppose donc rien : la date d'échéance est LUE en base. Si l'essai
 * n'existe pas encore — ou pas du tout, le plan ayant disparu — le message
 * part quand même, sans date. Un e-mail de bienvenue sans échéance reste
 * utile ; un e-mail annonçant une échéance inexistante serait un mensonge.
 */
class WelcomeNewClient
{
    public function __construct(private AdminNotifier $equipe) {}

    public function handle(UserRegistered $event): void
    {
        $user = $event->user;

        // La valeur peut avoir été mémorisée AVANT l'ouverture de l'essai.
        $user->forgetActiveSubscription();
        $abonnement = $user->activeSubscription();

        $duree = $abonnement?->plan?->duration_days
            ?? Plan::where('slug', 'essai-gratuit')->value('duration_days')
            ?? 15;

        Courrier::informer($user->email, new WelcomeMail(
            name: $user->name,
            createUrl: route('profile.create.step1'),
            trialDays: (int) $duree,
            trialEndsAt: $abonnement?->ends_at?->translatedFormat('j F Y'),

            // Le lien du groupe n'est proposé QU'ICI et sur le tableau de
            // bord : il donne accès à un espace réservé aux clients, et une
            // page publique le rendrait ouvert à tous.
            groupeUrl: config('automation.whatsapp_groupe'),
            recipient: $user->email,
        ));

        $this->equipe->alerter(
            MotifAlerte::CompteConfirme,
            [
                'Client' => $user->name,
                'Adresse' => $user->email,
                'Téléphone' => $user->phone ?: '—',
                'Inscrit le' => now()->translatedFormat('j F Y à H:i'),
            ],
            route('admin.clients.show', $user),
        );
    }
}
