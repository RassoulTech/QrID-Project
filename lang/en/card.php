<?php

/*
|--------------------------------------------------------------------------
| THE CARD — public page, QR code, printed card
|--------------------------------------------------------------------------
| What the VISITOR sees after a scan, the QR code screen, and the PVC card
| order.
|
| THE PUBLIC PAGE FOLLOWS THE VISITOR'S LANGUAGE, never the holder's: it is
| the correspondent who reads it. Its CONTENT — name, job title, company —
| obviously stays exactly as the holder wrote it.
*/

return [

    'publique' => [
        'email' => 'Email',
        'telephone' => 'Phone',
        'site_web' => 'Website',
        'localisation' => 'Location',
        'grille_aria' => 'Get in touch and follow',
        'enregistrer' => 'Save',
        'pied' => 'Card made with',
        'qr_aria' => 'QR code',
        'fermer' => 'Close',
    ],

    'inactive' => [
        'titre_proprietaire' => 'Your card is not online yet',
        'titre_visiteur' => 'Card not active',
        'description' => 'This digital business card is not active.',

        'suspendue_titre' => 'Your card has been suspended',
        'suspendue_texte' => 'That decision comes from our team, and your details are untouched. '
            .'Write to us: we will tell you why, and what to do about it.',
        'suspendue_action' => 'Contact support',

        'abonnement_titre' => 'Your subscription is no longer active',
        'abonnement_texte' => '<strong>Nothing is lost.</strong> Your card, your details and your '
            .'QR code are kept exactly as they were. It becomes visible again the moment you '
            .'activate — and <strong>neither the link nor the QR code changes</strong>, so the '
            .'ones you have already shared will keep leading here.',
        'abonnement_prolonger' => 'Extend my subscription',
        'abonnement_activer' => 'Activate my card',

        'brouillon_titre' => 'Your card is not online yet',
        'brouillon_texte' => '<strong>Nothing is broken, and your QR code is right.</strong> '
            .'It will lead exactly here as soon as the card is active. Just one step left: '
            .'put it online.',
        'brouillon_action' => 'Put my card online',

        'retour' => 'Back to my account',

        'visiteur_titre' => 'This card is not active',
        'visiteur_texte' => 'It does not exist, or its owner has not put it online yet. If you '
            .'have just scanned a QR code, ask the person to activate their card.',
        'visiteur_connexion' => 'It is mine — sign me in',
        'visiteur_creer' => 'Create my digital business card',
    ],

    'qr' => [
        'titre' => 'My QR code',
        'sous' => 'Print it, share it&nbsp;: it opens your card on any phone.',
        'code' => 'Your code',
        'png' => 'Download as PNG',
        'svg' => 'Download as SVG',
        'pdf' => 'Printable card (PDF)',
        'formats' => 'The SVG is vector&nbsp;: that is the format to hand to a printer. '
            .'PNG works everywhere else.',
        'apercu' => 'Card preview',
        'alt' => 'QR code for your profile',
    ],

    'demo' => [
        'titre' => ':nom — example profile',
        'titre_nu' => 'Example profile',
        'description' => 'An example of a digital professional profile. Create yours in three minutes.',
        'bandeau' => 'Demonstration example.',
        'enregistrer' => 'Save contact',
        'appeler' => 'Call',
    ],

    'physique' => [
        'titre' => 'My printed card',
        'kicker' => 'Printed card',
        'entete' => 'Where should we deliver your card&nbsp;?',
        'sous' => 'Your PVC card comes <strong>free</strong> with your subscription. It leaves '
            .'with the next print run, in about <strong>:jours days</strong> once the address '
            .'is confirmed.',

        'expediee_le' => 'Shipped on :date',
        'imprimee_le' => 'Printed on :date',

        'verrouillee' => 'Your card is already in production: the address can no longer be '
            .'changed. If it is wrong, write to us — we will step in before shipping if there '
            .'is still time.',

        'destinataire' => 'Recipient',
        'ville' => 'City',
        'region' => 'Region',

        'nom_destinataire' => 'Recipient name',
        'telephone_destinataire' => 'Recipient phone',
        'telephone_aide' => 'This is the number the courier will call.',
        'adresse_exemple' => 'Cité Keur Gorgui, villa 42',
        'ville_exemple' => 'Dakar',
        'indications' => 'Notes for the courier',
        'indications_exemple' => 'Opposite the pharmacy, green gate',

        'plus_tard' => 'Later',
        'enregistrer' => 'Save the address',
    ],

    'voir_verso' => 'View the back',
    'protocole' => 'Digital identity protocol',

    'actions' => [
        'appeler' => 'Call',
        'localisation' => 'Location',
    ],
];
