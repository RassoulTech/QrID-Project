<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Algorithme de hachage
    |--------------------------------------------------------------------------
    */

    'driver' => env('HASH_DRIVER', 'bcrypt'),

    /*
    |--------------------------------------------------------------------------
    | Coût bcrypt
    |--------------------------------------------------------------------------
    | SANS CE FICHIER, la variable BCRYPT_ROUNDS du .env n'était jamais lue :
    | Laravel retombait sur son défaut interne de 12 rounds. Or chaque
    | incrément DOUBLE le temps de calcul — 12 rounds consommait à lui seul
    | l'essentiel d'un budget de réponse de 300 ms.
    |
    | 10 en développement, 12 en production où la marge existe. Les hachés
    | déjà en base restent vérifiables : bcrypt inscrit son coût dans le haché.
    */

    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 12),
        'verify' => true,
        'limit' => null,
    ],

    'argon' => [
        'memory' => env('ARGON_MEMORY', 65536),
        'threads' => env('ARGON_THREADS', 1),
        'time' => env('ARGON_TIME', 4),
        'verify' => true,
    ],

    'rehash_on_login' => true,

];
