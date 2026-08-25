<?php

/*
|--------------------------------------------------------------------------
| PAIEMENT
|--------------------------------------------------------------------------
| L'écran des formules, le panneau de confiance, la confirmation, et l'écran
| de simulation réservé au développement.
|
| LES MONTANTS RESTENT EN FCFA DANS LES DEUX LANGUES. Ce n'est pas un oubli :
| convertir afficherait un prix qu'on ne peut pas encaisser, et le client paie
| bien en francs CFA quelle que soit la langue de son navigateur.
*/

return [

    'titre' => 'Paiement',
    'kicker' => 'Abonnement',

    'entete' => [
        'choisir' => 'Choisir ma formule',
        'renouveler' => 'Renouveler mon abonnement',
        'sous_choisir' => 'Votre carte est prête. Choisissez la formule qui vous convient '
            .'pour la mettre en ligne.',
        // « s'ajoute à ce qui reste dû » : le client craint de perdre les jours
        // qu'il a déjà payés. C'est la seule phrase qui l'en empêche.
        'sous_renouveler' => 'Votre abonnement court jusqu\'au :date. Un renouvellement '
            .'s\'ajoute à ce qui reste dû, il ne le remplace pas.',
    ],

    'formule' => [
        'legende' => 'Votre formule',
        'economie' => 'Économisez :pourcent %',
        'duree' => ':periodicite · :jours jours',
        'par_mois' => 'soit :montant FCFA par mois',
    ],

    'moyen' => [
        'legende' => 'Comment payer',
    ],

    // -----------------------------------------------------------------
    // Panneau de confiance — passerelle branchée
    // -----------------------------------------------------------------
    'confiance' => [
        'titre' => 'Avant de payer',
        'debit_titre' => 'Aucun débit immédiat.',
        'debit_texte' => 'La somme n\'est prélevée qu\'après votre confirmation chez l\'opérateur.',
        'lien_titre' => 'Votre lien et votre QR Code ne changent jamais.',
        'lien_texte' => 'Les cartes déjà distribuées continueront de fonctionner.',
        'ligne_titre' => 'En ligne aussitôt.',
        'ligne_texte' => 'Votre carte devient publique dès le paiement confirmé.',
        'payer' => 'Payer et publier ma carte',
        'renouveler' => 'Renouveler mon abonnement',
        'plus_tard' => 'Plus tard',
    ],

    // -----------------------------------------------------------------
    // Panneau de confiance — AUCUNE passerelle
    // -----------------------------------------------------------------
    /*
     | Ce panneau remplace le bouton « Payer », qui menait à une page 500
     | disant « le problème vient de nous ». C'était faux : rien n'est en
     | panne, l'encaissement en ligne n'est simplement pas encore ouvert.
     */
    'manuel' => [
        'titre' => 'Paiement à la main, pour l\'instant',
        'ferme_titre' => 'Le paiement en ligne n\'est pas encore ouvert.',
        'ferme_texte' => 'Écrivez-nous sur WhatsApp en indiquant la formule choisie : '
            .'nous activons votre carte à la main, dès réception.',
        'aucun_debit_titre' => 'Rien ne vous est débité ici.',
        'aucun_debit_texte' => 'Aucun montant ne transite par cette page.',
        'whatsapp' => 'Écrire sur WhatsApp',
        // L'exploitant qui se heurte lui-même à cet écran a déjà le pouvoir de
        // le débloquer. Il lui manquait le chemin.
        'admin' => 'Vous êtes administrateur — prolonger cet abonnement',
        'retour' => 'Retour à mon espace',
    ],

    // -----------------------------------------------------------------
    // Confirmation
    // -----------------------------------------------------------------
    'confirmation' => [
        'titre' => 'Votre carte est en ligne',
        'sous_avec_date' => 'Paiement confirmé. Vos contacts peuvent désormais ouvrir votre '
            .'carte — jusqu\'au :date.',
        'sous_sans_date' => 'Paiement confirmé. Vos contacts peuvent désormais ouvrir votre '
            .'carte dès maintenant.',
        'lien' => 'Votre lien public',
        'imprimable' => 'Carte imprimable (PDF)',
        'tableau' => 'Aller à mon tableau de bord',
    ],

    // -----------------------------------------------------------------
    // Simulation — développement uniquement
    // -----------------------------------------------------------------
    'simulation' => [
        'titre' => 'Simulation de paiement',
        'kicker' => 'Environnement de développement',
        'sous' => 'Aucune somme réelle n\'est en jeu. Cet écran remplace celui de l\'opérateur '
            .'tant qu\'aucun contrat n\'est signé.',
        'reference' => 'référence :ref',
        'confirmer' => 'Confirmer le paiement',
        'refus' => 'Simuler un refus',
        'annuler' => 'Annuler et revenir',
    ],
];
