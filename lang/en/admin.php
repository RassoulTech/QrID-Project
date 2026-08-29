<?php

/*
|--------------------------------------------------------------------------
| ADMIN AREA
|--------------------------------------------------------------------------
|
| The admin area follows the language of the SIGNED-IN ADMINISTRATOR, not
| that of the customer whose record is on screen. It is the only rule that
| holds: these screens are read all day long, and a customer record is not
| going to switch language depending on who it describes.
|
| Content ENTERED by customers — name, job title, company, a block reason
| written by a colleague — is never translated. Only interface labels are.
|
*/

return [

    'commun' => [
        'exporter_csv' => 'Export CSV',
        'reinitialiser' => 'Reset',
        'reinitialiser_filtres' => 'Reset filters',
        'apres_filtrage' => 'after filtering',
        'periode' => 'Period',
        'etat' => 'State',
        'statut' => 'Status',
        'date_heure' => 'Date and time',
        'telephone' => 'Phone',
        'modele' => 'Template',
        'identifiant_public' => 'Public handle',
        'nom_complet' => 'Full name',
        'cree_le' => 'Created on',
        'debut' => 'Start',
        'echeance' => 'Expiry',
        'voir_tout' => 'View all',
        'publie' => 'Published',
        'desactive' => 'Deactivated',
        'compte_supprime' => 'Account deleted',
        'fil_ariane' => 'Breadcrumb',
        'motif_journal' => 'This reason will be read in the audit log six months from now. Write a sentence that stands on its own.',
        'motif' => 'Reason',
        'affichage' => 'Showing :debut to :fin of',
        'entites' => [
            'clients' => ':compte customer|:compte customers',
            'profils' => ':compte profile|:compte profiles',
            'paiements' => ':compte payment|:compte payments',
            'abonnements' => ':compte subscription|:compte subscriptions',
        ],
    ],

    'journal' => [
        'titre' => 'Audit log',
        'sous_titre' => 'Full traceability of sensitive administrative actions.',
        'recherche' => 'Action, target or reason…',
        'tous_administrateurs' => 'All administrators',
        'type_action' => 'Action type',
        'tous_types' => 'All types',
        'entree' => ':compte entry|:compte entries',
        'entree_singulier' => 'entry',
        'affichage' => 'Showing :debut to :fin of',
        'vide' => 'Administrative actions will be recorded here.',
    ],

    'cartes' => [
        'titre' => 'Cards to produce',
        'sous_titre' => 'Batch production, printer export and shipment tracking.',
        'export_imprimeur' => 'Printer export',
        'seuil_atteint' => 'Threshold reached — start a batch',
        'delai_depasse' => 'Announced lead time exceeded',
        'renouvellent' => 'renew in Q2',
        'clients_payants' => 'paying customer(s)',
        'encaisses' => 'collected',
        'filtrer_etat' => 'Filter by state',
        'vide' => 'Card orders will appear here from the first paid activation.',
        'adresse_manquante' => 'Address missing',
        'creer_lot' => 'Create a batch from the selection',
        'creer_lot_aide' => 'Only pending orders with a complete address are included.',
        'faire_passer' => 'Advance the batch',
    ],

    'clients' => [
        'titre' => 'Customer list',
        'sous_titre' => 'Manage and review every registered user.',
        'recherche' => 'Name, email or phone…',
        'statut_compte' => 'Account status',
        'tous_statuts' => 'All statuses',
        'compte_actif' => 'Active account',
        'compte_bloque' => 'Blocked account',
        'avec_abonnement' => 'With subscription',
        'sans_abonnement' => 'Without subscription',
        'aucun_profil' => 'No profile',
        'essai_gratuit' => 'Free trial',
        'vide' => 'Customer accounts will appear here from the first registration.',
    ],

    'fiche' => [
        'titre' => 'Customer record',
        'inscrit_le' => 'Registered on',
        'motif_blocage' => 'Reason for blocking:',
        'identite_pro' => 'Professional identity',
        'aucun_profil_titre' => 'No profile created',
        'aucun_profil_message' => 'This account exists but its owner has not filled in their card yet.',
        'motif_desactivation' => 'Reason for deactivation',
        'journal_activite' => 'Activity log',
        'aucun_abonnement_titre' => 'No subscription',
        'aucun_abonnement_message' => 'This customer has never subscribed.',
        'aucune_transaction_titre' => 'No transaction',
        'aucune_transaction_message' => 'This customer has never paid.',
        'aucune_action_titre' => 'No administrative action',
        'aucune_action_message' => 'No blocking, no deactivation, no extension on this account.',

        'desactiver_profil' => 'Deactivate the profile',
        'desactiver' => 'Deactivate',
        'desactiver_titre' => 'Deactivate this profile',
        'desactiver_texte' => 'The card immediately stops being publicly reachable. The content is not deleted.',
        'reactiver_profil' => 'Reactivate the profile',
        'lever_desactivation' => 'Lift the deactivation',
        'reactiver_texte' => 'The profile will stay a draft: republishing is up to its owner.',
        'debloquer_compte' => 'Unblock the account',
        'debloquer' => 'Unblock',
        'debloquer_titre' => 'Unblock this account',
        'debloquer_texte' => 'The customer will be able to sign in again. Reason for blocking: :motif.',
        'motif_non_renseigne' => 'not given',
        'bloquer_compte' => 'Block the account',
        'bloquer_titre' => 'Block this account',
        'bloquer_texte' => 'The customer will no longer be able to sign in and their open sessions will be closed.',
        'prolonger' => 'Extend the subscription',
        'prolonger_titre' => 'Extend the subscription manually',
        'nombre_jours' => 'Number of days',
        'prolonger_aide' => 'Beyond :jours days this is no longer a goodwill gesture: use a plan instead, which leaves an accounting trail.',
    ],

    'vue_ensemble' => [
        'titre' => 'Overview',
        'sous_titre' => 'Today\'s platform activity at a glance.',
        'sur_periode_precedente' => 'vs. the previous period',
        'tendance_inscriptions' => 'Registration trend',
        'nouveaux_comptes' => 'New accounts',
        'aucune_inscription_periode_titre' => 'No registration in this period',
        'aucune_inscription_periode_message' => 'The chart will appear as soon as an account is created.',
        'moyens_paiement' => 'Payment methods',
        'aucun_encaissement_titre' => 'No payment collected',
        'aucun_encaissement_message' => 'The breakdown will appear on the first payment.',
        'dernieres_inscriptions' => 'Latest registrations',
        'aucune_inscription_titre' => 'No registration',
        'aucune_inscription_message' => 'New accounts will appear here.',
        'derniers_paiements' => 'Latest payments',
        'aucun_paiement_titre' => 'No payment',
        'aucun_paiement_message' => 'Collected payments will appear here.',
    ],

    'paiements' => [
        'titre' => 'Payment list',
        'sous_titre' => 'Tracking of financial transactions, with manual verification against the operator.',
        'reussis' => 'Successful',
        'en_attente' => 'Pending',
        'echoues' => 'Failed',
        'filtrer_statut' => 'Filter by status',
        'moyen_paiement' => 'Payment method',
        'tous_moyens' => 'All methods',
        'reference_date' => 'Reference and date',
        'verification' => 'Verification',
        'verifier' => 'Verify',
        'verifier_titre' => 'Verify this payment with the operator',
        'verifier_texte' => 'Reference :reference is submitted to the operator. If it confirms the payment, the subscription opens immediately. The operation has no effect if the payment has already been collected.',
        'total_filtre' => 'Total collected for this filter:',
        'vide' => 'Payments will appear here from the first collection.',
    ],

    'profils' => [
        'titre' => 'Profile list',
        'sous_titre' => 'Track and control the digital cards published by customers.',
        'recherche' => 'Name or public handle…',
        'etat_profil' => 'Profile state',
        'tous_etats' => 'All states',
        'modele_utilise' => 'Template used',
        'tous_modeles' => 'All templates',
        'reactiver' => 'Reactivate',
        'reactiver_texte' => 'Profile :nom will stay a draft: republishing is up to its owner.',
        'desactiver' => 'Deactivate',
        'desactiver_ce_profil' => 'Deactivate this profile',
        'desactiver_texte' => 'The card immediately stops being publicly reachable. The content is not deleted.',
        'vide' => 'Cards created by your customers will appear here.',
    ],

    'parametres' => [
        'titre' => 'Platform settings',
        'sous_titre' => 'Manage the pricing plans offered to customers.',
        'sections' => 'Settings sections',
        'aucun_reglage' => 'No setting can be changed here',
        'offres_actuelles' => 'Current plans',
        'retiree' => 'Withdrawn',
        'aucune_formule_titre' => 'No plan',
        'aucune_formule_message' => 'Create a first plan to open sales.',
        'creer_formule' => 'Create a new plan',
        'nom_formule' => 'Plan name',
        'identifiant_technique' => 'Technical identifier',
        'identifiant_definitif' => 'Final: it is written into every payment and can no longer be changed.',
        'identifiant_fige' => 'Not editable: this identifier is written into every payment already collected.',
        'prix_fcfa' => 'Price in FCFA',
        'prix_aide' => 'Whole number: the CFA franc has no subunit in circulation.',
        'periodicite' => 'Billing period',
        'creer' => 'Create the plan',
        'aucune_a_modifier_titre' => 'No plan to edit',
        'aucune_a_modifier_message' => 'Create a plan in the left-hand column first.',
        'editeur' => 'Plan editor',
        'prix_aide_edition' => 'Running subscriptions are untouched: the new price applies to subsequent sign-ups.',
        'elements_inclus' => 'Included items',
        'ajouter_element' => 'Add an item…',
        'nouvel_element' => 'New included item',
        'vider_pour_retirer' => 'Empty a line to remove the matching item.',
        'proposee_vente' => 'Plan offered for sale',
        'onglets' => [
            'plans' => 'Pricing plans',
            'general' => 'General settings',
            'securite' => 'Security',
        ],
    ],

    'statistiques' => [
        'titre' => 'Usage statistics',
        'sous_titre' => 'What the cards actually produce: views, scans and saves.',
        'interactions_totales' => 'Total interactions',
        'scans_qr' => 'QR code scans',
        'part_interactions' => '% of interactions',
        'interactions_par_jour' => 'Interactions per day',
        'toutes_interactions' => 'All interactions',
        'aucune_interaction_titre' => 'No interaction in this period',
        'aucune_interaction_message' => 'No card has been viewed or scanned. Change the period, or check that a card is published.',
        'cartes_plus_consultees' => 'Most viewed cards',
        'tous_profils' => 'All profiles',
        'aucune_carte_consultee_titre' => 'No card viewed',
        'aucune_carte_consultee_message' => 'The ranking will appear on the first view.',
        'publies' => 'Published',
        'desactives' => 'Deactivated',
        'etat_profils' => 'Profile states',
        'modeles_utilises' => 'Templates in use',
        'aucune_carte_publiee_titre' => 'No card published',
        'aucune_carte_publiee_message' => 'The breakdown will appear on the first publication.',
    ],

    'abonnements' => [
        'sous_titre' => 'Track expiry dates and the state of running subscriptions.',
        'filtrer_statut' => 'Filter by status',
        'toutes_formules' => 'All plans',
        'toutes_echeances' => 'All expiry dates',
        'echoit_3' => 'Expires within 3 days',
        'echoit_7' => 'Expires within 7 days',
        'echoit_30' => 'Expires within 30 days',
        'vide' => 'Subscriptions will appear here from the first sign-up.',
    ],

    'sante' => [
        'titre' => 'System health',
        'jobs_echec' => ':compte failed job(s). Inspect them, then retry with :commande.',
        'file_engorgee' => 'The "mail" queue is backing up (:compte waiting). Check that the worker is running.',
        'file_mail' => '"mail" queue',
        'total_jobs' => 'Total jobs',
        'jobs_echoues' => 'Failed jobs',
        'a_relancer' => 'to retry',
        'mails_bloques_titre' => 'Emails are probably not going out.',
        'mails_bloques_texte' => 'The queue driver is :pilote, which assumes a worker running :commande. Render\'s free plan runs none: messages are written to the :table table and never picked up — without a single error.',
        'mails_bloques_correction' => 'Immediate fix: set :variable to :valeur in the environment variables, then redeploy. Sending will happen inside the request — a second or two slower, but it will get through.',
        'derniers_mails' => 'Latest emails sent',
        'aujourdhui' => 'today',
        'aucun_mail' => 'No email recorded',
        'envoye' => 'Sent',
        'rafraichi' => 'Refreshed on page load. No real-time data on the browser side.',
    ],

    'modeles' => [
        'titre' => 'Card templates',
        'sous_titre' => 'Manage the visual templates offered to customers when they create their card.',
        'creation_aide' => 'A new template is created by duplicating an existing one, then proofreading it.',
        'filtrer' => 'Filter templates',
        'aucun_titre' => 'No template',
        'aucun_message' => 'No template matches this tab.',
        'le_modele' => 'the template',
        'par_defaut' => 'Default',
        'definir_defaut' => 'Set as default',
    ],

    /*
     | FLASH MESSAGES. They are read by WHOEVER JUST ACTED — so by the
     | administrator, in their language, like the rest of the screen.
     |
     | The proper noun dropped in the middle is never translated.
     */
    'flash' => [
        'compte_bloque' => ':nom\'s account is blocked. Their sessions have been closed.',
        'compte_debloque' => ':nom\'s account is active again.',
        'pas_soi_meme' => 'You cannot block your own account.',
        'pas_un_admin' => 'An administrator account cannot be blocked from this screen.',
        'formule_creee' => 'The ":nom" plan has been created.',
        'modele_par_defaut' => '":nom" is now the default template.',
        'aucune_adresse' => 'No selected order has a complete address.',
        'lot_cree' => 'Batch :lot created with :compte card(s).',
        'cartes_mises_a_jour' => ':compte card(s) updated.',
        'profil_deja_desactive' => 'This profile was already deactivated.',
        'profil_desactive' => 'The profile ":nom" is no longer publicly reachable.',
        'profil_pas_desactive' => 'This profile was not deactivated.',
        'aucun_abonnement_a_prolonger' => 'This customer has no subscription to extend.',
    ],

    'raccourcis' => [
        'paiements' => 'View payments',
        'clients' => 'View customers',
    ],

    /*
     | THE DAILY REPORT goes to the team. It is built ONCE by the scheduled
     | command, therefore in the default language: a single report cannot be
     | both French and English.
     */
    'rapport' => [
        'nouveaux_comptes' => 'New accounts',
        'cartes_creees' => 'Cards created',
        'cartes_publiees' => 'Cards published',
        'paiements' => 'Payments collected',
        'messages' => 'Messages received',
    ],

    /*
     | AUDIT LOG LABELS.
     |
     | They describe a PAST action, but they are rendered at READ time: the
     | log therefore shows in the language of whoever is reading it, not
     | whoever acted. That is the right call — a log gets re-read, often by
     | someone else.
     */
    'actions' => [
        'compte_bloque' => 'Account blocked',
        'compte_debloque' => 'Account unblocked',
        'profil_desactive' => 'Profile deactivated',
        'profil_reactive' => 'Profile reactivated',
        'abonnement_prolonge' => 'Subscription extended',
        'paiement_verifie' => 'Payment verified',
        'modele_bascule' => 'Template enabled or disabled',
        'modele_duplique' => 'Template duplicated',
        'modele_defaut' => 'Template set as default',
        'plan_cree' => 'Plan created',
        'plan_modifie' => 'Plan changed',
        'lot_cartes' => 'Card batch',
    ],
];
