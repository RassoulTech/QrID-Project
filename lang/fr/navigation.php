<?php

/*
|--------------------------------------------------------------------------
| NAVIGATION — barres, menus, colonnes latérales, pied de page
|--------------------------------------------------------------------------
| Tout ce qui sert à SE DÉPLACER dans le produit. Les intitulés de menu, les
| repères d'espace, la cloche, la recherche.
|
| CE FICHIER EST LU SUR CHAQUE PAGE, sans exception. Une clé manquante ici ne
| casse pas un écran : elle les casse tous.
*/

return [

    'aller_au_contenu' => 'Aller au contenu',
    'ouvrir_menu' => 'Ouvrir le menu',
    'sections_du_site' => 'Sections du site',
    'principale' => 'Navigation principale',
    'administration' => "Navigation de l'administration",
    'preferences' => 'Préférences',

    // -----------------------------------------------------------------
    // Le sélecteur de langue lui-même
    // -----------------------------------------------------------------
    'langue' => [
        'changer' => 'Changer de langue',
        'courante' => 'Langue : :langue',
    ],

    // -----------------------------------------------------------------
    // Barre publique
    // -----------------------------------------------------------------
    'public' => [
        'produits' => 'Produits',
        'ressources' => 'Ressources',
        'tarifs' => 'Tarifs',
        'connexion' => 'Connexion',
        'creer_compte' => 'Créer un compte',
        'mon_espace' => 'Mon espace',
        'comment_ca_marche' => 'Comment ça marche',
        'metiers' => 'Métiers concernés',
        'contact' => 'Contact',
    ],

    // -----------------------------------------------------------------
    // Coque connectée — client et administration
    // -----------------------------------------------------------------
    'coque' => [
        'espace_client' => 'Espace client',
        'portail_admin' => 'Portail Admin',
        'section_client' => 'Mon espace',
        'section_admin' => 'Administration',
        'rechercher' => 'Rechercher',
        'rechercher_client' => 'Rechercher un client',
        'mon_compte' => 'Mon compte',
        'administration' => 'Administration',
        'se_deconnecter' => 'Se déconnecter',
        'deconnexion' => 'Déconnexion',
        'retour_espace_client' => 'Retour à mon espace client',
        'aide' => 'Aide',
    ],

    // -----------------------------------------------------------------
    // Sections des colonnes latérales
    // -----------------------------------------------------------------
    'sections' => [
        'pilotage' => 'Pilotage',
        'ma_carte' => 'Ma carte',
        'compte' => 'Compte',
        'gestion' => 'Gestion',
        'configuration' => 'Configuration',
        'systeme' => 'Système',
    ],

    'client' => [
        'tableau_de_bord' => 'Tableau de bord',
        'statistiques' => 'Statistiques',
        'mon_profil' => 'Mon profil',
        'mon_qr' => 'Mon QR Code',
        'mon_abonnement' => 'Mon abonnement',

        /*
         | LIBELLÉS COURTS DU DOCK — un jeu séparé, et c'est nécessaire.
         |
         | Le dock reprenait les libellés ci-dessus. À 320px, cinq entrées
         | laissent 57px chacune : « Tableau de bord » s'affichait
         | « Tableau d… », « Mon QR Code » « Mon QR C… », « Mon abonnement »
         | « Mon abo… ». Trois entrées sur cinq tronquées.
         |
         | Un libellé tronqué est pire qu'un libellé court : il donne
         | l'impression d'un défaut, et ne dit pas davantage.
         |
         | RÈGLE : HUIT caractères au maximum, en français comme en anglais.
         | Mesuré, pas estimé : « Abonnement » (10 caractères) rend 60px dans
         | une part de 57 — il se tronquait encore. « Ma carte » (8) rend 44px
         | et tient. Au-delà de huit, ça ne passe pas à 320px.
         */
        'court' => [
            'tableau_de_bord' => 'Accueil',
            'mon_profil' => 'Profil',
            'mon_qr' => 'Carte',
            'statistiques' => 'Stats',
            'mon_abonnement' => 'Offre',
        ],
    ],

    'admin' => [
        'vue_ensemble' => "Vue d'ensemble",
        'statistiques' => 'Statistiques',
        'clients' => 'Clients',
        'profils' => 'Profils',
        'paiements' => 'Paiements',
        'abonnements' => 'Abonnements',
        'cartes' => 'Cartes',
        'modeles' => 'Modèles',
        'parametres' => 'Paramètres',
        'journal' => 'Journal',
        'etat_systeme' => 'État système',
        'retour' => 'Retour',
        'en_construction' => 'Écran en cours de construction',
    ],

    // -----------------------------------------------------------------
    // Cloche de notifications
    // -----------------------------------------------------------------
    'notifications' => [
        'titre' => 'Notifications',
        'aria' => 'Notifications',
        'aria_non_lues' => 'Notifications (:compte non lues)',
        'tout_marquer' => 'Tout marquer comme lu',
        'vide' => "Rien pour l'instant.",
        'vide_aide' => 'Vous serez prévenu dès la première consultation de votre carte.',

        /*
         | LE MESSAGE VIDE DÉPEND DE L'ESPACE.
         |
         | La cloche est partagée par les deux coques. En administration, elle
         | annonçait « dès la première consultation de VOTRE carte » — à un
         | exploitant qui n'en a pas. Le message parlait du produit au lieu de
         | parler à celui qui le lit.
         */
        'vide_aide_admin' => "Vous serez prévenu des paiements, des cartes à produire et des incidents.",
        'voir_tout' => 'Voir tout',
    ],

    // -----------------------------------------------------------------
    // Bandeau d'essai gratuit
    // -----------------------------------------------------------------
    'essai' => [
        'restants' => "Il vous reste :compte jour d'essai gratuit|Il vous reste :compte jours d'essai gratuit",
        'mettre_a_jour' => 'Mettre à jour mon plan',
    ],

    // -----------------------------------------------------------------
    // Pied de page public
    // -----------------------------------------------------------------
    'pied' => [
        'about' => 'Excellence Professionnelle Sénégalaise.',
        'about_suite' => 'La référence pour votre présence digitale sécurisée.',
        'plateforme' => 'Plateforme',
        'legal' => 'Légal',
        'support' => 'Support',
        'cgu' => 'CGU',
        'confidentialite' => 'Confidentialité',
        'mentions' => 'Mentions légales',
        'conditions' => 'Conditions générales',
    ],

    'dock' => [
        'aria' => 'Navigation principale',
        'en_attente' => ':compte élément en attente|:compte éléments en attente',
        'plus' => 'Plus',
    ],
];
