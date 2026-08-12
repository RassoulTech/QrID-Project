<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Inscription en deux temps (double opt-in)
    |--------------------------------------------------------------------------
    | Aucun compte n'est créé tant que le lien de confirmation n'a pas été
    | ouvert. Ces paramètres pilotent la durée de vie et les renvois.
    */

    /*
    | Validité du lien de confirmation, en minutes.
    |
    | Plancher à 5 minutes, et c'est délibéré. (int) env(...) rend 0 dès que la
    | variable est absente, vide ou non numérique — un cas qui arrive vraiment
    | quand le cache de configuration est reconstruit sans le .env sous la main.
    | Avec 0, expires_at valait « maintenant » : tout lien de confirmation
    | naissait expiré et PLUS AUCUNE inscription ne pouvait aboutir, sans le
    | moindre message d'erreur nulle part.
    */
    'verification_ttl' => max(5, (int) env('REGISTRATION_VERIFICATION_TTL', 60)),

    // Renvois d'e-mail autorisés par demande, et délai minimum entre deux renvois.
    'max_resends' => (int) env('REGISTRATION_MAX_RESENDS', 3),
    'resend_cooldown_seconds' => (int) env('REGISTRATION_RESEND_COOLDOWN', 60),

    // Rate limiting anti-abus (par heure).
    'rate_per_ip' => (int) env('REGISTRATION_RATE_PER_IP', 5),
    'rate_per_email' => (int) env('REGISTRATION_RATE_PER_EMAIL', 3),

    // Lien support affiché sur la page de confirmation.
    'support_whatsapp' => env('SUPPORT_WHATSAPP_URL', 'https://wa.me/221770000000'),

    // Adresse alertée si la file mail se bloque ou qu'un envoi échoue.
    'admin_email' => env('ADMIN_ALERT_EMAIL'),

    /*
    |--------------------------------------------------------------------------
    | Comptes administrateurs (AdminSeeder)
    |--------------------------------------------------------------------------
    | Sans ADMIN_EMAIL ni ADMIN_PASSWORD, aucun compte privilégié n'est créé.
    */

    'admin' => [
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
        'name' => env('ADMIN_NAME', 'Administrateur'),
        'phone' => env('ADMIN_PHONE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | L'ÉQUIPE — comptes administrateurs supplémentaires
    |--------------------------------------------------------------------------
    | Format attendu, séparé par des virgules :
    |
    |   ADMIN_TEAM="adresse@exemple.sn|Prénom Nom, autre@exemple.sn|Prénom Nom"
    |
    | Le mot de passe initial est commun à tous : ADMIN_TEAM_PASSWORD. Chacun
    | le change depuis « Mon compte » dès sa première connexion.
    |
    | CE MOT DE PASSE N'EST POSÉ QU'À LA CRÉATION DU COMPTE. Le seeder tourne
    | à chaque démarrage du conteneur ; s'il le réécrivait, tout changement
    | serait annulé au déploiement suivant — silencieusement.
    */
    'team' => [
        'members' => env('ADMIN_TEAM'),
        'password' => env('ADMIN_TEAM_PASSWORD'),

        /*
         | Un compte d'administration peut bloquer un client, désactiver un
         | profil et modifier les tarifs. Un mot de passe initial court, même
         | destiné à être changé, reste en base tant qu'il ne l'est pas — et
         | il est identique pour toute l'équipe. Douze caractères est un
         | plancher, pas un objectif.
         */
        'longueur_minimale' => 12,
    ],

];
