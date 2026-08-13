<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Destinataires des alertes d'équipe
    |--------------------------------------------------------------------------
    |
    | DEUX SOURCES, DANS CET ORDRE DE PRIORITÉ.
    |
    | 1. ADMIN_ALERT_RECIPIENTS — liste explicite, séparée par des virgules.
    |    Elle a le dernier mot quand elle est renseignée.
    |
    | 2. À défaut : tous les comptes de rôle « admin » présents en base.
    |
    | Le repli automatique n'est pas une commodité, c'est une garantie. Une
    | liste figée dans une variable d'environnement se périme au premier
    | changement d'équipe, et le jour où quelqu'un part, ses alertes partent
    | avec lui sans que personne ne s'en aperçoive. En lisant la base, ajouter
    | un administrateur suffit à l'abonner.
    |
    | Inversement, la liste explicite reste utile pour viser une boîte
    | partagée — support@ — plutôt que trois boîtes personnelles.
    |
    */

    'admin_recipients' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('ADMIN_ALERT_RECIPIENTS', ''))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Ce qui déclenche une alerte d'équipe
    |--------------------------------------------------------------------------
    |
    | Interrupteur par motif. Les deux motifs d'ÉCHEC ne sont pas listés : ils
    | ne sont pas désactivables. Un paiement en échec ou un traitement qui
    | tombe doivent se voir, toujours — c'est précisément ce qu'on n'a pas
    | envie de lire, donc précisément ce qu'on finirait par éteindre.
    |
    | Les trois autres sont du confort. Sur un produit qui marche, « nouveau
    | client inscrit » finit par arriver vingt fois par jour ; il faut alors
    | pouvoir le couper sans toucher au code.
    |
    */

    'admin_alerts' => [
        'compte_confirme' => (bool) env('ALERT_ON_SIGNUP', true),
        'profil_cree' => (bool) env('ALERT_ON_PROFILE', true),
        'carte_activee' => (bool) env('ALERT_ON_PUBLISH', true),
        'paiement_reussi' => (bool) env('ALERT_ON_PAYMENT', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | RÉCAPITULATIF QUOTIDIEN DISCORD
    |--------------------------------------------------------------------------
    |
    | L'URL DU WEBHOOK EST UN SECRET : quiconque la possède peut écrire dans le
    | salon. Elle ne doit jamais être versionnée, ni apparaître dans un
    | journal. DiscordNotifier ne consigne qu'un identifiant de salon tronqué.
    |
    | Discord → Paramètres du salon → Intégrations → Webhooks → Nouveau webhook.
    |
    | VIDE = AUCUN ENVOI. La commande s'exécute quand même et le dit dans son
    | journal : une configuration manquante ne doit pas ressembler à une
    | journée sans activité.
    |
    | L'HEURE EST CELLE DE DAKAR, et le fuseau est explicite. Le serveur tourne
    | en UTC : sans cette précision, le récapitulatif du soir partirait à 21 h
    | UTC, soit 21 h à Dakar en apparence — jusqu'au jour où l'hébergeur change
    | de région et où tout le monde cherche pourquoi le message arrive à 23 h.
    |
    */

    'discord' => [
        'webhook' => env('DISCORD_WEBHOOK_URL'),

        // Dix secondes. Discord répond en quelques centaines de millisecondes ;
        // au-delà, c'est que quelque chose ne va pas et il vaut mieux le dire
        // vite que de retenir une commande planifiée.
        'timeout' => (int) env('DISCORD_TIMEOUT', 10),

        'heure' => env('DISCORD_REPORT_HOUR', '21:00'),
        'fuseau' => env('DISCORD_REPORT_TZ', 'Africa/Dakar'),
    ],

];
