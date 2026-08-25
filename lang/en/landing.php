<?php

/*
|--------------------------------------------------------------------------
| THE PUBLIC HOME PAGE
|--------------------------------------------------------------------------
| Eight sections, in reading order: hero, professions, figures, steps, demo,
| pricing, contact, final call to action.
|
| Plan names and features are NOT here: they come from the `plans` table and
| are editable from the admin area. The billing PERIOD is computed by the
| model, not typed in, and lives in subscription.php.
*/

return [

    'meta' => [
        'titre' => ':marque | Your Professional Digital Identity',
        'description' => 'The secure digital identity platform for Senegal\'s professional elite. '
            .'Bring your expertise together and stand out with elegance.',
    ],

    'hero' => [
        'titre' => 'Your professional identity, <span class="hero-mark">reinvented</span>',
        'accroche' => 'The secure digital identity platform for Senegal\'s professional elite. '
            .'Bring your expertise together and stand out with elegance.',
        'cta' => 'Start the free trial',
        'exemple' => 'See an example',
        'qr_genere' => 'QR code generated',
        'vues_totales' => 'Total views',
        'contact_enregistre' => 'Contact saved',
    ],

    'metiers' => [
        'aria' => 'Professions served',
        'architecte' => 'Architect',
        'consultant' => 'Consultant',
        'avocat' => 'Lawyer',
        'medecin' => 'Doctor',
        'ingenieur' => 'Engineer',
        'freelance' => 'Freelancer',
    ],

    'chiffres' => [
        'minutes_mot' => 'Minutes',
        'minutes_libelle' => 'To get set up',
        'lien_mot' => 'Link',
        'lien_libelle' => 'To share everything',
        'jours_mot' => 'Days',
        'jours_libelle' => 'Of free trial',
    ],

    'etapes' => [
        'titre' => 'How it works',
        'profil_titre' => 'Create your profile',
        'profil_texte' => 'Fill in your professional details in a few clicks, through an interface '
            .'that stays out of your way.',
        'liens_titre' => 'Set up your links',
        'liens_texte' => 'Add your social networks, your portfolio and the ways you prefer to be reached.',
        'partage_titre' => 'Share without limits',
        'partage_texte' => 'A single link, or an elegant QR code, for every professional encounter.',
    ],

    'demo' => [
        'titre' => 'See your finished profile before you pay.',
        'texte' => 'See exactly how your digital identity will look, right away. No hidden fees, '
            .'no commitment up front. Try :marque for free.',
        'scanner' => 'Scan &amp; View',
        'professionnels' => 'More than 500 professionals',
    ],

    'tarifs' => [
        'titre' => 'Simple, transparent pricing',
        'sous_titre' => 'Pay with Wave, Orange Money or Free Money.',
        'best_value' => 'Best value',
        'essayer' => 'Try it free',
        'abonner' => 'Subscribe',
    ],

    'final' => [
        'titre' => 'Ready to stand out in Senegal&nbsp;?',
        'texte' => 'Join the growing community of leaders who trust :marque to carry their message.',
        'cta' => 'Start my journey',
    ],

    'contact' => [
        'titre' => 'A question&nbsp;? Write to us.',
        'sous_titre' => 'A question about the service, an order for printed cards, or simply a '
            .'doubt&nbsp;: we reply within 24 working hours.',
        'whatsapp_titre' => 'Faster answer&nbsp;: WhatsApp',
        'whatsapp_texte' => 'The quickest channel, Monday to Saturday.',
        'recu_titre' => 'Message received.',
        'recu_texte' => 'We will reply to the address you gave, within 24 working hours.',
        'votre_nom' => 'Your name',
        'nom_exemple' => 'Awa Ndiaye',
        'votre_message' => 'Your message',
        'message_exemple' => 'Tell us in a few lines what you need.',
        'envoyer' => 'Send my message',
        'legal' => 'Your details are used only to reply to you. They are never sold, and never '
            .'used for cold outreach.',

        'motifs' => [
            'information' => 'A question about the service',
            'commande' => 'Order printed cards',
            'assistance' => 'I need help with my account',
            'partenariat' => 'Partnership or reselling',
        ],

        'validation' => [
            'nom_requis' => 'Please give your name, so we know who to reply to.',
            'nom_court' => 'That name looks too short.',
            'email_requis' => 'Without an email address we will not be able to reply.',
            'email_invalide' => 'That email address does not look valid.',
            'motif_requis' => 'Please choose a subject.',
            'motif_inconnu' => 'That subject is not on the list.',
            'message_requis' => 'Please write your message.',
            'message_court' => 'Tell us a little more: at least twenty characters.',
            'message_long' => 'Your message is too long. Sum up the essentials and we will call you back.',
            'piege' => 'Your message could not be sent.',
        ],
    ],

    'aide' => [
        'question' => 'A question&nbsp;?',
        'ecrire' => 'Message us on WhatsApp',
    ],
];
