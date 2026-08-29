<?php

/*
|--------------------------------------------------------------------------
| LA CARTE ET LE COMPTE — tout ce que le client SAISIT
|--------------------------------------------------------------------------
| Les trois étapes de création, la fiche du profil, l'aperçu avant activation,
| et les réglages du compte.
|
| CE QUI EST SAISI N'EST JAMAIS TRADUIT : nom, fonction, entreprise, adresse,
| liens. Seuls les libellés qui les entourent le sont.
*/

return [

    // =================================================================
    // LE PARCOURS EN TROIS ÉTAPES
    // =================================================================
    'wizard' => [
        'etape_sur' => 'Étape :n sur :total',
        'progression' => 'Progression : étape :n sur :total',
        'retour' => 'Retour',
        'continuer' => 'Continuer',
        'terminer' => 'Terminer',

        // ── Étape 1 ───────────────────────────────────────────────────
        'titre_1' => 'Créer mon profil — étape 1',
        'entete_1' => 'Qui êtes-vous ?',
        'sous_1' => 'Ces informations apparaissent en haut de votre profil.',
        'retour_tableau' => 'Tableau de bord',
        'fonction_exemple' => 'Commercial, avocate, gérant…',

        'couverture' => 'Image de couverture',
        'couverture_actuelle' => 'Bannière actuelle',
        'couverture_ajouter' => 'Ajouter une image',
        'couverture_changer' => 'Changer l\'image',
        'couverture_formats' => 'JPG, PNG ou WEBP — 2 Mo maximum',
        'couverture_aide' => 'Format paysage, idéalement 1200 × 800 pixels. Elle est recadrée '
            .'pour remplir le haut de votre carte, et votre nom s\'affiche dessus. Sans image, '
            .'votre carte porte le décor de :marque.',

        // ── Étape 2 ───────────────────────────────────────────────────
        'titre_2' => 'Créer mon profil — étape 2',
        'entete_2' => 'Comment vous joindre ?',
        'sous_2' => 'Seul le téléphone est nécessaire. Vous compléterez le reste plus tard.',

        'localisation' => 'Lien de localisation',
        'localisation_aide' => 'Collez le lien de votre fiche Google Maps pour un repérage exact.',
        'email_public' => 'E-mail public',
        'email_public_aide' => 'Affiché sur votre profil. Différent de votre e-mail de connexion.',
        'email_public_exemple' => 'contact@exemple.sn',
        'adresse_exemple' => 'Sacré-Cœur 3, Dakar',

        'reseaux' => 'Réseaux sociaux',
        'reseau_choisir' => 'Choisir…',
        'reseau_aria' => 'Réseau social :n',
        'reseau_aria_nu' => 'Réseau social',
        'reseau_lien' => 'Lien vers votre page',
        'reseau_lien_aria' => 'Lien du réseau :n',
        'reseau_lien_aria_nu' => 'Lien du réseau',
        'reseau_retirer' => 'Retirer ce réseau',
        'reseau_ajouter' => 'Ajouter un réseau',

        // ── Étape 3 ───────────────────────────────────────────────────
        'titre_3' => 'Créer mon profil — étape 3',
        'entete_3' => 'Votre style',
        'sous_3' => 'Tout est déjà choisi. Modifiez si vous le souhaitez, ou terminez.',

        'modele' => 'Modèle',
        'modele_aria' => 'Modèle de profil',
        'carte' => 'Votre carte',
        'carte_aria' => 'Variante de carte',

        // Les valeurs de repli de l'aperçu, quand rien n'est encore saisi.
        'apercu_prenom' => 'Votre',
        'apercu_nom' => 'nom',
        'apercu_fonction' => 'Votre fonction',
    ],

    // =================================================================
    // MON PROFIL — la fiche en consultation
    // =================================================================
    'fiche' => [
        'titre' => 'Mon profil',
        'sous' => 'Les informations que verront vos contacts.',
        'modifier' => 'Modifier mes informations',

        'identite' => 'Identité',
        'nom_complet' => 'Nom complet',

        'coordonnees' => 'Coordonnées',
        'whatsapp' => 'WhatsApp',
        'email_public' => 'E-mail public',

        'reseaux' => 'Réseaux sociaux',
        'aucun_reseau' => 'Aucun réseau ajouté. Vous pouvez en ajouter jusqu\'à six '
            .'depuis la modification.',

        'apparence' => 'Apparence et lien',
        'modele' => 'Modèle',
        'carte' => 'Carte',

        // L'avertissement le plus important de l'écran : ce lien part à
        // l'imprimeur, et le changer casse tout ce qui a déjà circulé.
        'lien_modifiable' => 'Votre lien peut être modifié <strong>une seule fois</strong>, '
            .'depuis la modification. Après changement, les cartes déjà imprimées et les '
            .'QR Codes en circulation cesseront de fonctionner&nbsp;: ils pointeront vers '
            .'une page introuvable.',
        'lien_definitif' => 'Votre lien a déjà été modifié une fois&nbsp;: il est définitif. '
            .'C\'est ce qui garantit que les cartes déjà imprimées continueront de fonctionner.',

        'apercu' => 'Aperçu',
    ],

    // =================================================================
    // L'APERÇU AVANT ACTIVATION
    // =================================================================
    'apercu' => [
        'titre' => 'Votre carte est prête',
        'sous' => 'Regardez-la avant de l\'activer&nbsp;: rien n\'est publié, rien n\'est '
            .'débité tant que vous n\'avez pas décidé.',
        'physique' => 'Votre carte physique',
        'physique_note' => 'Format carte bancaire, prête à imprimer.',
        'contacts' => 'Ce que verront vos contacts',
        'contacts_note' => 'La page qui s\'ouvre après un scan.',
        'activer' => 'Activer ma carte',
        'modifier' => 'Modifier mes informations',
    ],

    // =================================================================
    // MON COMPTE
    // =================================================================
    'compte' => [
        'titre' => 'Mon compte',

        'informations' => 'Informations du compte',
        'informations_sous' => 'Vos identifiants de connexion.',
        'enregistre' => 'Enregistré.',

        'mot_de_passe' => 'Mot de passe',
        'mot_de_passe_sous' => 'Utilisez un mot de passe long et unique.',
        'actuel' => 'Mot de passe actuel',
        'nouveau' => 'Nouveau mot de passe',

        /*
         | UN COMPTE GOOGLE N'A PAS DE MOT DE PASSE.
         |
         | Lui réclamer son « mot de passe actuel » est une impasse : le champ
         | ne peut jamais être rempli correctement. On dit donc ce que
         | l'opération apporte — un SECOND moyen d'accès, pas un remplacement.
         */
        'sans_mot_de_passe' => 'Vous vous connectez avec Google, sans mot de passe. '
            .'En définir un ici vous donnera un <strong>second moyen d\'accès</strong> : '
            .'la connexion par Google continuera de fonctionner.',

        'supprimer' => 'Supprimer le compte',
        'supprimer_sous' => 'Cette action supprime aussi votre profil professionnel.',
        'supprimer_avertissement' => 'Une fois le compte supprimé, toutes ses données sont '
            .'définitivement perdues. Avant de continuer, téléchargez ce que vous souhaitez '
            .'conserver.',
        'supprimer_bouton' => 'Supprimer mon compte',
        'supprimer_confirmer' => 'Confirmer la suppression',
        'supprimer_modale' => 'Cette action est irréversible. Saisissez votre mot de passe '
            .'pour confirmer la suppression définitive de votre compte.',
        'supprimer_definitivement' => 'Supprimer définitivement',
    ],

    'qr' => [
        'scannent' => 'Vos contacts scannent, votre profil s\'ouvre.',
        'en_preparation' => 'Génération en cours de mise en place. Votre lien public fonctionne déjà.',
        'telecharger_png' => 'Télécharger en PNG',
        'version_svg' => 'Version SVG',
    ],

    'flash' => [
        'carte_avant_formule' => 'Créez d\'abord votre carte, vous choisirez votre formule ensuite.',
        'carte_avant_qr' => 'Créez d\'abord votre carte : son QR Code sera généré automatiquement.',
        'carte_avant_stats' => 'Créez d\'abord votre carte : ses statistiques suivront.',
        'carte_physique_apres_paiement' => 'Votre carte physique vous sera proposée dès votre première activation payée.',
    ],
];
