<?php

/*
|--------------------------------------------------------------------------
| Messages d'authentification
|--------------------------------------------------------------------------
| ATTENTION : ces clés sont référencées par leur chemin (auth.failed), pas
| par leur texte. Le fichier lang/fr.json ne les résout PAS — lui ne traduit
| que les chaînes littérales passées à __('…'). Sans ce fichier-ci, et avec
| APP_FALLBACK_LOCALE=fr qui interdit tout repli anglais, l'écran affiche
| la clé brute « auth.failed ».
*/

return [
    /*
    | AFFIRME LE MOINS POSSIBLE, ET RIEN DE FAUX.
    |
    | La formulation précédente — « Ces identifiants ne correspondent à aucun
    | compte » — était un vrai piège. Ce message s'affiche AUSSI quand le compte
    | existe et que seul le mot de passe est erroné : il annonçait alors une
    | chose fausse, et poussait l'utilisateur à supprimer puis recréer un compte
    | parfaitement valide. C'est exactement ce qui s'est produit, plusieurs fois.
    |
    | L'original anglais de Laravel est volontairement vague (« do not match our
    | records ») : c'est ce flou qui empêche d'énumérer les comptes. La
    | traduction avait transformé ce flou en une affirmation précise et fausse.
    |
    | On dit donc ce qui est vrai dans les deux cas, sans révéler lequel.
    */
    'failed' => 'E-mail ou mot de passe incorrect.',
    'password' => 'Ce mot de passe est incorrect.',
    'throttle' => 'Trop de tentatives. Réessayez dans :seconds secondes.',

    // Propres à ce produit.
    'blocked' => 'Ce compte est suspendu. Contactez le support pour le réactiver.',
    'not_admin' => 'Cette page est réservée à l\'administration.',
];
