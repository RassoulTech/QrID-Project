<?php

namespace App\Enums;

/**
 * Cas rencontrés lors d'une demande d'inscription.
 *
 * Cette information est JOURNALISÉE mais n'est JAMAIS exposée à l'utilisateur :
 * l'écran affiché est rigoureusement identique dans tous les cas. Elle sert au
 * support (« quel e-mail ce client a-t-il reçu ? ») et aux tests.
 */
enum RegistrationOutcome: string
{
    /** Cas 1 — adresse inconnue : demande créée, e-mail de confirmation. */
    case Created = 'created';

    /** Cas 2 — un compte existe déjà : rien créé, e-mail « vous avez déjà un compte ». */
    case AccountExists = 'account_exists';

    /** Cas 3 — demande en attente valide : jeton régénéré, e-mail renvoyé. */
    case PendingRefreshed = 'pending_refreshed';

    /** Cas 3 bis — limite de renvois atteinte : aucun e-mail envoyé. */
    case ResendLimitReached = 'resend_limit_reached';

    /** Cas 4 — demande expirée : ancienne supprimée, nouvelle créée. */
    case ExpiredReplaced = 'expired_replaced';

    /** Rate limiting global (IP ou adresse) : aucun e-mail envoyé. */
    case RateLimited = 'rate_limited';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Demande créée, e-mail de confirmation envoyé',
            self::AccountExists => 'Compte déjà existant, e-mail d\'information envoyé',
            self::PendingRefreshed => 'Demande existante rafraîchie, e-mail renvoyé',
            self::ResendLimitReached => 'Limite de renvois atteinte, aucun e-mail',
            self::ExpiredReplaced => 'Demande expirée remplacée, e-mail de confirmation envoyé',
            self::RateLimited => 'Trop de tentatives, aucun e-mail',
        };
    }
}
