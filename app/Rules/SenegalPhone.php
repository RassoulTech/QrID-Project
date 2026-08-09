<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SenegalPhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (self::normalize($value) === null) {
            $fail(__('Numéro de téléphone sénégalais invalide. Exemple : 77 383 13 64.'));
        }
    }

    /**
     * Normalise une saisie humaine en numéro canonique +221XXXXXXXXX.
     * Retourne null si ce n'est objectivement pas un mobile sénégalais.
     *
     * Acceptés (→ +221773831364) :
     *   +221773831364 · 00221773831364 · 221773831364 · 0773831364 · 773831364
     *   77 383 13 64 · 77-383-13-64 · +221 77 383 13 64 · (+221) 77 383 13 64
     */
    public static function normalize(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $s = (string) $value;

        // 1. Retirer espaces (dont insécables), points, tirets, parenthèses.
        $s = preg_replace('/[\s\.\-\(\)\x{00A0}]/u', '', $s);

        // 2. Retirer un éventuel indicatif international.
        if (str_starts_with($s, '+221')) {
            $s = substr($s, 4);
        } elseif (str_starts_with($s, '00221')) {
            $s = substr($s, 5);
        } elseif (str_starts_with($s, '221') && strlen($s) > 9) {
            $s = substr($s, 3);
        }

        // 3. Retirer un zéro initial de courtoisie.
        if (str_starts_with($s, '0')) {
            $s = substr($s, 1);
        }

        // 4. Ne conserver que les chiffres.
        $s = preg_replace('/\D+/', '', (string) $s);

        // 5. Contrôle final : 9 chiffres, préfixe mobile 70/75/76/77/78.
        if (preg_match('/^7[05678][0-9]{7}$/', $s)) {
            return '+221'.$s;
        }

        return null;
    }
}
