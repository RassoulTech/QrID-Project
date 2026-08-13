<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | CONNEXION GOOGLE
    |--------------------------------------------------------------------------
    |
    | L'ADRESSE DE RETOUR est dérivée de APP_URL et n'est jamais écrite à la
    | main. Elle doit correspondre AU CARACTÈRE PRÈS à celle déclarée dans la
    | console Google, faute de quoi le retour échoue avec « redirect_uri_
    | mismatch » — l'erreur la plus fréquente de cette intégration, et la plus
    | opaque. La dériver supprime toute possibilité de divergence entre
    | l'environnement local et la production.
    |
    | LA CONNEXION EST ABSENTE DES ÉCRANS TANT QUE LES CLÉS MANQUENT. Un bouton
    | qui mène à une page d'erreur Google est pire que pas de bouton du tout :
    | l'utilisateur conclut que le service est cassé, pas qu'il est en cours de
    | configuration. Voir GoogleController::estDisponible().
    |
    */

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => rtrim((string) env('APP_URL', 'http://localhost'), '/').'/auth/google/retour',
    ],

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
