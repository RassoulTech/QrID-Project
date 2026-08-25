<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bandeau des métiers
    |--------------------------------------------------------------------------
    */

    /*
     | DES CLÉS, PAS DES PHRASES.
     |
     | Cette liste portait le texte français, que la vue passait à __(). La
     | phrase française servait donc de clé de traduction — ce que la
     | structure des fichiers de langue interdit : renommer « Médecin » en
     | « Docteur » ferait silencieusement disparaître la traduction anglaise,
     | sans erreur et sans test rouge.
     |
     | Les libellés vivent dans les fichiers de langue, sous landing.metiers.*.
     */
    'trades' => [
        'architecte',
        'consultant',
        'avocat',
        'medecin',
        'ingenieur',
        'freelance',
    ],

    /*
    |--------------------------------------------------------------------------
    | Trois chiffres clés — VALEURS DE CONFIGURATION, PAS DES STATISTIQUES.
    | Ils décrivent la promesse produit : rien n'est mesuré ni inventé.
    |--------------------------------------------------------------------------
    */

    'figures' => [
        // Le CHIFFRE reste ici : c'est une valeur, pas du texte. Le mot et le
        // libellé sont des clés vers landing.chiffres.*.
        ['number' => '3',  'cle' => 'minutes'],
        ['number' => '1',  'cle' => 'lien'],
        ['number' => '15', 'cle' => 'jours'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Comment ça marche
    |--------------------------------------------------------------------------
    */

    // Trois clés vers landing.etapes.* — l'ordre EST celui de l'affichage.
    'steps' => ['profil', 'liens', 'partage'],

    /*
    |--------------------------------------------------------------------------
    | Maquette du téléphone — ILLUSTRATION, PAS UN PROFIL RÉEL.
    |--------------------------------------------------------------------------
    | La landing montre un profil de démonstration pris en base (DemoSeeder).
    | Quand la base n'en contient aucun — production le jour de la mise en
    | ligne, base fraîchement migrée, profils de démo dépubliés — c'est ce
    | contenu qui alimente la maquette.
    |
    | Sans ce repli, l'accueil renvoyait une erreur 500 : la page la plus
    | visitée du site tombait dès que le jeu de démonstration manquait.
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | Marque imprimée au verso des cartes
    |--------------------------------------------------------------------------
    | Le verso est identique sur TOUTES les cartes : c'est la face de la
    | plateforme. Ces valeurs partent chez l'imprimeur en centaines
    | d'exemplaires — elles ne se corrigent pas par un déploiement.
    |
    | BRAND_WEBSITE est distinct d'APP_URL : l'application peut tourner sur
    | app.exemple.sn alors que la carte doit afficher le site vitrine. Sans
    | cette variable, la carte imprimerait l'adresse de développement.
    */

    'brand' => [
        'tagline' => env('BRAND_TAGLINE', 'Votre identité professionnelle, en un scan'),

        /*
         | Repli VOLONTAIREMENT indépendant d'APP_URL.
         |
         | La version précédente dérivait l'adresse de APP_URL : en
         | développement, la carte imprimait « 127.0.0.1 ». Un défaut de ce
         | genre ne se voit qu'une fois les cartes sorties de l'imprimerie,
         | et ne se corrige pas par un déploiement.
         */
        'website' => env('BRAND_WEBSITE', 'qrid.sn'),

        /*
         | Mention imprimée sous le QR Code du verso. Impérative et brève :
         | elle dit à celui qui reçoit la carte ce qu'il gagne à scanner.
         */
        'card_cta' => env('BRAND_CARD_CTA', 'Créez votre carte'),

        /*
         | Provenance ajoutée à l'adresse encodée dans le QR du VERSO.
         |
         | C'est ce paramètre, et lui seul, qui rend les cartes mesurables :
         | sans lui, une inscription venue d'une carte est indiscernable du
         | trafic direct, et personne ne saura jamais si l'idée fonctionne.
         |
         | Le QR est mis en cache sous une empreinte de l'adresse complète :
         | changer cette valeur régénère le fichier, sans purge manuelle.
         */
        'card_source' => env('BRAND_CARD_SOURCE', 'carte'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SUPPORT — le bouton WhatsApp flottant et le formulaire de contact
    |--------------------------------------------------------------------------
    |
    | LE NUMÉRO EST EN CHIFFRES, indicatif compris, sans « + » ni espaces :
    | wa.me n'accepte rien d'autre, et un lien mal formé s'ouvre sur une erreur
    | WhatsApp au lieu d'une conversation. Le composant nettoie ce qu'il reçoit,
    | mais la valeur d'origine gagne à être déjà propre.
    |
    | VIDE = PAS DE BOUTON. Un bouton d'aide qui mène à un numéro inexistant
    | est pire que pas de bouton du tout : quelqu'un qui a déjà un problème en
    | rencontre un second.
    |
    | Au Sénégal, WhatsApp est le canal de support réellement utilisé. Un
    | formulaire de contact seul laisserait la moitié des demandes sans
    | réponse — d'où les deux, et non l'un ou l'autre.
    |
    */

    'support' => [
        'whatsapp' => env('SUPPORT_WHATSAPP', '221773831364'),

        // Destinataire des messages du formulaire de contact. Vide : ils
        // partent vers les administrateurs, comme les autres alertes d'équipe.
        'email' => env('SUPPORT_EMAIL'),
    ],

    'mockup' => [
        'first_name' => 'Awa',
        'last_name' => 'Ndiaye',
        'job_title' => 'Architecte',
        'company' => 'Atelier Teranga',
        'phone' => '+221770000000',
        'public_email' => 'contact@exemple.sn',
        'address' => 'Dakar, Sénégal',
        'primary_color' => '#0B3B2E',
    ],

    /*
    |--------------------------------------------------------------------------
    | Preuve sociale — désactivée tant qu'il n'y a pas de vrais clients.
    | Pour activer : renseigner les valeurs et passer 'enabled' à true.
    |--------------------------------------------------------------------------
    */

    'testimonial' => [
        'enabled' => false,
        'quote' => '',
        'name' => '',
        'role' => '',
    ],

];
