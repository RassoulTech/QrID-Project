<?php

/*
|--------------------------------------------------------------------------
| NAVIGATION — bars, menus, sidebars, footer
|--------------------------------------------------------------------------
| Everything used to MOVE AROUND the product.
|
| This file is read on every single page. A missing key here does not break
| one screen: it breaks all of them.
*/

return [

    'aller_au_contenu' => 'Skip to content',
    'ouvrir_menu' => 'Open menu',
    'sections_du_site' => 'Site sections',
    'principale' => 'Main navigation',
    'administration' => 'Admin navigation',
    'preferences' => 'Preferences',

    'langue' => [
        'changer' => 'Change language',
        'courante' => 'Language: :langue',
    ],

    'public' => [
        'produits' => 'Products',
        'ressources' => 'Resources',
        'tarifs' => 'Pricing',
        'connexion' => 'Sign in',
        'creer_compte' => 'Create an account',
        'mon_espace' => 'My account',
        'comment_ca_marche' => 'How it works',
        'metiers' => 'Professions served',
        'contact' => 'Contact',
        'fonctionnement' => 'How it works',
        'carte_pvc' => 'PVC card',
        'exemple' => 'Example',
        'aide' => 'Help',
        'se_connecter' => 'Sign in',
    ],

    'coque' => [
        'espace_client' => 'Client area',
        'portail_admin' => 'Admin portal',
        'section_client' => 'My account',
        'section_admin' => 'Administration',
        'rechercher' => 'Search',
        'rechercher_client' => 'Search for a client',
        'mon_compte' => 'My account',
        'administration' => 'Administration',
        'se_deconnecter' => 'Sign out',
        'deconnexion' => 'Sign out',
        'retour_espace_client' => 'Back to my client area',
        'aide' => 'Help',

        // Shell A (lot 1) — top bar, menus that work without JavaScript.
        'menu_compte' => 'Account menu',
        'connecte' => 'Signed in',
        'role_client' => 'Client area',
        'role_admin' => 'Administrator',
        'preferences' => 'Display',
        'aide_whatsapp' => 'A question? Write to us on WhatsApp',
        'aide_court' => 'A question?',
        'lancer_recherche' => 'Search',
        'fermer_menu' => 'Close the menu',
        'fil_ariane' => 'Breadcrumb',
    ],

    'sections' => [
        'pilotage' => 'Overview',
        'ma_carte' => 'My card',
        'compte' => 'Account',
        'gestion' => 'Management',
        'configuration' => 'Configuration',
        'systeme' => 'System',
    ],

    'client' => [
        'tableau_de_bord' => 'Dashboard',
        'statistiques' => 'Statistics',
        'mon_profil' => 'My profile',
        'mon_qr' => 'My QR code',
        'mon_abonnement' => 'My subscription',
        /*
         | TOP TABS (desktop) — six destinations, full labels. The list and
         | its order live in App\Support\Navigation.
         */
        'onglet' => [
            'tableau_de_bord' => 'Dashboard',
            'statistiques' => 'Statistics',
            'mon_profil' => 'My profile',
            'mon_qr' => 'My QR code',
            'abonnement' => 'Subscription',
            'compte' => 'Account',
        ],


        /*
         | SHORT DOCK LABELS — see the French file for the measurement.
         | Twelve characters maximum: past that they truncate at 320px.
         */
        'court' => [
            'tableau_de_bord' => 'Home',
            'mon_profil' => 'Profile',
            'mon_qr' => 'Card',
            'statistiques' => 'Stats',
            'mon_abonnement' => 'Plan',
            'compte' => 'Account',
        ],
    ],

    'admin' => [
        'vue_ensemble' => 'Overview',
        'statistiques' => 'Statistics',
        'clients' => 'Clients',
        'profils' => 'Profiles',
        'paiements' => 'Payments',
        'abonnements' => 'Subscriptions',
        'cartes' => 'Cards',
        'modeles' => 'Templates',
        'parametres' => 'Settings',
        'journal' => 'Audit log',
        'etat_systeme' => 'System health',
        'retour' => 'Back',
        'en_construction' => 'Screen under construction',
        'tag' => 'Admin',
        'a_verifier' => ':compte payment to verify|:compte payments to verify',

        /* Short dock labels — eight characters maximum. */
        'court' => [
            'vue_ensemble' => 'Home',
            'clients' => 'Clients',
            'paiements' => 'Payments',
            'cartes' => 'Cards',
        ],
    ],

    'notifications' => [
        'titre' => 'Notifications',
        'aria' => 'Notifications',
        'aria_non_lues' => 'Notifications (:compte unread)',
        'tout_marquer' => 'Mark all as read',
        'vide' => 'Nothing yet.',
        'vide_aide' => 'You will be notified the first time someone opens your card.',
        'vide_aide_admin' => 'You will be notified about payments, cards to produce and incidents.',
        'voir_tout' => 'See all',
    ],

    'essai' => [
        'restants' => ':compte day of free trial left|:compte days of free trial left',
        'mettre_a_jour' => 'Upgrade my plan',
    ],

    'pied' => [
        'about' => 'Senegalese Professional Excellence.',
        'about_suite' => 'The reference for a secure digital presence.',
        'plateforme' => 'Platform',
        'legal' => 'Legal',
        'support' => 'Support',
        'cgu' => 'Terms',
        'confidentialite' => 'Privacy',
        'mentions' => 'Legal notice',
        'conditions' => 'Terms and conditions',

        // Footer A (lot 1).
        'description' => 'The digital business card for professionals in Senegal: a PVC card, a QR code, a page in your name.',
        'produit' => 'Product',
        'aide' => 'Help',
        'exemple_carte' => 'Card example',
        'nous_ecrire' => 'Write to us',
        'paiement' => 'Payment: Wave · Orange Money · Free Money · in FCFA',
    ],

    'dock' => [
        'aria' => 'Main navigation',
        'en_attente' => ':compte item pending|:compte items pending',
        'plus' => 'More',
        'fermer' => 'Close',
    ],
];
