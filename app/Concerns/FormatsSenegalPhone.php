<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * Accesseurs d'affichage pour les numéros stockés au format canonique
 * +221XXXXXXXXX. Le format lisible n'est jamais ce qui est persisté.
 *
 * Réutilisable par User (phone) et, à l'étape 3, par Profile (phone, whatsapp).
 */
trait FormatsSenegalPhone
{
    /** Accesseur : $model->formatted_phone → "+221 77 383 13 64" */
    protected function formattedPhone(): Attribute
    {
        return Attribute::get(fn () => static::formatSenegalPhone($this->phone));
    }

    /** "+221773831364" → "+221 77 383 13 64" */
    public static function formatSenegalPhone(?string $canonical): ?string
    {
        if (! $canonical || ! preg_match('/^\+221(\d{9})$/', $canonical, $m)) {
            return $canonical;
        }

        $d = $m[1];

        return sprintf('+221 %s %s %s %s',
            substr($d, 0, 2), substr($d, 2, 3), substr($d, 5, 2), substr($d, 7, 2));
    }

    /** Lien appel : "tel:+221773831364" */
    public static function senegalTelHref(?string $canonical): ?string
    {
        return $canonical ? 'tel:'.$canonical : null;
    }

    /** Lien WhatsApp : "https://wa.me/221773831364" */
    public static function senegalWhatsappHref(?string $canonical): ?string
    {
        if (! $canonical) {
            return null;
        }

        return 'https://wa.me/'.ltrim($canonical, '+');
    }
}
