<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

/**
 * LA LANGUE COURANTE — un seul endroit décide.
 *
 * Deux mémoires, dans cet ordre, exactement comme pour le thème :
 *
 * 1. le COMPTE, si quelqu'un est connecté. La préférence suit alors la
 *    personne : elle la retrouve sur son téléphone comme sur son poste ;
 * 2. un COOKIE, sinon. Un visiteur de la landing n'a aucun compte où écrire
 *    quoi que ce soit — sans cookie, le sélecteur lui serait inutile.
 *
 * Le cookie est écrit dans les DEUX cas. C'est ce qui permet au tout premier
 * rendu après connexion d'être déjà dans la bonne langue, avant même que la
 * session ne soit lue — donc sans le clignotement d'une page qui se
 * retraduirait sous les yeux.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LA PAGE PUBLIQUE SUIT LE VISITEUR, PAS LE PROPRIÉTAIRE
 * ═══════════════════════════════════════════════════════════════════════
 * Un client sénégalais qui a choisi le français garde une carte lisible par un
 * correspondant anglophone : c'est ce dernier qui la lit, et c'est donc SA
 * langue qui doit habiller les libellés. Le contenu du profil, lui, reste tel
 * que son propriétaire l'a écrit — on ne traduit pas une fonction ni un nom
 * d'entreprise.
 */
class Langue
{
    public const FRANCAIS = 'fr';

    public const ANGLAIS = 'en';

    /** Un an : une préférence de langue n'a pas à être redemandée. */
    public const DUREE_COOKIE = 60 * 24 * 365;

    private const COOKIE = 'langue';

    /** @return list<string> */
    public static function disponibles(): array
    {
        return [self::FRANCAIS, self::ANGLAIS];
    }

    /** Libellés courts, dans LEUR propre langue — jamais traduits. */
    public static function libelles(): array
    {
        return [
            self::FRANCAIS => 'Français',
            self::ANGLAIS => 'English',
        ];
    }

    public static function courante(): string
    {
        $utilisateur = Auth::user();

        if ($utilisateur !== null && self::valide($utilisateur->locale)) {
            return $utilisateur->locale;
        }

        $cookie = Cookie::get(self::COOKIE);

        return self::valide($cookie) ? $cookie : self::FRANCAIS;
    }

    public static function valide(?string $code): bool
    {
        return $code !== null && in_array($code, self::disponibles(), true);
    }

    /** La langue vers laquelle bascule le sélecteur — l'autre. */
    public static function inverse(): string
    {
        return self::courante() === self::FRANCAIS ? self::ANGLAIS : self::FRANCAIS;
    }

    public static function libelle(?string $code = null): string
    {
        return self::libelles()[$code ?? self::courante()] ?? 'Français';
    }

    /** Le code court affiché dans la barre : « FR », « EN ». */
    public static function code(?string $code = null): string
    {
        return mb_strtoupper($code ?? self::courante());
    }

    public static function nomDuCookie(): string
    {
        return self::COOKIE;
    }
}
