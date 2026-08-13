<?php

namespace App\Enums;

/**
 * Les six motifs d'alerte administrateur.
 *
 * Fermé volontairement : une alerte que personne n'a décidé d'envoyer est une
 * alerte que personne ne lira. Toute nouvelle raison de réveiller
 * l'administration passe par une entrée ici, donc par une décision.
 *
 * URGENT / NON URGENT — c'est la seule distinction qui compte, et elle décide
 * de la couleur du bandeau et du préfixe du sujet. Une inscription est une
 * bonne nouvelle qu'on lit le soir ; un paiement en échec appelle une action.
 * Sans cette séparation, les six motifs se ressemblent et l'on cesse de les
 * ouvrir — c'est ainsi que meurt un canal d'alerte.
 */
enum MotifAlerte: string
{
    case CompteConfirme = 'compte_confirme';
    case ProfilCree = 'profil_cree';
    case CarteActivee = 'carte_activee';
    case PaiementReussi = 'paiement_reussi';
    case PaiementEchoue = 'paiement_echoue';
    case TravailEnEchec = 'travail_en_echec';

    /** Titre affiché en tête du message et repris dans le sujet. */
    public function titre(): string
    {
        return match ($this) {
            self::CompteConfirme => 'Nouveau client inscrit',
            self::ProfilCree => 'Nouvelle carte créée',
            self::CarteActivee => 'Carte mise en ligne',
            self::PaiementReussi => 'Paiement encaissé',
            self::PaiementEchoue => 'Paiement en échec',
            self::TravailEnEchec => 'Traitement en échec',
        };
    }

    /**
     * Une alerte urgente appelle une action, pas une lecture.
     *
     * Seuls les deux motifs d'échec le sont. Marquer « nouveau client » comme
     * urgent serait la manière la plus rapide de rendre le mot inutile.
     */
    public function estUrgent(): bool
    {
        return match ($this) {
            self::PaiementEchoue, self::TravailEnEchec => true,
            default => false,
        };
    }
}
