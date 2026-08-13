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

];
