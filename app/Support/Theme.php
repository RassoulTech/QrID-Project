<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

/**
 * Thème courant, clair ou sombre — UN SEUL endroit décide.
 *
 * Deux mémoires, dans cet ordre :
 *
 * 1. le COMPTE, si quelqu'un est connecté. La préférence suit alors la
 *    personne : elle la retrouve sur son téléphone comme sur son poste ;
 * 2. un COOKIE, sinon. Un visiteur de la landing ou du formulaire de
 *    connexion n'a aucun compte où écrire quoi que ce soit — sans cookie,
 *    la bascule serait inutilisable pour lui, ou réservée aux clients.
 *
 * Le cookie est écrit dans les DEUX cas. C'est ce qui permet au tout premier
 * rendu après connexion d'être déjà au bon thème, avant même que la session
 * ne soit lue.
 */
class Theme
{
    public const CLAIR = 'light';

    public const SOMBRE = 'dark';

    /** Un an : une préférence d'affichage n'a pas à être redemandée. */
    public const DUREE_COOKIE = 60 * 24 * 365;

    private const COOKIE = 'theme';

    public static function courant(): string
    {
        $utilisateur = Auth::user();

        if ($utilisateur !== null) {
            return $utilisateur->prefersDark() ? self::SOMBRE : self::CLAIR;
        }

        return Cookie::get(self::COOKIE) === self::SOMBRE ? self::SOMBRE : self::CLAIR;
    }

    public static function estSombre(): bool
    {
        return self::courant() === self::SOMBRE;
    }

    /** Le thème vers lequel bascule le bouton — l'inverse du courant. */
    public static function inverse(): string
    {
        return self::estSombre() ? self::CLAIR : self::SOMBRE;
    }

    public static function nomDuCookie(): string
    {
        return self::COOKIE;
    }
}
