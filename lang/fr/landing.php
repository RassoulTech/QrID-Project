<?php

/*
|--------------------------------------------------------------------------
| LA PAGE D'ACCUEIL PUBLIQUE
|--------------------------------------------------------------------------
| Huit sections, dans l'ordre où on les lit : hero, métiers, chiffres, étapes,
| démonstration, tarifs, contact, appel final.
|
| CE QUI N'EST PAS ICI, ET POURQUOI :
|
|   · les NOMS ET INCLUSIONS DES FORMULES viennent de la table `plans`. Ce
|     sont des données, modifiables depuis l'administration : les traduire
|     reviendrait à figer dans le code ce qui doit rester modifiable sans
|     déploiement ;
|   · la PÉRIODE de facturation, elle, est calculée par le modèle et non
|     saisie. Elle vit dans subscription.php.
*/

return [

    'meta' => [
        'titre' => ':marque | Votre Identité Professionnelle Numérique',
        'description' => 'La plateforme d\'identité numérique sécurisée pour l\'élite professionnelle '
            .'du Sénégal. Centralisez votre expertise et rayonnez avec élégance.',
    ],

    // -----------------------------------------------------------------
    // Hero
    // -----------------------------------------------------------------
    'hero' => [
        // Le balisage est DANS la traduction : l'anglais ne place pas
        // l'adjectif au même endroit de la phrase.
        'titre' => 'Votre identité professionnelle <span class="hero-mark">réinventée</span>',
        'accroche' => 'La plateforme d\'identité numérique sécurisée pour l\'élite professionnelle '
            .'du Sénégal. Centralisez votre expertise et rayonnez avec élégance.',
        'cta' => 'Démarrer l\'essai gratuit',
        'exemple' => 'Voir un exemple',
        'qr_genere' => 'QR Code généré',
        'vues_totales' => 'Vues totales',
        'contact_enregistre' => 'Contact enregistré',
    ],

    // -----------------------------------------------------------------
    // Bandeau des métiers
    // -----------------------------------------------------------------
    'metiers' => [
        'aria' => 'Métiers concernés',
        'architecte' => 'Architecte',
        'consultant' => 'Consultant',
        'avocat' => 'Avocat',
        'medecin' => 'Médecin',
        'ingenieur' => 'Ingénieur',
        'freelance' => 'Freelance',
    ],

    // -----------------------------------------------------------------
    // Trois chiffres clés — la PROMESSE, pas une statistique mesurée
    // -----------------------------------------------------------------
    'chiffres' => [
        'minutes_mot' => 'Minutes',
        'minutes_libelle' => 'Pour créer',
        'lien_mot' => 'Lien',
        'lien_libelle' => 'Pour tout partager',
        'jours_mot' => 'Jours',
        'jours_libelle' => 'D\'essai gratuit',
    ],

    // -----------------------------------------------------------------
    // Comment ça marche
    // -----------------------------------------------------------------
    'etapes' => [
        'titre' => 'Comment ça marche',
        'profil_titre' => 'Créez votre profil',
        'profil_texte' => 'Remplissez vos informations professionnelles en quelques clics '
            .'grâce à notre interface intuitive.',
        'liens_titre' => 'Personnalisez vos liens',
        'liens_texte' => 'Ajoutez vos réseaux sociaux, votre portfolio et vos moyens de contact favoris.',
        'partage_titre' => 'Partagez sans limite',
        'partage_texte' => 'Un seul lien unique ou un QR code élégant pour toutes vos '
            .'interactions professionnelles.',
    ],

    // -----------------------------------------------------------------
    // Section sombre — démonstration
    // -----------------------------------------------------------------
    'demo' => [
        'titre' => 'Voyez votre profil terminé avant de payer.',
        'texte' => 'Notre technologie vous permet de visualiser instantanément le rendu de votre '
            .'identité numérique. Pas de frais cachés, pas d\'engagement immédiat. Testez la '
            .'puissance de :marque gratuitement.',
        'scanner' => 'Scanner &amp; Voir',
        'professionnels' => 'Plus de :compte professionnels',
        'professionnels_debut' => 'Des professionnels qui ont déjà leur carte',
    ],

    // -----------------------------------------------------------------
    // Tarifs
    // -----------------------------------------------------------------
    'tarifs' => [
        'titre' => 'Tarifs simples &amp; transparents',
        'sous_titre' => 'Payable via Wave, Orange Money et Free Money.',
        'best_value' => 'Best value',
        'essayer' => 'Essayer gratuitement',
        'abonner' => 'S\'abonner',
    ],

    // -----------------------------------------------------------------
    // Appel à l'action final
    // -----------------------------------------------------------------
    'final' => [
        'titre' => 'Prêt à rayonner au Sénégal&nbsp;?',
        'texte' => 'Rejoignez la communauté grandissante des leaders qui font confiance à '
            .':marque pour porter leur message.',
        'cta' => 'Démarrer mon aventure',
    ],

    // -----------------------------------------------------------------
    // Contact
    // -----------------------------------------------------------------
    'contact' => [
        'titre' => 'Une question&nbsp;? Écrivez-nous.',
        'sous_titre' => 'Une demande sur le service, une commande de cartes imprimées, ou '
            .'simplement un doute&nbsp;: nous répondons sous 24&nbsp;heures ouvrées.',
        'whatsapp_titre' => 'Répondre plus vite&nbsp;: WhatsApp',
        'whatsapp_texte' => 'Le canal le plus rapide, du lundi au samedi.',
        'recu_titre' => 'Message reçu.',
        'recu_texte' => 'Nous vous répondons à l\'adresse indiquée, sous 24&nbsp;heures ouvrées.',
        'votre_nom' => 'Votre nom',
        'nom_exemple' => 'Awa Ndiaye',
        'votre_message' => 'Votre message',
        'message_exemple' => 'Dites-nous en quelques lignes ce dont vous avez besoin.',
        'envoyer' => 'Envoyer mon message',
        'legal' => 'Vos coordonnées servent uniquement à vous répondre. Elles ne sont ni '
            .'revendues, ni utilisées pour de la prospection.',

        // Les quatre motifs. Liste fermée : voir ContactRequest::SUJETS.
        'motifs' => [
            'information' => 'Une question sur le service',
            'commande' => 'Commander des cartes imprimées',
            'assistance' => 'J\'ai besoin d\'aide sur mon compte',
            'partenariat' => 'Partenariat ou revente',
        ],

        // Messages de validation propres à ce formulaire.
        'validation' => [
            'nom_requis' => 'Indiquez votre nom, pour qu\'on sache à qui répondre.',
            'nom_court' => 'Ce nom paraît trop court.',
            'email_requis' => 'Sans adresse e-mail, nous ne pourrons pas vous répondre.',
            'email_invalide' => 'Cette adresse e-mail ne semble pas valide.',
            'motif_requis' => 'Choisissez un motif.',
            'motif_inconnu' => 'Ce motif n\'est pas proposé.',
            'message_requis' => 'Écrivez votre message.',
            'message_court' => 'Dites-nous-en un peu plus : au moins vingt caractères.',
            'message_long' => 'Votre message est trop long. Résumez l\'essentiel, nous vous rappellerons.',
            // Le piège à robots. Le message reste vague À DESSEIN : expliquer
            // qu'un champ masqué a été rempli apprendrait au robot à l'éviter.
            'piege' => 'Votre message n\'a pas pu être envoyé.',
        ],
    ],

    // -----------------------------------------------------------------
    // Bouton d'aide flottant
    // -----------------------------------------------------------------
    'aide' => [
        'question' => 'Une question&nbsp;?',
        'ecrire' => 'Nous écrire sur WhatsApp',
    ],
];
