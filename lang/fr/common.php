<?php

/*
|--------------------------------------------------------------------------
| VOCABULAIRE PARTAGÉ
|--------------------------------------------------------------------------
| Les mots qui reviennent partout : actions, états, unités, libellés de
| champs communs. Tout ce qui n'appartient à AUCUN écran en particulier.
|
| LA RÈGLE D'ARBITRAGE, quand on hésite entre ce fichier et un fichier de
| domaine : un mot vient ici s'il garderait exactement le même sens dans un
| autre produit. « Enregistrer » est commun. « Publier ma carte » ne l'est
| pas — il appartient à card.php, même s'il contient un verbe.
|
| Sans cette règle, common.php devient le fourre-tout que la consigne
| interdit, et l'on finit par y chercher chaque clé avant de la trouver
| ailleurs.
*/

return [

    // -----------------------------------------------------------------
    // Actions
    // -----------------------------------------------------------------
    'actions' => [
        'enregistrer' => 'Enregistrer',
        'annuler' => 'Annuler',
        'continuer' => 'Continuer',
        'retour' => 'Retour',
        'precedent' => 'Précédent',
        'suivant' => 'Suivant',
        'fermer' => 'Fermer',
        'supprimer' => 'Supprimer',
        'modifier' => 'Modifier',
        'appliquer' => 'Appliquer',
        'reinitialiser' => 'Réinitialiser',
        'rechercher' => 'Rechercher',
        'filtrer' => 'Filtrer',
        'exporter' => 'Exporter',
        'copier' => 'Copier',
        'partager' => 'Partager',
        'telecharger' => 'Télécharger',
        'voir' => 'Voir',
        'voir_tout' => 'Voir tout',
        'confirmer' => 'Confirmer',
        'reessayer' => 'Réessayer',
        'ouvrir' => 'Ouvrir',
    ],

    // -----------------------------------------------------------------
    // États et statuts
    // -----------------------------------------------------------------
    'etats' => [
        'actif' => 'Actif',
        'inactif' => 'Inactif',
        'en_attente' => 'En attente',
        'suspendu' => 'Suspendu',
        'expire' => 'Expiré',
        'paye' => 'Payé',
        'echoue' => 'Échoué',
        'rembourse' => 'Remboursé',
        'essai' => 'Essai gratuit',
        'brouillon' => 'Brouillon',
        'publie' => 'Publié',
        'oui' => 'Oui',
        'non' => 'Non',
        'aucun' => 'Aucun',
        'jamais' => 'Jamais',
    ],

    // -----------------------------------------------------------------
    // Champs et formulaires
    // -----------------------------------------------------------------
    'champs' => [
        'nom' => 'Nom',
        'prenom' => 'Prénom',
        'email' => 'Adresse e-mail',
        'email_court' => 'E-mail',
        'telephone' => 'Téléphone',
        'mobile' => 'Mobile',
        'mot_de_passe' => 'Mot de passe',
        'entreprise' => 'Entreprise',
        'fonction' => 'Fonction',
        'adresse' => 'Adresse',
        'ville' => 'Ville',
        'pays' => 'Pays',
        'site_web' => 'Site web',
        'lien' => 'Lien',
        'localisation' => 'Localisation',
        'message' => 'Message',
        'motif' => 'Motif',
        'date' => 'Date',
        'statut' => 'Statut',
        'montant' => 'Montant',
        'periode' => 'Période',
        'optionnel' => 'optionnel',
        'obligatoire' => 'obligatoire',
        'astensque' => "Les champs marqués d'un astérisque sont obligatoires.",
        'piege' => 'Ne remplissez pas ce champ',
    ],

    // -----------------------------------------------------------------
    // Temps et durées
    // -----------------------------------------------------------------
    'temps' => [
        'jour' => ':compte jour|:compte jours',
        'mois' => ':compte mois|:compte mois',
        'annee' => ':compte an|:compte ans',
        'minute' => ':compte minute|:compte minutes',
        'jours' => 'Jours',
        'minutes' => 'Minutes',
        'aujourdhui' => "Aujourd'hui",
        'hier' => 'Hier',
        'par_mois' => 'Par mois',
        'par_an' => 'Par an',
        'par_semaine' => 'Par semaine',
        'pour_n_mois' => 'Pour :compte mois',
    ],

    // -----------------------------------------------------------------
    // États vides et messages génériques
    // -----------------------------------------------------------------
    'vide' => [
        'rien' => "Rien pour l'instant.",
        'aucun_resultat' => 'Aucun résultat.',
        'aucune_donnee' => 'Aucune donnée sur cette période.',
    ],

    'divers' => [
        'chargement' => 'Chargement…',
        'sur' => 'sur',
        'ou' => 'ou',
        'et' => 'et',
        'total' => 'Total',
    ],
];
