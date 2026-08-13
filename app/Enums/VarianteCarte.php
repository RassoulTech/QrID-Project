<?php

namespace App\Enums;

/**
 * LES DEUX SEULES CARTES QUI EXISTENT.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI LE CLIENT NE CHOISIT PLUS SA COULEUR
 * ═══════════════════════════════════════════════════════════════════════
 * Chaque carte imprimée est un support de communication pour la plateforme.
 * Cinq teintes au choix produisaient cinq marques différentes : celui qui
 * reçoit une carte ambre et une carte grenat ne voit pas deux clients d'un
 * même service, il voit deux services.
 *
 * Le nuancier a donc disparu au profit de DEUX VARIANTES, présentées comme
 * deux cartes et non comme des pastilles de couleur. La différence de
 * formulation compte : un nuancier invite à composer, deux aperçus invitent
 * à choisir.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LA COLONNE primary_color EST CONSERVÉE
 * ═══════════════════════════════════════════════════════════════════════
 * Elle ne porte plus une teinte libre mais la COULEUR DE FOND de la variante,
 * et n'accepte plus que ces deux valeurs. Conserver la colonne évite une
 * migration de données sur une table déjà en production, et la valeur reste
 * lisible telle quelle par le CSS comme par DomPDF.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * UN AVERTISSEMENT D'IMPRESSION À CONNAÎTRE
 * ═══════════════════════════════════════════════════════════════════════
 * La norme ISO/IEC 18004 décrit un QR Code SOMBRE sur fond CLAIR. La variante
 * BLANCHE respecte cette description ; la variante VERTE l'inverse.
 *
 * Les lecteurs modernes gèrent l'inversion, d'autres non, et leur échec est
 * SILENCIEUX — le porteur de la carte croit simplement que son code est
 * mauvais. À qualité d'impression égale, la variante blanche est donc la plus
 * sûre. Ce n'est pas une raison de retirer la verte, qui porte l'identité de
 * la marque : c'est une raison de le savoir avant de commander cinq cents
 * exemplaires.
 */
enum VarianteCarte: string
{
    case Verte = '#0B3B2E';
    case Blanche = '#FFFFFF';

    /** La variante servie à qui n'a rien choisi. */
    public const DEFAUT = self::Verte;

    public function libelle(): string
    {
        return match ($this) {
            self::Verte => 'Verte',
            self::Blanche => 'Blanche',
        };
    }

    /** Ce qui distingue les deux, en une phrase, pour l'écran de choix. */
    public function description(): string
    {
        return match ($this) {
            self::Verte => 'Fond vert profond, texte et QR Code en blanc.',
            self::Blanche => 'Fond blanc, texte et QR Code en vert profond.',
        };
    }

    /** Couleur de fond de la carte. Identique à la valeur stockée. */
    public function fond(): string
    {
        return $this->value;
    }

    /** Couleur du texte et des modules du QR Code. */
    public function encre(): string
    {
        return match ($this) {
            self::Verte => '#FFFFFF',
            self::Blanche => '#0B3B2E',
        };
    }

    /**
     * Le QR du recto est-il inversé par rapport à la norme ?
     *
     * Vrai pour la verte — modules clairs sur fond sombre. Cette réponse
     * commande la génération du code ET l'avertissement affiché avant
     * téléchargement du fichier d'impression.
     */
    public function qrInverse(): bool
    {
        return $this === self::Verte;
    }

    /**
     * Résout une valeur stockée, quelle qu'elle soit.
     *
     * TOLÉRANT PAR NÉCESSITÉ, ET NON PAR FACILITÉ. Des profils portent encore
     * « #7A3E12 » ou « #0E5F73 » — les teintes de l'ancien nuancier. La
     * migration les normalise, mais une valeur inattendue ne doit jamais
     * produire une exception sur la page publique d'un client : elle
     * retombe sur la variante par défaut.
     */
    public static function depuis(?string $valeur): self
    {
        return self::tryFrom(mb_strtoupper((string) $valeur)) ?? self::DEFAUT;
    }

    /** @return array<int, self> */
    public static function toutes(): array
    {
        return self::cases();
    }
}
