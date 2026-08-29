<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Profile;
use App\Models\ProfileEvent;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\QueryException;

/**
 * Émission des notifications — uniquement sur des faits vérifiés en base.
 *
 * Chaque méthode part d'un événement qui S'EST PRODUIT : un paiement encaissé,
 * une échéance qui approche, une première consultation. Rien n'est prédit,
 * rien n'est inventé.
 *
 * IDEMPOTENCE : chaque notification porte une clé d'unicité (compte + type +
 * jour ou identifiant). Sans elle, l'alerte d'expiration reviendrait à chaque
 * chargement de page et le client cesserait de les lire — un canal saturé ne
 * vaut pas mieux qu'un canal muet.
 */
class NotificationService
{
    public function paiementValide(User $user, int $montantFcfa): void
    {
        $this->poser($user, [
            'type' => Notification::TYPE_PAIEMENT,
            'title' => __('dashboard.notifs.paiement_confirme'),
            'body' => number_format($montantFcfa, 0, ',', ' ').' FCFA · votre carte est en ligne.',
            'url' => route('dashboard'),
            'cle_unicite' => Notification::TYPE_PAIEMENT.':'.now()->format('YmdHis'),
        ]);
    }

    /** Relance d'échéance. Une seule par jour, quel que soit le nombre de visites. */
    public function abonnementExpireBientot(User $user, Subscription $abonnement): void
    {
        $jours = $abonnement->daysRemaining();

        if ($jours === null || $jours > 7) {
            return;
        }

        $this->poser($user, [
            'type' => Notification::TYPE_EXPIRATION,
            'title' => $jours === 0 ? 'Votre abonnement expire aujourd\'hui' : "Votre abonnement expire dans {$jours} jour".($jours > 1 ? 's' : ''),
            'body' => 'Passé cette date, votre carte ne sera plus consultable.',
            'url' => route('abonnement.paiement'),
            'cle_unicite' => Notification::TYPE_EXPIRATION.':'.now()->toDateString(),
        ]);
    }

    /** La toute première consultation d'une carte — elle n'arrive qu'une fois. */
    public function premiereVue(User $user, Profile $profile): void
    {
        $this->poser($user, [
            'type' => Notification::TYPE_PREMIERE_VUE,
            'title' => __('dashboard.notifs.carte_consultee'),
            'body' => 'Quelqu\'un vient d\'ouvrir votre carte pour la première fois.',
            'url' => route('dashboard'),
            'cle_unicite' => Notification::TYPE_PREMIERE_VUE.':'.$profile->id,
        ]);
    }

    /** Un contact enregistré : le geste qui prouve que la carte a servi. */
    public function contactEnregistre(User $user, ProfileEvent $evenement): void
    {
        $this->poser($user, [
            'type' => Notification::TYPE_CONTACT,
            'title' => __('dashboard.notifs.contact_enregistre'),
            'body' => 'Vos coordonnées viennent d\'être ajoutées à un répertoire.',
            'url' => route('dashboard'),
            'cle_unicite' => Notification::TYPE_CONTACT.':'.$evenement->id,
        ]);
    }

    /**
     * Écriture protégée par la clé d'unicité.
     *
     * On tente l'insertion et on absorbe le conflit, plutôt que de vérifier
     * puis d'insérer : entre les deux, une seconde requête peut passer. C'est
     * la contrainte de base qui tranche, pas une lecture optimiste.
     */
    private function poser(User $user, array $attributs): void
    {
        try {
            Notification::create($attributs + ['user_id' => $user->id]);
        } catch (QueryException $e) {
            // 23000 = violation de contrainte d'unicité : la notification
            // existe déjà, il n'y a rien à faire et rien à signaler.
            if (! str_starts_with((string) $e->getCode(), '23')) {
                throw $e;
            }
        }
    }
}
