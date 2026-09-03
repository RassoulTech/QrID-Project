<?php

/*
|--------------------------------------------------------------------------
| L'ESPACE CLIENT — pilotage
|--------------------------------------------------------------------------
| Tableau de bord, statistiques, notifications, recherche. Les quatre écrans
| où le client REGARDE son activité, par opposition à ceux où il modifie sa
| carte (profile.php) ou paie (payment.php).
*/

return [

    // =================================================================
    // TABLEAU DE BORD
    // =================================================================
    'titre' => 'Tableau de bord',

    'vide' => [
        'bienvenue' => 'Bienvenue :nom',
        'texte' => 'Créez votre profil professionnel pour le partager par lien ou QR Code.',
        'creer' => 'Créer mon profil',
    ],

    'tete' => [
        'bonjour' => 'Bonjour :prenom',
        'sous' => 'Suivez votre carte et son audience.',
        'voir_carte' => 'Voir ma carte publique',
        'activer_carte' => 'Activer ma carte',
    ],

    'expire' => [
        'titre' => 'Votre abonnement a expiré',
        'texte' => 'Votre carte n\'est plus consultable par vos contacts&nbsp;: le lien public '
            .'ne répond plus. Rien n\'est perdu — un paiement la remet en ligne immédiatement.',
        'reactiver' => 'Réactiver ma carte',
    ],

    // -----------------------------------------------------------------
    // Vue d'ensemble — quatre compteurs
    // -----------------------------------------------------------------
    'apercu' => [
        'titre' => 'Vue d\'ensemble',

        'views_libelle' => 'Vues de la carte',
        'views_attente' => 'Partagez votre lien pour voir arriver vos premières vues.',

        'scans_libelle' => 'Scans du QR Code',
        'scans_attente' => 'Aucun scan pour l\'instant.',

        'saves_libelle' => 'Contacts enregistrés',
        'saves_attente' => 'Personne ne vous a encore enregistré.',

        'days_libelle' => 'Jours d\'abonnement',
        'days_attente' => 'Aucun abonnement en cours.',
    ],

    // -----------------------------------------------------------------
    // Bloc « Ma carte »
    // -----------------------------------------------------------------
    'carte' => [
        'titre' => 'Ma carte',
        'lien_public' => 'Lien public',
        'lien_aria' => 'Lien public de votre carte',
        'copier' => 'Copier',
        'copie' => 'Copié',
        'photo' => 'Photo',
        'banniere' => 'Bannière',
        'photo_ok' => 'Photo enregistrée',
        'photo_absente' => 'Aucune photo',
        'banniere_ok' => 'Bannière enregistrée',
        'banniere_absente' => 'Aucune bannière',
        'qr_png' => 'QR en PNG',
        'qr_svg' => 'QR en SVG',
        'imprimable' => 'Carte imprimable',
        'modifier' => 'Modifier ma carte',
    ],

    // -----------------------------------------------------------------
    // Carte physique
    // -----------------------------------------------------------------
    'physique' => [
        'titre' => 'Ma carte physique',
        'sans_adresse' => 'Votre carte PVC est <strong>offerte</strong> et vous attend. '
            .'Il nous manque seulement l\'adresse où la livrer.',
        'indiquer_adresse' => 'Indiquer mon adresse',
        'livraison' => 'Livraison à <strong>:ville</strong>, pour :destinataire.',
        'livree_le' => 'Livrée le :date.',
        'expediee_le' => 'Expédiée le :date.',
        'depart_prochain' => 'Départ à la prochaine production, sous environ :jours jours.',
        'corriger_adresse' => 'Corriger l\'adresse',
    ],

    // -----------------------------------------------------------------
    // Activité récente
    // -----------------------------------------------------------------
    'activite' => [
        'titre' => 'Activité récente',
        'periode_aria' => 'Période affichée',
        'periode_jours' => ':compte jours',
        'graphique_aria' => 'Vues de la carte sur les :compte derniers jours',
        'infobulle' => ':jour · :compte vue|:jour · :compte vues',
        'tableau_titre' => 'Vues par jour',
        'colonne_jour' => 'Jour',
        'colonne_vues' => 'Vues',
        'vide_titre' => 'Aucune vue pour l\'instant',
        'vide_texte' => 'Partagez votre QR Code ou votre lien&nbsp;: les consultations '
            .'apparaîtront ici, jour par jour.',
    ],

    // -----------------------------------------------------------------
    // Colonne de droite
    // -----------------------------------------------------------------
    'rail' => [
        'visiteurs' => 'Derniers visiteurs',
        'scan' => 'Scan du QR Code',
        'consultation' => 'Consultation directe',
        'enregistrement' => 'Contact enregistré',
        'partage' => 'Partage lancé',
        'aucun_visiteur' => 'Personne n\'a encore ouvert votre carte.',
        'journal' => 'Activité du compte',
        'aucune_activite' => 'Aucune activité enregistrée.',
        'groupe_titre' => 'Besoin d\'un coup de main&nbsp;?',
        'groupe_texte' => 'Un groupe WhatsApp réunit les clients :marque. Questions, entraide, '
            .'et réponses de notre équipe.',
        'groupe_rejoindre' => 'Rejoindre le groupe',
    ],

    // =================================================================
    // STATISTIQUES
    // =================================================================
    'stats' => [
        'titre' => 'Statistiques',
        'sous' => 'L\'audience de votre carte, jour par jour.',
        'periode' => 'Période affichée',
        'derniers_jours' => ':compte derniers jours',
        'sur_jours' => 'Sur :compte jours',
        'vues_directes' => 'Vues directes',
        'scans' => 'Scans du QR Code',
        'contacts' => 'Contacts enregistrés',
        'total' => 'Total des consultations',
        'partages' => 'Partages lancés',
        'partages_aide' => 'Ouvertures de WhatsApp ou du menu de partage. Un envoi effectif ne peut pas être mesuré.',
        'evolution' => 'Évolution',
        'evolution_aria' => 'Vues et scans sur les :compte derniers jours',
        'infobulle' => ':jour · :vues vue(s), :scans scan(s)',
        'legende_vues' => 'Vues directes',
        'legende_scans' => 'Scans',
        'tableau_titre' => 'Vues et scans par jour',
        'colonne_jour' => 'Jour',
        'colonne_vues' => 'Vues',
        'colonne_scans' => 'Scans',
        'vide_titre' => 'Aucun événement sur cette période',
        'vide_texte' => 'Partagez votre QR Code ou votre lien&nbsp;: chaque consultation '
            .'apparaîtra ici.',
        'voir_qr' => 'Voir mon QR Code',
        'derniers' => 'Derniers événements',
        'aucun_evenement' => 'Aucun événement enregistré pour l\'instant.',
    ],

    // =================================================================
    // NOTIFICATIONS
    // =================================================================
    'notifications' => [
        'titre' => 'Notifications',
        'sous' => 'Les faits marquants de votre compte.',
        'tout_marquer' => 'Tout marquer comme lu',
        'vide_titre' => 'Aucune notification',
        'vide_texte' => 'Vous serez prévenu dès qu\'un fait le mérite&nbsp;: première '
            .'consultation de votre carte, contact enregistré, paiement confirmé, '
            .'échéance d\'abonnement.',
    ],

    // =================================================================
    // RECHERCHE
    // =================================================================
    'recherche' => [
        'titre' => 'Recherche',
        'resultats_pour' => 'Résultats pour «&nbsp;:terme&nbsp;»',
        'invite' => 'Saisissez un terme dans la barre de recherche.',
        'trop_court' => 'Saisissez au moins deux caractères.',
        'aucun_titre' => 'Aucun résultat',
        'aucun_texte' => 'Rien ne correspond à «&nbsp;:terme&nbsp;» dans votre carte, '
            .'vos paiements ou vos notifications.',
        'ma_carte' => 'Ma carte',
        'paiements' => 'Paiements',
        'notifications' => 'Notifications',
    ],

    'flash' => [
        'notifications_lues' => 'Toutes vos notifications sont marquées comme lues.',
    ],

    /*
     | LES NOTIFICATIONS SONT ECRITES EN BASE, donc figees au moment de
     | l'ecriture. Elles sont rendues dans la langue du DESTINATAIRE — pas
     | dans celle de l'action : une consultation de carte vient d'un
     | inconnu, dont la langue ne regarde pas le porteur.
     |
     | Consequence assumee : une notification ecrite avant un changement de
     | langue reste dans l'ancienne.
     */
    'notifs' => [
        'paiement_confirme' => 'Paiement confirmé',
        'carte_consultee' => 'Votre carte a été consultée',
        'contact_enregistre' => 'Un contact vous a enregistré',
    ],
];
