<?php

/*
|--------------------------------------------------------------------------
| ESPACE D'ADMINISTRATION
|--------------------------------------------------------------------------
|
| L'espace admin suit la langue de l'ADMINISTRATEUR CONNECTÉ, pas celle du
| client dont il consulte la fiche. C'est la seule règle qui tienne : on
| lit ces écrans toute la journée, et une fiche client ne va pas changer de
| langue selon qui elle décrit.
|
| Le contenu SAISI par les clients — nom, fonction, entreprise, motif de
| blocage écrit par un collègue — n'est jamais traduit. Seuls les libellés
| de l'interface le sont.
|
*/

return [

    'commun' => [
        'exporter_csv' => 'Exporter CSV',
        'reinitialiser' => 'Réinitialiser',
        'reinitialiser_filtres' => 'Réinitialiser les filtres',
        'apres_filtrage' => 'après filtrage',
        'periode' => 'Période',
        'etat' => 'État',
        'statut' => 'Statut',
        'date_heure' => 'Date et heure',
        'telephone' => 'Téléphone',
        'modele' => 'Modèle',
        'identifiant_public' => 'Identifiant public',
        'nom_complet' => 'Nom complet',
        'cree_le' => 'Créé le',
        'debut' => 'Début',
        'echeance' => 'Échéance',
        'voir_tout' => 'Voir tout',
        'publie' => 'Publié',
        'desactive' => 'Désactivé',
        'compte_supprime' => 'Compte supprimé',
        'fil_ariane' => 'Fil d\'ariane',
        'motif_journal' => 'Ce motif sera lu dans le journal d\'audit dans six mois. Écrivez une phrase qui se comprendra seule.',
        'motif' => 'Motif',
        'affichage' => 'Affichage :debut à :fin sur',
        'entites' => [
            'clients' => ':compte client|:compte clients',
            'profils' => ':compte profil|:compte profils',
            'paiements' => ':compte paiement|:compte paiements',
            'abonnements' => ':compte abonnement|:compte abonnements',
        ],
    ],

    'journal' => [
        'titre' => 'Journal d\'audit',
        'sous_titre' => 'Traçabilité complète des actions administratives sensibles.',
        'recherche' => 'Action, cible ou motif…',
        'tous_administrateurs' => 'Tous les administrateurs',
        'type_action' => 'Type d\'action',
        'tous_types' => 'Tous les types',
        'entree' => ':compte entrée|:compte entrées',
        'entree_singulier' => 'entrée',
        'affichage' => 'Affichage :debut à :fin sur',
        'vide' => 'Les actions d\'administration seront consignées ici.',
    ],

    'cartes' => [
        'titre' => 'Cartes à produire',
        'sous_titre' => 'Production par lots, export imprimeur et suivi des expéditions.',
        'export_imprimeur' => 'Export imprimeur',
        'seuil_atteint' => 'Seuil atteint — lancez un lot',
        'delai_depasse' => 'Délai annoncé dépassé',
        'renouvellent' => 'renouvellent au 2ᵉ trimestre',
        'clients_payants' => 'client(s) payant(s)',
        'encaisses' => 'encaissés',
        'filtrer_etat' => 'Filtrer par état',
        'vide' => 'Les commandes de cartes apparaîtront ici dès la première activation payée.',
        'adresse_manquante' => 'Adresse manquante',
        'creer_lot' => 'Créer un lot avec la sélection',
        'creer_lot_aide' => 'Seules les commandes en attente et dont l\'adresse est complète sont incluses.',
        'faire_passer' => 'Faire passer le lot',
    ],

    'clients' => [
        'titre' => 'Liste des clients',
        'sous_titre' => 'Gérer et consulter la totalité des utilisateurs inscrits.',
        'recherche' => 'Nom, e-mail ou téléphone…',
        'statut_compte' => 'Statut du compte',
        'tous_statuts' => 'Tous les statuts',
        'compte_actif' => 'Compte actif',
        'compte_bloque' => 'Compte bloqué',
        'avec_abonnement' => 'Avec abonnement',
        'sans_abonnement' => 'Sans abonnement',
        'aucun_profil' => 'Aucun profil',
        'essai_gratuit' => 'Essai gratuit',
        'vide' => 'Les comptes clients apparaîtront ici dès la première inscription.',
    ],

    'fiche' => [
        'titre' => 'Fiche client',
        'inscrit_le' => 'Inscrit le',
        'motif_blocage' => 'Motif du blocage :',
        'identite_pro' => 'Identité professionnelle',
        'aucun_profil_titre' => 'Aucun profil créé',
        'aucun_profil_message' => 'Ce compte existe mais son propriétaire n\'a pas encore rempli sa carte.',
        'motif_desactivation' => 'Motif de désactivation',
        'journal_activite' => 'Journal d\'activité',
        'aucun_abonnement_titre' => 'Aucun abonnement',
        'aucun_abonnement_message' => 'Ce client n\'a jamais souscrit.',
        'aucune_transaction_titre' => 'Aucune transaction',
        'aucune_transaction_message' => 'Ce client n\'a jamais payé.',
        'aucune_action_titre' => 'Aucune action administrative',
        'aucune_action_message' => 'Aucun blocage, aucune désactivation, aucune prolongation sur ce compte.',

        'desactiver_profil' => 'Désactiver le profil',
        'desactiver' => 'Désactiver',
        'desactiver_titre' => 'Désactiver ce profil',
        'desactiver_texte' => 'La carte cesse immédiatement d\'être accessible publiquement. Le contenu n\'est pas supprimé.',
        'reactiver_profil' => 'Réactiver le profil',
        'lever_desactivation' => 'Lever la désactivation',
        'reactiver_texte' => 'Le profil restera en brouillon : c\'est à son propriétaire de le republier.',
        'debloquer_compte' => 'Débloquer le compte',
        'debloquer' => 'Débloquer',
        'debloquer_titre' => 'Débloquer ce compte',
        'debloquer_texte' => 'Le client pourra de nouveau se connecter. Motif du blocage : :motif.',
        'motif_non_renseigne' => 'non renseigné',
        'bloquer_compte' => 'Bloquer le compte',
        'bloquer_titre' => 'Bloquer ce compte',
        'bloquer_texte' => 'Le client ne pourra plus se connecter et ses sessions ouvertes seront fermées.',
        'prolonger' => 'Prolonger l\'abonnement',
        'prolonger_titre' => 'Prolonger manuellement l\'abonnement',
        'nombre_jours' => 'Nombre de jours',
        'prolonger_aide' => 'Au-delà de :jours jours, ce n\'est plus un geste commercial : passez par une formule, qui laisse une trace comptable.',
    ],

    'vue_ensemble' => [
        'titre' => 'Vue d\'ensemble',
        'sous_titre' => 'Suivi de l\'activité de la plateforme aujourd\'hui.',
        'sur_periode_precedente' => 'sur la période précédente',
        'tendance_inscriptions' => 'Tendance des inscriptions',
        'nouveaux_comptes' => 'Nouveaux comptes',
        'aucune_inscription_periode_titre' => 'Aucune inscription sur la période',
        'aucune_inscription_periode_message' => 'Le graphique apparaîtra dès la première création de compte.',
        'moyens_paiement' => 'Moyens de paiement',
        'aucun_encaissement_titre' => 'Aucun paiement encaissé',
        'aucun_encaissement_message' => 'La répartition apparaîtra au premier encaissement.',
        'dernieres_inscriptions' => 'Dernières inscriptions',
        'aucune_inscription_titre' => 'Aucune inscription',
        'aucune_inscription_message' => 'Les nouveaux comptes apparaîtront ici.',
        'derniers_paiements' => 'Derniers paiements',
        'aucun_paiement_titre' => 'Aucun paiement',
        'aucun_paiement_message' => 'Les encaissements apparaîtront ici.',
    ],

    'paiements' => [
        'titre' => 'Liste des paiements',
        'sous_titre' => 'Suivi des transactions financières, avec vérification manuelle auprès de l\'opérateur.',
        'reussis' => 'Réussis',
        'en_attente' => 'En attente',
        'echoues' => 'Échoués',
        'filtrer_statut' => 'Filtrer par statut',
        'moyen_paiement' => 'Moyen de paiement',
        'tous_moyens' => 'Tous les moyens',
        'reference_date' => 'Référence et date',
        'verification' => 'Vérification',
        'verifier' => 'Vérifier',
        'verifier_titre' => 'Vérifier ce paiement auprès de l\'opérateur',
        'verifier_texte' => 'La référence :reference est soumise à l\'opérateur. Si elle confirme l\'encaissement, l\'abonnement est ouvert immédiatement. L\'opération est sans effet si le paiement a déjà été encaissé.',
        'total_filtre' => 'Total encaissé sur ce filtre :',
        'vide' => 'Les paiements apparaîtront ici dès le premier encaissement.',
    ],

    'profils' => [
        'titre' => 'Liste des profils',
        'sous_titre' => 'Suivre et contrôler les cartes numériques publiées par les clients.',
        'recherche' => 'Nom ou identifiant public…',
        'etat_profil' => 'État du profil',
        'tous_etats' => 'Tous les états',
        'modele_utilise' => 'Modèle utilisé',
        'tous_modeles' => 'Tous les modèles',
        'reactiver' => 'Réactiver',
        'reactiver_texte' => 'Le profil :nom restera en brouillon : c\'est à son propriétaire de le republier.',
        'desactiver' => 'Désactiver',
        'desactiver_ce_profil' => 'Désactiver ce profil',
        'desactiver_texte' => 'La carte cesse immédiatement d\'être accessible publiquement. Le contenu n\'est pas supprimé.',
        'vide' => 'Les cartes créées par vos clients apparaîtront ici.',
    ],

    'parametres' => [
        'titre' => 'Paramètres de la plateforme',
        'sous_titre' => 'Gérer les offres tarifaires proposées aux clients.',
        'sections' => 'Sections des paramètres',
        'aucun_reglage' => 'Aucun réglage modifiable ici',
        'offres_actuelles' => 'Offres actuelles',
        'retiree' => 'Retirée',
        'aucune_formule_titre' => 'Aucune formule',
        'aucune_formule_message' => 'Créez une première formule pour ouvrir la vente.',
        'creer_formule' => 'Créer une nouvelle formule',
        'nom_formule' => 'Nom de la formule',
        'identifiant_technique' => 'Identifiant technique',
        'identifiant_definitif' => 'Définitif : il est inscrit dans chaque paiement et ne pourra plus être modifié.',
        'identifiant_fige' => 'Non modifiable : cet identifiant est inscrit dans chaque paiement déjà encaissé.',
        'prix_fcfa' => 'Prix en FCFA',
        'prix_aide' => 'Nombre entier : le franc CFA n\'a pas de subdivision en circulation.',
        'periodicite' => 'Périodicité',
        'creer' => 'Créer la formule',
        'aucune_a_modifier_titre' => 'Aucune formule à modifier',
        'aucune_a_modifier_message' => 'Créez d\'abord une formule dans la colonne de gauche.',
        'editeur' => 'Éditeur de formule',
        'prix_aide_edition' => 'Les abonnements en cours ne sont pas touchés : le nouveau tarif s\'applique aux souscriptions suivantes.',
        'elements_inclus' => 'Éléments inclus',
        'ajouter_element' => 'Ajouter un élément…',
        'nouvel_element' => 'Nouvel élément inclus',
        'vider_pour_retirer' => 'Videz une ligne pour retirer l\'élément correspondant.',
        'proposee_vente' => 'Formule proposée à la vente',
        'onglets' => [
            'plans' => 'Plans tarifaires',
            'general' => 'Paramètres généraux',
            'securite' => 'Sécurité',
        ],
    ],

    'statistiques' => [
        'titre' => 'Statistiques d\'usage',
        'sous_titre' => 'Ce que les cartes produisent réellement : consultations, scans et enregistrements.',
        'interactions_totales' => 'Interactions totales',
        'scans_qr' => 'Scans de QR Code',
        'part_interactions' => '% des interactions',
        'interactions_par_jour' => 'Interactions par jour',
        'toutes_interactions' => 'Toutes interactions',
        'aucune_interaction_titre' => 'Aucune interaction sur la période',
        'aucune_interaction_message' => 'Aucune carte n\'a été consultée ni scannée. Changez de période, ou vérifiez qu\'une carte est publiée.',
        'cartes_plus_consultees' => 'Cartes les plus consultées',
        'tous_profils' => 'Tous les profils',
        'aucune_carte_consultee_titre' => 'Aucune carte consultée',
        'aucune_carte_consultee_message' => 'Le classement apparaîtra dès la première consultation.',
        'publies' => 'Publiés',
        'desactives' => 'Désactivés',
        'etat_profils' => 'État des profils',
        'modeles_utilises' => 'Modèles utilisés',
        'aucune_carte_publiee_titre' => 'Aucune carte publiée',
        'aucune_carte_publiee_message' => 'La répartition apparaîtra à la première publication.',
    ],

    'abonnements' => [
        'sous_titre' => 'Suivre les échéances et l\'état des souscriptions en cours.',
        'filtrer_statut' => 'Filtrer par statut',
        'toutes_formules' => 'Toutes les formules',
        'toutes_echeances' => 'Toutes les échéances',
        'echoit_3' => 'Échoit sous 3 jours',
        'echoit_7' => 'Échoit sous 7 jours',
        'echoit_30' => 'Échoit sous 30 jours',
        'vide' => 'Les abonnements apparaîtront ici dès la première souscription.',
    ],

    'sante' => [
        'titre' => 'État système',
        'jobs_echec' => ':compte job(s) en échec. Inspectez-les puis relancez avec :commande.',
        'file_engorgee' => 'File « mail » engorgée (:compte en attente). Vérifiez que le worker tourne.',
        'file_mail' => 'File « mail »',
        'total_jobs' => 'Total jobs',
        'jobs_echoues' => 'Jobs échoués',
        'a_relancer' => 'à relancer',
        'mails_bloques_titre' => 'Les e-mails ne partent probablement pas.',
        'mails_bloques_texte' => 'Le pilote de file est :pilote, ce qui suppose un worker exécutant :commande. Le plan gratuit de Render n\'en fait pas tourner : les messages sont écrits dans la table :table et jamais repris — sans la moindre erreur.',
        'mails_bloques_correction' => 'Correction immédiate : passer :variable à :valeur dans les variables d\'environnement, puis redéployer. L\'envoi se fera dans la requête — plus lent d\'une seconde ou deux, mais il aboutira.',
        'derniers_mails' => 'Derniers e-mails envoyés',
        'aujourdhui' => 'aujourd\'hui',
        'aucun_mail' => 'Aucun e-mail enregistré',
        'envoye' => 'Envoyé',
        'rafraichi' => 'Rafraîchi au chargement de la page. Aucune donnée temps réel côté navigateur.',
    ],

    'modeles' => [
        'titre' => 'Modèles de carte',
        'sous_titre' => 'Gérer les gabarits visuels proposés aux clients à la création de leur carte.',
        'creation_aide' => 'Un nouveau modèle se crée en dupliquant un existant, puis en le relisant.',
        'filtrer' => 'Filtrer les modèles',
        'aucun_titre' => 'Aucun modèle',
        'aucun_message' => 'Aucun gabarit ne correspond à cet onglet.',
        'le_modele' => 'le modèle',
        'par_defaut' => 'Par défaut',
        'definir_defaut' => 'Définir par défaut',
    ],

    /*
     | LES MESSAGES FLASH. Ils sont lus par CELUI QUI VIENT D'AGIR — donc
     | par l'administrateur, dans sa langue, comme le reste de l'ecran.
     |
     | Le nom propre insere au milieu n'est jamais traduit.
     */
    'flash' => [
        'compte_bloque' => 'Le compte de :nom est bloqué. Ses sessions ont été fermées.',
        'compte_debloque' => 'Le compte de :nom est de nouveau actif.',
        'pas_soi_meme' => 'Vous ne pouvez pas bloquer votre propre compte.',
        'pas_un_admin' => 'Un compte administrateur ne se bloque pas depuis cet écran.',
        'formule_creee' => 'La formule « :nom » est créée.',
        'modele_par_defaut' => '« :nom » est désormais le modèle proposé par défaut.',
        'aucune_adresse' => 'Aucune commande sélectionnée n\'a d\'adresse complète.',
        'lot_cree' => 'Lot :lot créé avec :compte carte(s).',
        'cartes_mises_a_jour' => ':compte carte(s) mises à jour.',
        'profil_deja_desactive' => 'Ce profil était déjà désactivé.',
        'profil_desactive' => 'Le profil « :nom » n\'est plus accessible publiquement.',
        'profil_pas_desactive' => 'Ce profil n\'était pas désactivé.',
        'aucun_abonnement_a_prolonger' => 'Ce client n\'a aucun abonnement à prolonger.',
    ],

    'raccourcis' => [
        'paiements' => 'Voir les paiements',
        'clients' => 'Voir les clients',
    ],

    /*
     | LE RAPPORT QUOTIDIEN part a l'equipe. Il est construit UNE fois par
     | la commande planifiee, donc dans la langue par defaut : un rapport
     | unique ne peut pas etre a la fois francais et anglais.
     */
    'rapport' => [
        'nouveaux_comptes' => 'Nouveaux comptes',
        'cartes_creees' => 'Cartes créées',
        'cartes_publiees' => 'Cartes mises en ligne',
        'paiements' => 'Paiements encaissés',
        'messages' => 'Messages reçus',
    ],

    /*
     | LES LIBELLES DU JOURNAL D'AUDIT.
     |
     | Ils decrivent une action PASSEE, mais ils sont rendus au moment de
     | la LECTURE : le journal s'affiche donc dans la langue de qui le
     | consulte, pas dans celle de qui a agi. C'est le bon choix — un
     | journal se relit, souvent par quelqu'un d'autre.
     */
    'actions' => [
        'compte_bloque' => 'Compte bloqué',
        'compte_debloque' => 'Compte débloqué',
        'profil_desactive' => 'Profil désactivé',
        'profil_reactive' => 'Profil réactivé',
        'abonnement_prolonge' => 'Abonnement prolongé',
        'paiement_verifie' => 'Paiement vérifié',
        'modele_bascule' => 'Modèle activé ou désactivé',
        'modele_duplique' => 'Modèle dupliqué',
        'modele_defaut' => 'Modèle défini par défaut',
        'plan_cree' => 'Plan créé',
        'plan_modifie' => 'Plan modifié',
        'lot_cartes' => 'Lot de cartes',
    ],
];
