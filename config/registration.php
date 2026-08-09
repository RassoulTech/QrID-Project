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
    | Compte administrateur (AdminSeeder)
    |--------------------------------------------------------------------------
    | Sans ADMIN_EMAIL ni ADMIN_PASSWORD, aucun compte privilégié n'est créé.
    */

    'admin' => [
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
        'name' => env('ADMIN_NAME', 'Administrateur'),
        'phone' => env('ADMIN_PHONE'),
    ],

];
