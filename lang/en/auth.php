<?php

/*
|--------------------------------------------------------------------------
| AUTHENTICATION — English
|--------------------------------------------------------------------------
| The first five keys are referenced by path (auth.failed) by the framework
| itself. They can neither be renamed nor moved into a sub-section.
|
| Wording matches the French: it says WHAT to do next, never merely what went
| wrong. « These credentials do not match our records » leaves someone
| guessing which of the two fields to fix.
*/

return [

    'failed' => 'Wrong email address or password.',
    'password' => 'This password is incorrect.',
    'throttle' => 'Too many attempts. Please try again in :seconds seconds.',

    'blocked' => 'This account is suspended. Contact support to reactivate it.',
    'not_admin' => 'This page is reserved for administrators.',

    'onglets' => [
        'aria' => 'Sign in or create an account',
        'connexion' => 'Sign in',
        'inscription' => 'Create an account',
    ],

    'google' => [
        'continuer' => 'Continue with Google',
        'inscrire' => 'Sign up with Google',
        'ou' => 'or with your email address',
    ],

    'champs' => [
        'email' => 'Email address',
        'email_exemple' => 'you@example.sn',
        'nom_complet' => 'Full name',
        'nom_exemple' => 'Awa Ndiaye',
        'telephone' => 'Phone',
        'mot_de_passe' => 'Password',
        'nouveau_mot_de_passe' => 'New password',
        'confirmer_mot_de_passe' => 'Confirm password',
        'huit_caracteres' => 'At least 8 characters.',
        'afficher_mot_de_passe' => 'Show password',
        'masquer_mot_de_passe' => 'Hide password',
        'se_souvenir' => 'Remember me',
        'mot_de_passe_aide' => 'At least 8 characters.',
    ],

    'liens' => [
        'retour_connexion' => 'Back to sign in',
        'retour_espace' => 'Back to my account',
        'mot_de_passe_oublie' => 'Forgot your password&nbsp;?',
        'pas_de_compte' => 'No account yet&nbsp;?',
        'deja_inscrit' => 'Already registered&nbsp;?',
        'creer_compte' => 'Create an account',
        'se_connecter' => 'Sign in',
    ],

    'login' => [
        'titre' => 'Sign in',
        'description' => 'Sign in to your :marque account.',
        'bienvenue' => 'Welcome to :marque',
        'accroche' => 'Sign in to get back to your account.',
        'bouton' => 'Sign in',

        'secours' => 'Forgot your password&nbsp;? <a href=":lien">Reset it in a minute</a>. '
            .'No need to create another account&nbsp;: everything you had is still there.',

        'aside_titre' => 'Your professional identity, wherever you are.',
        'aside_texte' => 'One link, one QR code, and your contacts have it all: details, networks, '
            .'introduction. Never again a card left at the office.',
        'visuel_qr' => 'QR code generated',
        'visuel_vues' => 'Views this month',
    ],

    'register' => [
        'titre' => 'Create an account',
        'description' => 'Create your :marque account in under three minutes.',
        'accroche' => 'Fifteen days free, no card required.',
        'bouton' => 'Send me my confirmation link',

        'aside_titre' => 'Protect your professional identity.',
        'aside_texte' => 'Your details stay yours. You choose what shows, you change it whenever '
            .'you like, and you share it in a single gesture.',
        'visuel_titre' => 'Your data belongs to you',
        'visuel_texte' => 'Nothing is published without your say-so, and nothing is ever sold.',
        'visuel_securise' => 'Account secured',
    ],

    'forgot' => [
        'titre' => 'Forgotten password',
        'question' => 'Forgot your password&nbsp;?',
        'accroche' => 'Enter your email address: we will send you a link to set a new one.',
        'bouton' => 'Send the link',

        'aside_titre' => 'We will get you back on track.',
        'aside_texte' => 'A link valid for one hour, sent to your address. Nobody else can use it, '
            .'and your current password keeps working until you choose a new one.',
        'visuel_titre' => 'Good to have you back',
        'visuel_texte' => 'A single link is all it takes to regain control of your account.',
        'visuel_role' => 'Architect · Atelier Teranga',
    ],

    'reset' => [
        'titre' => 'New password',
        'accroche' => 'Choose a new password for your account.',
        'bouton' => 'Reset my password',

        'aside_titre' => 'One password, and off you go.',
        'aside_texte' => 'Pick one only you know. The strength meter tells you where you stand; '
            .'it never demands anything the form does not already accept.',
        'visuel_protege' => 'Account protected',
    ],

    'confirm' => [
        'titre' => 'Confirmation required',
        'accroche' => 'Secure area. Confirm your password to continue.',
        'bouton' => 'Confirm',

        'aside_titre' => 'One more check, one less worry.',
        'aside_texte' => 'Before a sensitive action we ask for your password again. If someone sits '
            .'down at your screen, they will not be able to change a thing.',
        'visuel_zone' => 'Secure area',
    ],

    'pending' => [
        'titre' => 'Check your inbox',

        'accroche' => 'We have just written to :adresse. The message tells you what to do next.',
        'etape_1' => 'Open the message we have just sent you.',
        'etape_2' => 'Follow the link it contains.',
        'etape_3' => 'You land straight in your account.',
        'indesirables' => 'Nothing after two minutes&nbsp;? Look in your spam folder&nbsp;: the '
            .'first message sometimes ends up there.',
        'renvoyer' => 'Send the email again',
        'limite_atteinte' => 'You have reached the resend limit. Use « Start over » or contact support.',
        'renvois_restants' => 'You have :compte resend left.|You have :compte resends left.',
        'deja_compte' => 'Already have an account for this address&nbsp;?',
        'connectez_vous' => 'Sign in',
        'reinitialisez' => 'reset your password',
        'recommencer' => 'Start over',
        'aide' => 'Help',

        'aside_titre' => 'A proven address, a safe account.',
        'aside_texte' => 'We only create the account once you click the link. Nobody can register '
            .'with your address in your place.',
        'visuel_titre' => 'Message sent',
        'visuel_texte' => 'It usually arrives in under a minute.',
    ],

    'expired' => [
        'titre' => 'Link expired',
        'entete' => 'This link has expired',
        'accroche' => 'No account was created. Enter your email address&nbsp;: we start again from '
            .'the beginning, and you get a fresh link.',
        'bouton' => 'Start registration again',

        'aside_titre' => 'A link expires — your place does not.',
        'aside_texte' => 'Confirmation links live for one hour only, so that nobody else can use '
            .'them. Getting a new one takes five seconds.',
        'visuel_titre' => 'Links have a lifespan',
        'visuel_texte' => 'One hour, no more: that is what makes them safe.',
    ],

    'flash' => [
        'deja_confirme' => 'Your account is already confirmed. Please sign in.',
        'lien_perime' => 'This link has already been used, or is no longer valid. Sign in to reach your account.',
        'compte_incomplet' => 'This account is incomplete. Start registration again, or contact support.',
    ],

];
