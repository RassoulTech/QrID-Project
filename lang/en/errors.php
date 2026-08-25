<?php

/*
|--------------------------------------------------------------------------
| ERROR PAGES
|--------------------------------------------------------------------------
| Six screens, and the only place in the product where we address someone who
| is already annoyed. Every message says THREE things: what happened, whose
| fault it is, and what to do next.
*/

return [

    'aide' => [
        'question' => 'Need help&nbsp;?',
        'contact' => 'Contact support',
    ],

    'retour_accueil' => 'Back to home',
    'retour_espace' => 'Back to my account',
    'aller_connexion' => 'Go to sign in',
    'se_connecter' => 'Sign in',

    '403' => [
        'code' => 'Error 403',
        'titre' => 'Access denied',
        'message' => 'You do not have the rights needed to view this page.',
    ],

    '404' => [
        'code' => 'Error 404',
        'titre' => 'Page not found',
        'message' => 'This page does not exist, or has moved. Check the link — it may contain a mistake.',
    ],

    '419' => [
        'code' => 'Error 419',
        'titre' => 'Page expired',
        'message' => 'This page stayed open too long. For your safety it has to be reloaded before you continue.',
        'json' => 'Your session has expired. Please reload the page.',
        'saisies_conservees' => 'This page stayed open too long. What you typed has been kept: submit again to continue.',
    ],

    '429' => [
        'code' => 'Error 429',
        'titre' => 'Too many attempts',
        'message' => 'You have tried too many times in a short while. Wait a minute before trying again.',
    ],

    '500' => [
        'code' => 'Error 500',
        'titre' => 'Something went wrong',
        'message' => 'The problem is on our side, not yours. Our team has been told. Try again in a moment.',
    ],

    '503' => [
        'code' => 'Maintenance',
        'titre' => 'Service temporarily unavailable',
        'message' => 'We are shipping an update. The service will be back in a few minutes.',
    ],

    'trop_volumineux' => [
        'json' => 'File too large.',
        'photo' => 'Your photo is too heavy to upload. Please choose an image under 2 MB.',
    ],
];
