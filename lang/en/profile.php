<?php

/*
|--------------------------------------------------------------------------
| THE CARD AND THE ACCOUNT — everything the client TYPES IN
|--------------------------------------------------------------------------
| The three creation steps, the profile sheet, the preview before activation,
| and the account settings.
|
| WHAT IS TYPED IN IS NEVER TRANSLATED: name, job title, company, address,
| links. Only the labels around them are.
*/

return [

    'wizard' => [
        'etape_sur' => 'Step :n of :total',
        'progression' => 'Progress: step :n of :total',
        'retour' => 'Back',
        'continuer' => 'Continue',
        'terminer' => 'Finish',

        'titre_1' => 'Create my profile — step 1',
        'entete_1' => 'Who are you?',
        'sous_1' => 'These details appear at the top of your profile.',
        'retour_tableau' => 'Dashboard',
        'fonction_exemple' => 'Sales rep, lawyer, manager…',

        'couverture' => 'Cover image',
        'couverture_actuelle' => 'Current banner',
        'couverture_ajouter' => 'Add an image',
        'couverture_changer' => 'Change the image',
        'couverture_formats' => 'JPG, PNG or WEBP — 2 MB maximum',
        'couverture_aide' => 'Landscape format, ideally 1200 × 800 pixels. It is cropped to fill '
            .'the top of your card, and your name is shown over it. Without an image, your card '
            .'carries the :marque design.',

        'titre_2' => 'Create my profile — step 2',
        'entete_2' => 'How can people reach you?',
        'sous_2' => 'Only the phone number is required. You can fill in the rest later.',

        'localisation' => 'Location link',
        'localisation_aide' => 'Paste the link to your Google Maps listing so people find the exact spot.',
        'email_public' => 'Public email',
        'email_public_aide' => 'Shown on your profile. Different from your sign-in address.',
        'email_public_exemple' => 'contact@example.sn',
        'adresse_exemple' => 'Sacré-Cœur 3, Dakar',

        'reseaux' => 'Social networks',
        'reseau_choisir' => 'Choose…',
        'reseau_aria' => 'Social network :n',
        'reseau_aria_nu' => 'Social network',
        'reseau_lien' => 'Link to your page',
        'reseau_lien_aria' => 'Link for network :n',
        'reseau_lien_aria_nu' => 'Network link',
        'reseau_retirer' => 'Remove this network',
        'reseau_ajouter' => 'Add a network',

        'titre_3' => 'Create my profile — step 3',
        'entete_3' => 'Your style',
        'sous_3' => 'Everything is already chosen. Change it if you like, or finish.',

        'modele' => 'Template',
        'modele_aria' => 'Profile template',
        'carte' => 'Your card',
        'carte_aria' => 'Card variant',

        'apercu_prenom' => 'Your',
        'apercu_nom' => 'name',
        'apercu_fonction' => 'Your job title',
    ],

    'fiche' => [
        'titre' => 'My profile',
        'sous' => 'What your contacts will see.',
        'modifier' => 'Edit my details',

        'identite' => 'Identity',
        'nom_complet' => 'Full name',

        'coordonnees' => 'Contact details',
        'whatsapp' => 'WhatsApp',
        'email_public' => 'Public email',

        'reseaux' => 'Social networks',
        'aucun_reseau' => 'No networks added. You can add up to six from the edit screen.',

        'apparence' => 'Appearance and link',
        'modele' => 'Template',
        'carte' => 'Card',

        'lien_modifiable' => 'Your link can be changed <strong>once, and only once</strong>, from '
            .'the edit screen. After that, cards already printed and QR codes already in '
            .'circulation will stop working&nbsp;: they will point to a page that does not exist.',
        'lien_definitif' => 'Your link has already been changed once&nbsp;: it is now final. '
            .'That is what guarantees cards already printed keep working.',

        'apercu' => 'Preview',
    ],

    'apercu' => [
        'titre' => 'Your card is ready',
        'sous' => 'Take a look before you activate it&nbsp;: nothing is published and nothing is '
            .'charged until you decide.',
        'physique' => 'Your printed card',
        'physique_note' => 'Bank-card format, ready to print.',
        'contacts' => 'What your contacts will see',
        'contacts_note' => 'The page that opens after a scan.',
        'activer' => 'Activate my card',
        'modifier' => 'Edit my details',
    ],

    'compte' => [
        'titre' => 'My account',

        'informations' => 'Account details',
        'informations_sous' => 'Your sign-in credentials.',
        'enregistre' => 'Saved.',

        'mot_de_passe' => 'Password',
        'mot_de_passe_sous' => 'Use a long, unique password.',
        'actuel' => 'Current password',
        'nouveau' => 'New password',

        'sans_mot_de_passe' => 'You sign in with Google, without a password. Setting one here '
            .'gives you a <strong>second way in</strong>: signing in with Google will keep working.',

        'supprimer' => 'Delete account',
        'supprimer_sous' => 'This also deletes your professional profile.',
        'supprimer_avertissement' => 'Once the account is deleted, all of its data is gone for '
            .'good. Before you continue, download anything you want to keep.',
        'supprimer_bouton' => 'Delete my account',
        'supprimer_confirmer' => 'Confirm deletion',
        'supprimer_modale' => 'This cannot be undone. Enter your password to confirm the '
            .'permanent deletion of your account.',
        'supprimer_definitivement' => 'Delete permanently',
    ],
];
