<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bandeau des métiers
    |--------------------------------------------------------------------------
    */

    'trades' => [
        'Architecte',
        'Consultant',
        'Avocat',
        'Médecin',
        'Ingénieur',
        'Freelance',
    ],

    /*
    |--------------------------------------------------------------------------
    | Trois chiffres clés — VALEURS DE CONFIGURATION, PAS DES STATISTIQUES.
    | Ils décrivent la promesse produit : rien n'est mesuré ni inventé.
    |--------------------------------------------------------------------------
    */

    'figures' => [
        ['number' => '3',  'word' => 'Minutes', 'label' => 'Pour créer'],
        ['number' => '1',  'word' => 'Lien',    'label' => 'Pour tout partager'],
        ['number' => '15', 'word' => 'Jours',   'label' => "D'essai gratuit"],
    ],

    /*
    |--------------------------------------------------------------------------
    | Comment ça marche
    |--------------------------------------------------------------------------
    */

    'steps' => [
        [
            'title' => 'Créez votre profil',
            'text' => 'Remplissez vos informations professionnelles en quelques clics grâce à notre interface intuitive.',
        ],
        [
            'title' => 'Personnalisez vos liens',
            'text' => 'Ajoutez vos réseaux sociaux, votre portfolio et vos moyens de contact favoris.',
        ],
        [
            'title' => 'Partagez sans limite',
            'text' => 'Un seul lien unique ou un QR code élégant pour toutes vos interactions professionnelles.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Maquette du téléphone — ILLUSTRATION, PAS UN PROFIL RÉEL.
    |--------------------------------------------------------------------------
    | La landing montre un profil de démonstration pris en base (DemoSeeder).
    | Quand la base n'en contient aucun — production le jour de la mise en
    | ligne, base fraîchement migrée, profils de démo dépubliés — c'est ce
    | contenu qui alimente la maquette.
    |
    | Sans ce repli, l'accueil renvoyait une erreur 500 : la page la plus
    | visitée du site tombait dès que le jeu de démonstration manquait.
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | Marque imprimée au verso des cartes
    |--------------------------------------------------------------------------
    | Le verso est identique sur TOUTES les cartes : c'est la face de la
    | plateforme. Ces valeurs partent chez l'imprimeur en centaines
    | d'exemplaires — elles ne se corrigent pas par un déploiement.
    |
    | BRAND_WEBSITE est distinct d'APP_URL : l'application peut tourner sur
    | app.exemple.sn alors que la carte doit afficher le site vitrine. Sans
    | cette variable, la carte imprimerait l'adresse de développement.
    */

    'brand' => [
        'tagline' => env('BRAND_TAGLINE', 'Votre identité professionnelle, en un scan'),

        /*
         | Repli VOLONTAIREMENT indépendant d'APP_URL.
         |
         | La version précédente dérivait l'adresse de APP_URL : en
         | développement, la carte imprimait « 127.0.0.1 ». Un défaut de ce
         | genre ne se voit qu'une fois les cartes sorties de l'imprimerie,
         | et ne se corrige pas par un déploiement.
         */
        'website' => env('BRAND_WEBSITE', 'qrid.sn'),

        /*
         | Contenu du code-barres du verso : 'url' ou 'slug'.
         |
         | 'url'  — conforme à la demande, mais 453 modules. Sur ≈28 mm chaque
         |          module ferait 0,062 mm, contre 0,19 mm de seuil de lecture
         |          fiable : le code est alors décoratif, pas fonctionnel.
         | 'slug' — 189 modules, soit 0,15 mm. Nettement plus lisible, mais le
         |          scanner rend « mouhamed-dione » et non une adresse.
         */
        'barcode_content' => env('BARCODE_CONTENT', 'url'),
    ],

    'mockup' => [
        'first_name' => 'Awa',
        'last_name' => 'Ndiaye',
        'job_title' => 'Architecte',
        'company' => 'Atelier Teranga',
        'phone' => '+221770000000',
        'public_email' => 'contact@exemple.sn',
        'address' => 'Dakar, Sénégal',
        'primary_color' => '#0B3B2E',
    ],

    /*
    |--------------------------------------------------------------------------
    | Preuve sociale — désactivée tant qu'il n'y a pas de vrais clients.
    | Pour activer : renseigner les valeurs et passer 'enabled' à true.
    |--------------------------------------------------------------------------
    */

    'testimonial' => [
        'enabled' => false,
        'quote' => '',
        'name' => '',
        'role' => '',
    ],

];
