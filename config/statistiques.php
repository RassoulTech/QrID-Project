<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Rétention des événements bruts
    |--------------------------------------------------------------------------
    |
    | Douze mois par défaut : c'est la durée sur laquelle un client compare
    | une année à la précédente. Au-delà, les agrégats journaliers suffisent —
    | ils pèsent mille fois moins et répondent aux mêmes questions.
    |
    | Mesuré sur cette base : un événement brut occupe 169 octets, index
    | compris. Un million d'événements pèse donc 161 Mo, et cinq millions
    | dépassent 800 Mo — au-delà de ce que le plan de base absorbe sans
    | douleur.
    |
    | La purge ne s'exécute QUE derrière l'agrégation : voir
    | app:agreger-statistiques --purger.
    |
    */

    'retention_mois' => (int) env('STATS_RETENTION_MOIS', 12),

    /*
    |--------------------------------------------------------------------------
    | Seuil de journalisation des requêtes lentes
    |--------------------------------------------------------------------------
    |
    | Toute requête SQL dépassant ce seuil est journalisée avec sa durée et
    | l'adresse qui l'a déclenchée. 300 ms est le budget d'une page : une
    | seule requête qui le consomme entièrement est déjà une anomalie.
    |
    | Mettre à 0 désactive la surveillance.
    |
    */

    'seuil_requete_lente_ms' => (int) env('SEUIL_REQUETE_LENTE_MS', 300),

];
