<?php

/*
|--------------------------------------------------------------------------
| PAGES D'ERREUR
|--------------------------------------------------------------------------
| Six écrans, et le seul endroit du produit où l'on s'adresse à quelqu'un qui
| est déjà contrarié. Chaque message dit TROIS choses : ce qui s'est passé, à
| qui la faute, et quoi faire ensuite.
|
| « Erreur 500 » seul ne dit aucune des trois.
*/

return [

    'aide' => [
        'question' => 'Besoin d\'aide&nbsp;?',
        'contact' => 'Contactez le support',
    ],

    'retour_accueil' => 'Retour à l\'accueil',
    'retour_espace' => 'Retour à mon espace',
    'aller_connexion' => 'Aller à la connexion',
    'se_connecter' => 'Se connecter',

    '403' => [
        'code' => 'Erreur 403',
        'titre' => 'Accès refusé',
        'message' => 'Vous n\'avez pas les droits nécessaires pour consulter cette page.',
    ],

    '404' => [
        'code' => 'Erreur 404',
        'titre' => 'Page introuvable',
        'message' => 'Cette page n\'existe pas ou a été déplacée. Vérifiez le lien, il contient peut-être une erreur.',
    ],

    '419' => [
        'code' => 'Erreur 419',
        'titre' => 'Page expirée',
        'message' => 'Cette page est restée ouverte trop longtemps. Par sécurité, il faut la recharger avant de continuer.',
        // Le même incident, vu depuis une requête JSON.
        'json' => 'Votre session a expiré. Rechargez la page.',
        // Et vu depuis un formulaire dont les saisies ont pu être conservées.
        'saisies_conservees' => 'Votre page est restée ouverte trop longtemps. Vos informations sont conservées : validez à nouveau pour continuer.',
    ],

    '429' => [
        'code' => 'Erreur 429',
        'titre' => 'Trop de tentatives',
        'message' => 'Vous avez effectué trop de tentatives en peu de temps. Patientez une minute avant de réessayer.',
    ],

    '500' => [
        'code' => 'Erreur 500',
        'titre' => 'Une erreur est survenue',
        // « Le problème vient de nous, pas de vous » : la seule phrase de cet
        // écran qui change quelque chose pour la personne qui le lit.
        'message' => 'Le problème vient de nous, pas de vous. Notre équipe en a été informée. Réessayez dans un instant.',
    ],

    '503' => [
        'code' => 'Maintenance',
        'titre' => 'Service temporairement indisponible',
        'message' => 'Nous effectuons une mise à jour. Le service revient dans quelques minutes.',
    ],

    // Envoi trop volumineux — le « 419 déguisé » : au-delà de post_max_size,
    // PHP vide $_POST en entier, jeton CSRF compris.
    'trop_volumineux' => [
        'json' => 'Fichier trop volumineux.',
        'photo' => 'Votre photo est trop lourde pour être envoyée. Choisissez une image de moins de 2 Mo.',
    ],
];
