<?php

namespace App\Support;

/**
 * CATALOGUE DES ACTIONS JOURNALISÉES.
 *
 * `admin_actions.action` est une chaîne libre en base. Une chaîne libre écrite
 * à la main dans dix contrôleurs finit toujours par diverger : « block_user »
 * ici, « user_blocked » là, et le filtre du journal d'audit rate la moitié des
 * entrées sans que rien ne signale l'erreur.
 *
 * Cette classe est la seule source des valeurs autorisées. Le filtre du
 * journal se construit à partir d'elle, donc un type ajouté ici apparaît dans
 * la liste déroulante sans autre intervention.
 *
 * LE TON : le libellé décrit ce que l'administrateur A FAIT, au passé et à
 * l'actif. « Compte bloqué » et non « Blocage » — on lit un journal pour
 * savoir qui a fait quoi, pas pour consulter une nomenclature.
 */
final class AdminActionType
{
    public const BLOCAGE_COMPTE = 'block_user';

    public const DEBLOCAGE_COMPTE = 'unblock_user';

    public const DESACTIVATION_PROFIL = 'deactivate_profile';

    public const REACTIVATION_PROFIL = 'reactivate_profile';

    public const PROLONGATION_ABONNEMENT = 'extend_subscription';

    public const VERIFICATION_PAIEMENT = 'verify_payment';

    public const MODELE_ACTIVE = 'toggle_template';

    public const MODELE_DUPLIQUE = 'duplicate_template';

    public const MODELE_PAR_DEFAUT = 'default_template';

    public const PLAN_CREE = 'create_plan';

    public const PLAN_MODIFIE = 'update_plan';

    /**
     * Libellés affichés dans le journal.
     *
     * @return array<string, string>
     */
    public static function libelles(): array
    {
        return [
            self::BLOCAGE_COMPTE => 'Compte bloqué',
            self::DEBLOCAGE_COMPTE => 'Compte débloqué',
            self::DESACTIVATION_PROFIL => 'Profil désactivé',
            self::REACTIVATION_PROFIL => 'Profil réactivé',
            self::PROLONGATION_ABONNEMENT => 'Abonnement prolongé',
            self::VERIFICATION_PAIEMENT => 'Paiement vérifié',
            self::MODELE_ACTIVE => 'Modèle activé ou désactivé',
            self::MODELE_DUPLIQUE => 'Modèle dupliqué',
            self::MODELE_PAR_DEFAUT => 'Modèle défini par défaut',
            self::PLAN_CREE => 'Plan créé',
            self::PLAN_MODIFIE => 'Plan modifié',
        ];
    }

    public static function libelle(string $action): string
    {
        // Repli sur la valeur brute : une entrée écrite par une version
        // antérieure doit rester lisible, pas disparaître de l'affichage.
        return self::libelles()[$action] ?? $action;
    }

    /**
     * Couleur du badge. Trois familles seulement — au-delà, un journal
     * devient un arc-en-ciel où plus rien ne ressort.
     *
     *   danger  · ce qui retire un droit ou coupe un accès
     *   attention · ce qui accorde ou modifie une contrepartie payante
     *   neutre  · le reste, y compris les vérifications
     */
    public static function ton(string $action): string
    {
        return match ($action) {
            self::BLOCAGE_COMPTE, self::DESACTIVATION_PROFIL => 'danger',
            self::PROLONGATION_ABONNEMENT, self::PLAN_CREE, self::PLAN_MODIFIE => 'attention',
            default => 'neutre',
        };
    }

    /** @return list<string> */
    public static function toutes(): array
    {
        return array_keys(self::libelles());
    }
}
