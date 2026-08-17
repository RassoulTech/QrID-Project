<?php

return [

    /*
    |--------------------------------------------------------------------------
    | DÉCLENCHEUR DES TÂCHES PLANIFIÉES
    |--------------------------------------------------------------------------
    |
    | Cinq tâches sont programmées dans routes/console.php et AUCUNE ne
    | s'exécute : un service web ne fait que répondre aux requêtes HTTP, il ne
    | regarde jamais l'heure. Il faut donc quelqu'un pour appeler
    | `schedule:run` chaque minute — ici, un scénario Make.
    |
    | ═══════════════════════════════════════════════════════════════════
    | LE JETON N'EST PAS UNE FORMALITÉ
    | ═══════════════════════════════════════════════════════════════════
    | Cette adresse déclenche des envois d'e-mails et un message Discord. Sans
    | jeton, quiconque la découvre peut la marteler — et sur la minute où le
    | récapitulatif doit partir, produire autant de messages qu'il fait
    | d'appels.
    |
    | VIDE = ROUTE INACTIVE. Elle rend 404, comme si elle n'existait pas.
    | C'est le comportement voulu tant que rien n'est configuré : une route
    | ouverte qu'on croit fermée est pire qu'une route absente.
    |
    | Produire un jeton solide :
    |     php artisan automation:token
    |
    */

    'schedule' => [
        'token' => env('AUTOMATION_SCHEDULE_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | ENVOI DES PROSPECTS VERS MAKE
    |--------------------------------------------------------------------------
    |
    | À chaque compte CONFIRMÉ, l'application appelle ce webhook avec les
    | informations du nouveau client. Le scénario Make décide ensuite : fiche
    | dans un tableur, invitation au groupe WhatsApp, message de bienvenue.
    |
    | ═══════════════════════════════════════════════════════════════════
    | POURQUOI À LA CONFIRMATION, ET NON À LA DEMANDE D'INSCRIPTION
    | ═══════════════════════════════════════════════════════════════════
    | Une demande non confirmée n'est pas un client : c'est une adresse tapée
    | dans un formulaire, parfois par erreur, parfois par quelqu'un d'autre.
    | Transmettre ces adresses à un service tiers avant même que leur
    | propriétaire ait cliqué sur le lien de confirmation serait indéfendable
    | — et le groupe WhatsApp est réservé aux clients.
    |
    | VIDE = AUCUN ENVOI. Rien n'est mis en file, rien n'est perdu : la
    | fonction est simplement absente.
    |
    */

    'make' => [
        'webhook' => env('MAKE_WEBHOOK_URL'),

        // Dix secondes. Make répond en quelques centaines de millisecondes ;
        // au-delà, c'est que quelque chose ne va pas, et cet appel se produit
        // PENDANT la confirmation d'inscription — l'utilisateur attend.
        'timeout' => (int) env('MAKE_TIMEOUT', 10),

        /*
         | Signature partagée, envoyée en en-tête X-QrID-Signature.
         |
         | Elle permet au scénario Make de vérifier que l'appel vient bien de
         | nous. Un webhook Make est une URL publique : sans cette
         | vérification, n'importe qui connaissant l'adresse peut y injecter
         | de faux prospects, qui seraient invités au groupe WhatsApp.
         */
        'secret' => env('MAKE_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | GROUPE WHATSAPP DES CLIENTS
    |--------------------------------------------------------------------------
    |
    | Lien d'invitation au groupe réservé aux clients, pour l'entraide et le
    | support. Il est proposé dans l'e-mail de bienvenue et sur le tableau de
    | bord.
    |
    | ⚠ CE LIEN EST PUBLIC PAR NATURE : quiconque l'obtient peut rejoindre le
    | groupe. Il n'est donc JAMAIS affiché sur la page d'accueil ni sur une
    | page publique — uniquement à un compte connecté et confirmé.
    |
    | Le régénérer depuis WhatsApp (Infos du groupe → Inviter via un lien →
    | Révoquer) invalide l'ancien : à faire si le lien fuite.
    |
    */

    'whatsapp_groupe' => env('SUPPORT_WHATSAPP_GROUP'),

];
