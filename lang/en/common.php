<?php

/*
|--------------------------------------------------------------------------
| SHARED VOCABULARY
|--------------------------------------------------------------------------
| The words that come back everywhere: actions, states, units, common field
| labels. Everything that belongs to NO screen in particular.
|
| Keys are identical to lang/fr/common.php, one for one. `lang:check` fails
| the build if they ever drift apart.
*/

return [

    // -----------------------------------------------------------------
    // Actions
    // -----------------------------------------------------------------
    'actions' => [
        'enregistrer' => 'Save',
        'annuler' => 'Cancel',
        'continuer' => 'Continue',
        'retour' => 'Back',
        'precedent' => 'Previous',
        'suivant' => 'Next',
        'fermer' => 'Close',
        'supprimer' => 'Delete',
        'modifier' => 'Edit',
        'appliquer' => 'Apply',
        'reinitialiser' => 'Reset',
        'activer' => 'Enable',
        'desactiver' => 'Disable',
        'rechercher' => 'Search',
        'filtrer' => 'Filter',
        'exporter' => 'Export',
        'copier' => 'Copy',
        'partager' => 'Share',
        'telecharger' => 'Download',
        'voir' => 'View',
        'voir_tout' => 'See all',
        'confirmer' => 'Confirm',
        'reessayer' => 'Try again',
        'ouvrir' => 'Open',
    ],

    // -----------------------------------------------------------------
    // States
    // -----------------------------------------------------------------
    'etats' => [
        'actif' => 'Active',
        'inactif' => 'Inactive',
        'en_attente' => 'Pending',
        'suspendu' => 'Suspended',
        'expire' => 'Expired',
        'paye' => 'Paid',
        'echoue' => 'Failed',
        'rembourse' => 'Refunded',
        'essai' => 'Free trial',
        'brouillon' => 'Draft',
        'publie' => 'Published',
        'oui' => 'Yes',
        'non' => 'No',
        'aucun' => 'None',
        'jamais' => 'Never',
    ],

    // -----------------------------------------------------------------
    // Fields and forms
    // -----------------------------------------------------------------
    'champs' => [
        'nom' => 'Name',
        'prenom' => 'First name',
        'email' => 'Email address',
        'email_court' => 'Email',
        'telephone' => 'Phone',
        'mobile' => 'Mobile',
        'mot_de_passe' => 'Password',
        'entreprise' => 'Company',
        'fonction' => 'Job title',
        'adresse' => 'Address',
        'ville' => 'City',
        'pays' => 'Country',
        'site_web' => 'Website',
        'lien' => 'Link',
        'localisation' => 'Location',
        'message' => 'Message',
        'motif' => 'Subject',
        'date' => 'Date',
        'statut' => 'Status',
        'montant' => 'Amount',
        'periode' => 'Period',
        'optionnel' => 'optional',
        'obligatoire' => 'required',
        'astensque' => 'Fields marked with an asterisk are required.',
        'piege' => 'Do not fill in this field',
        'indicatif' => 'Country code',
        'afficher_mot_de_passe' => 'Show password',
    ],

    // -----------------------------------------------------------------
    // Time
    // -----------------------------------------------------------------
    'temps' => [
        'jour' => ':compte day|:compte days',
        'mois' => ':compte month|:compte months',
        'annee' => ':compte year|:compte years',
        'minute' => ':compte minute|:compte minutes',
        'jours' => 'Days',
        'minutes' => 'Minutes',
        'aujourdhui' => 'Today',
        'hier' => 'Yesterday',
        'par_mois' => 'Per month',
        'par_an' => 'Per year',
        'par_semaine' => 'Per week',
        'pour_n_mois' => 'For :compte months',
    ],

    // -----------------------------------------------------------------
    // Empty states
    // -----------------------------------------------------------------
    'vide' => [
        'rien' => 'Nothing yet.',
        'aucun_resultat' => 'No results.',
        'aucune_donnee' => 'No data for this period.',
    ],

    'divers' => [
        'chargement' => 'Loading…',
        'sur' => 'of',
        'ou' => 'or',
        'et' => 'and',
        'total' => 'Total',
        'fil_ariane' => 'Breadcrumb',
    ],

    /*
     | FORMATS — separators, dates, currency.
     |
     | They live here rather than in a match() on the locale: adding a third
     | language will not require coming back to edit PHP code.
     |
     | Note the backslashes in `date_heure`: `a` and `t` are both date()
     | format characters. Left unescaped, "at" would render as "pm31" —
     | a bug that only shows up in one language, on one line.
     */
    'formats' => [
        'separateur_milliers' => ',',
        'separateur_decimal' => '.',
        'devise' => 'FCFA',
        'date_longue' => 'F j, Y',
        'date_heure' => 'F j, Y \a\t g:i A',
        'date_complete' => 'l F j, Y',
        'date_courte' => 'm/d',
        'mois_court' => 'M',
    ],

    /*
     | THE PLURAL RULE LIVES HERE, NOT IN THE TEMPLATE.
     | The template wrote « resultat{{ $n > 1 ? 's' : '' }} »: a French rule
     | hard-coded in markup, which happened to be right in English and would
     | be wrong in any language with three forms.
     */
    'pagination' => [
        'sur' => 'of',
        'resultat' => ':compte result|:compte results',
    ],

    'theme' => [
        'clair' => 'Light theme',
        'sombre' => 'Dark theme',
        'vers_clair' => 'Switch to light theme',
        'vers_sombre' => 'Switch to dark theme',
    ],
];
