<?php

/*
|--------------------------------------------------------------------------
| AUTHENTIFICATION
|--------------------------------------------------------------------------
| ATTENTION : les cinq premières clés sont référencées par leur chemin
| (auth.failed) depuis le framework lui-même. Elles ne peuvent ni changer de
| nom, ni se déplacer dans une sous-section.
|
| Tout ce qui suit appartient aux sept écrans d'authentification.
*/

return [

    // =================================================================
    // MESSAGES DU FRAMEWORK — noms imposés
    // =================================================================

    /*
    | AFFIRME LE MOINS POSSIBLE, ET RIEN DE FAUX.
    |
    | La formulation précédente — « Ces identifiants ne correspondent à aucun
    | compte » — était un vrai piège. Ce message s'affiche AUSSI quand le compte
    | existe et que seul le mot de passe est erroné : il annonçait alors une
    | chose fausse, et poussait l'utilisateur à supprimer puis recréer un compte
    | parfaitement valide. C'est exactement ce qui s'est produit, plusieurs fois.
    |
    | L'original anglais de Laravel est volontairement vague (« do not match our
    | records ») : c'est ce flou qui empêche d'énumérer les comptes. La
    | traduction avait transformé ce flou en une affirmation précise et fausse.
    |
    | On dit donc ce qui est vrai dans les deux cas, sans révéler lequel.
    */
    'failed' => 'E-mail ou mot de passe incorrect.',
    'password' => 'Ce mot de passe est incorrect.',
    'throttle' => 'Trop de tentatives. Réessayez dans :seconds secondes.',

    // Propres à ce produit.
    'blocked' => 'Ce compte est suspendu. Contactez le support pour le réactiver.',
    'not_admin' => 'Cette page est réservée à l\'administration.',

    // =================================================================
    // ÉLÉMENTS COMMUNS AUX ÉCRANS
    // =================================================================

    'onglets' => [
        'aria' => 'Connexion ou création de compte',
        'connexion' => 'Connexion',
        'inscription' => 'Créer un compte',
    ],

    'google' => [
        'continuer' => 'Continuer avec Google',
        'inscrire' => 'S\'inscrire avec Google',
        'ou' => 'ou avec votre adresse e-mail',
    ],

    'champs' => [
        'email' => 'Adresse e-mail',
        'email_exemple' => 'vous@exemple.sn',
        'nom_complet' => 'Nom complet',
        'nom_exemple' => 'Awa Ndiaye',
        'telephone' => 'Téléphone',
        'mot_de_passe' => 'Mot de passe',
        'nouveau_mot_de_passe' => 'Nouveau mot de passe',
        'confirmer_mot_de_passe' => 'Confirmer le mot de passe',
        'huit_caracteres' => 'Au moins 8 caractères.',
        'afficher_mot_de_passe' => 'Afficher le mot de passe',
        'masquer_mot_de_passe' => 'Masquer le mot de passe',
        'se_souvenir' => 'Se souvenir de moi',
    ],

    'liens' => [
        'retour_connexion' => 'Retour à la connexion',
        'retour_espace' => 'Retour à mon espace',
        'mot_de_passe_oublie' => 'Mot de passe oublié&nbsp;?',
        'pas_de_compte' => 'Pas encore de compte&nbsp;?',
        'deja_inscrit' => 'Déjà inscrit&nbsp;?',
        'creer_compte' => 'Créer un compte',
        'se_connecter' => 'Se connecter',
    ],

    // =================================================================
    // CONNEXION
    // =================================================================
    'login' => [
        'titre' => 'Connexion',
        'description' => 'Connectez-vous à votre espace :marque.',
        'bienvenue' => 'Bienvenue sur :marque',
        'accroche' => 'Connectez-vous pour retrouver votre espace.',
        'bouton' => 'Se connecter',

        /*
         | SORTIE DE SECOURS, affichée dès le premier échec.
         |
         | Sans elle, l'utilisateur bloqué n'avait sous les yeux qu'un message
         | d'erreur et un bouton « Créer un compte ». Il recréait donc un
         | compte qu'il possédait déjà.
         |
         | Aucune fuite : ce bloc apparaît à CHAQUE échec, que le compte
         | existe ou non. Il ne dit rien de l'état de l'adresse.
         */
        'secours' => 'Mot de passe oublié&nbsp;? <a href=":lien">Réinitialisez-le en une minute</a>. '
            .'Inutile de recréer un compte&nbsp;: vos informations sont conservées.',

        'aside_titre' => 'Votre identité pro, partout avec vous.',
        'aside_texte' => 'Un lien, un QR code, et vos contacts ont tout : coordonnées, réseaux, '
            .'présentation. Plus jamais de carte oubliée au bureau.',
        'visuel_qr' => 'QR Code généré',
        'visuel_vues' => 'Vues du mois',
    ],

    // =================================================================
    // INSCRIPTION
    // =================================================================
    'register' => [
        'titre' => 'Créer un compte',
        'description' => 'Créez votre compte :marque en moins de trois minutes.',
        'accroche' => 'Quinze jours d\'essai gratuit, sans carte bancaire.',
        'bouton' => 'Recevoir mon lien de confirmation',

        'aside_titre' => 'Protégez votre identité professionnelle.',
        'aside_texte' => 'Vos coordonnées restent à vous. Vous choisissez ce qui s\'affiche, '
            .'vous le modifiez quand vous voulez, et vous le partagez d\'un seul geste.',
        'visuel_titre' => 'Vos données vous appartiennent',
        'visuel_texte' => 'Rien n\'est publié sans votre accord, rien n\'est revendu.',
        'visuel_securise' => 'Compte sécurisé',
    ],

    // =================================================================
    // MOT DE PASSE OUBLIÉ
    // =================================================================
    'forgot' => [
        'titre' => 'Mot de passe oublié',
        'question' => 'Mot de passe oublié&nbsp;?',
        'accroche' => 'Indiquez votre adresse e-mail : nous vous envoyons un lien pour en définir un nouveau.',
        'bouton' => 'Recevoir le lien',

        'aside_titre' => 'Nous vous remettons en selle.',
        'aside_texte' => 'Un lien valable une heure, envoyé à votre adresse. Personne d\'autre ne peut '
            .'l\'utiliser, et votre mot de passe actuel reste valable jusqu\'à ce que vous en '
            .'choisissiez un nouveau.',
        'visuel_titre' => 'Bon retour parmi nous',
        'visuel_texte' => 'Un seul lien suffit à reprendre la main sur votre compte.',
        'visuel_role' => 'Architecte · Atelier Teranga',
    ],

    // =================================================================
    // NOUVEAU MOT DE PASSE
    // =================================================================
    'reset' => [
        'titre' => 'Nouveau mot de passe',
        'accroche' => 'Choisissez un nouveau mot de passe pour votre compte.',
        'bouton' => 'Réinitialiser mon mot de passe',

        'aside_titre' => 'Un mot de passe, et vous repartez.',
        'aside_texte' => 'Choisissez-en un que vous seul connaissez. L\'indicateur vous dit où vous '
            .'en êtes ; il ne vous impose rien que le formulaire n\'accepte déjà.',
        'visuel_protege' => 'Compte protégé',
    ],

    // =================================================================
    // CONFIRMATION DE MOT DE PASSE — zone sécurisée
    // =================================================================
    'confirm' => [
        'titre' => 'Confirmation requise',
        'accroche' => 'Zone sécurisée. Confirmez votre mot de passe pour continuer.',
        'bouton' => 'Confirmer',

        'aside_titre' => 'Une vérification de plus, une inquiétude de moins.',
        'aside_texte' => 'Avant une action sensible, nous redemandons votre mot de passe. Si '
            .'quelqu\'un s\'assied devant votre écran, il ne pourra rien changer.',
        'visuel_zone' => 'Zone sécurisée',
    ],

    // =================================================================
    // ATTENTE DE CONFIRMATION D'ADRESSE
    // =================================================================
    'pending' => [
        'titre' => 'Vérifiez votre boîte mail',

        /*
         | FORMULATION VALABLE DANS LES TROIS CAS, sans en révéler aucun.
         |
         | L'écran est identique que l'adresse soit inconnue, déjà inscrite, ou
         | déjà en attente. Mais il ne doit rien AFFIRMER qui soit faux dans
         | l'un des trois : la version précédente annonçait « un lien de
         | confirmation vient d'être envoyé ». Pour une adresse déjà inscrite,
         | l'e-mail reçu est « Vous avez déjà un compte » et ne contient aucun
         | lien de ce genre.
         */
        'accroche' => 'Nous venons d\'écrire à :adresse. Le message vous indique la marche à suivre.',
        'etape_1' => 'Ouvrez le message que nous venons de vous envoyer.',
        'etape_2' => 'Suivez le lien qu\'il contient.',
        'etape_3' => 'Vous arrivez directement dans votre espace.',
        'indesirables' => 'Rien reçu au bout de deux minutes&nbsp;? Regardez dans vos courriers '
            .'indésirables&nbsp;: le message y arrive parfois au premier envoi.',
        'renvoyer' => 'Renvoyer l\'e-mail',
        'limite_atteinte' => 'Limite de renvois atteinte. Utilisez « Recommencer » ou contactez l\'aide.',
        'renvois_restants' => 'Il vous reste :compte renvoi.|Il vous reste :compte renvois.',
        'deja_compte' => 'Vous avez déjà un compte pour cette adresse&nbsp;?',
        'connectez_vous' => 'Connectez-vous',
        'reinitialisez' => 'réinitialisez votre mot de passe',
        'recommencer' => 'Recommencer',
        'aide' => 'Aide',

        'aside_titre' => 'Une adresse prouvée, un compte sûr.',
        'aside_texte' => 'Nous ne créons le compte qu\'après votre clic sur le lien. Personne ne peut '
            .'s\'inscrire avec votre adresse à votre place.',
        'visuel_titre' => 'Message envoyé',
        'visuel_texte' => 'Il arrive en général en moins d\'une minute.',
    ],

    // =================================================================
    // LIEN EXPIRÉ
    // =================================================================
    'expired' => [
        'titre' => 'Lien expiré',
        'entete' => 'Ce lien a expiré',
        'accroche' => 'Aucun compte n\'a été créé. Saisissez votre adresse e-mail&nbsp;: nous '
            .'repartons du début, et vous recevez un nouveau lien.',
        'bouton' => 'Relancer l\'inscription',

        'aside_titre' => 'Un lien expire, pas votre place.',
        'aside_texte' => 'Les liens de confirmation ne vivent qu\'une heure, pour que personne '
            .'d\'autre ne puisse s\'en servir. En relancer un prend cinq secondes.',
        'visuel_titre' => 'Les liens ont une durée de vie',
        'visuel_texte' => 'Une heure, pas plus : c\'est ce qui les rend sûrs.',
    ],
];
