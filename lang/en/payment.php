<?php

/*
|--------------------------------------------------------------------------
| PAYMENT
|--------------------------------------------------------------------------
| The plan screen, the trust panel, the confirmation, and the simulation
| screen reserved for development.
|
| AMOUNTS STAY IN FCFA IN BOTH LANGUAGES. That is not an oversight:
| converting would show a price we cannot charge, and the client pays in CFA
| francs whatever their browser language.
*/

return [

    'titre' => 'Payment',
    'kicker' => 'Subscription',

    'entete' => [
        'choisir' => 'Choose my plan',
        'renouveler' => 'Renew my subscription',
        'sous_choisir' => 'Your card is ready. Choose the plan that suits you to put it online.',
        'sous_renouveler' => 'Your subscription runs until :date. A renewal is ADDED to the time '
            .'you have left; it does not replace it.',
    ],

    'formule' => [
        'legende' => 'Your plan',
        'economie' => 'Save :pourcent %',
        'duree' => ':periodicite · :jours days',
        'par_mois' => 'that is :montant FCFA per month',
    ],

    'moyen' => [
        'legende' => 'How to pay',
    ],

    'confiance' => [
        'titre' => 'Before you pay',
        'debit_titre' => 'Nothing is charged yet.',
        'debit_texte' => 'The amount is only taken after you confirm with your operator.',
        'lien_titre' => 'Your link and your QR code never change.',
        'lien_texte' => 'Cards already handed out will keep working.',
        'ligne_titre' => 'Online right away.',
        'ligne_texte' => 'Your card goes public as soon as the payment is confirmed.',
        'payer' => 'Pay and publish my card',
        'renouveler' => 'Renew my subscription',
        'plus_tard' => 'Later',
    ],

    'manuel' => [
        'titre' => 'Payment by hand, for now',
        'ferme_titre' => 'Online payment is not open yet.',
        'ferme_texte' => 'Message us on WhatsApp telling us which plan you chose: we activate '
            .'your card by hand, as soon as it arrives.',
        'aucun_debit_titre' => 'Nothing is charged here.',
        'aucun_debit_texte' => 'No money passes through this page.',
        'whatsapp' => 'Message us on WhatsApp',
        'admin' => 'You are an administrator — extend this subscription',
        'retour' => 'Back to my account',
    ],

    'confirmation' => [
        'titre' => 'Your card is online',
        'sous_avec_date' => 'Payment confirmed. Your contacts can now open your card — '
            .'until :date.',
        'sous_sans_date' => 'Payment confirmed. Your contacts can open your card right now.',
        'lien' => 'Your public link',
        'imprimable' => 'Printable card (PDF)',
        'tableau' => 'Go to my dashboard',
    ],

    'simulation' => [
        'titre' => 'Payment simulation',
        'kicker' => 'Development environment',
        'sous' => 'No real money is involved. This screen stands in for the operator\'s own, '
            .'until a contract is signed.',
        'reference' => 'reference :ref',
        'confirmer' => 'Confirm the payment',
        'refus' => 'Simulate a refusal',
        'annuler' => 'Cancel and go back',
    ],
];
