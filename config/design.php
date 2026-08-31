<?php

return [

    /*
    |--------------------------------------------------------------------------
    | LE CLIQUET
    |--------------------------------------------------------------------------
    |
    | Le plafond de valeurs en dur tolérées. `php artisan design:check`
    | échoue dès qu'il est dépassé.
    |
    | IL NE REMONTE JAMAIS. Chaque lot le baisse, et la valeur baissée est
    | inscrite dans le commit. C'est ce qui distingue une règle d'une
    | intention : sans cliquet, la dette revient — et elle est déjà revenue
    | une fois sur ce projet.
    |
    | Relever ce nombre pour faire passer un correctif pressé, c'est
    | supprimer le garde-fou. La bonne réponse à un dépassement est de
    | corriger la valeur, pas le plafond.
    |
    */
    'plafond' => [

        // Couleurs, longueurs et durées littérales hors _tokens.scss.
        'valeurs' => 1366,

        /*
         | `!important` — plafonné, pas interdit.
         |
         | RÉPARTITION MESURÉE, hors commentaires :
         |     _socle.scss        27
         |     _animations.scss    5
         |     _phone.scss         4   (gelé)
         |     _base.scss          4
         |     _theme-dark.scss    3
         |     _admin.scss         1
         |
         | Le décompte diffère de `grep -c !important`, qui rend 55 : grep
         | compte les LIGNES, l'analyseur compte les OCCURRENCES. Une ligne
         | qui en porte deux vaut 1 pour grep et 2 ici. C'est cette seconde
         | mesure qui est juste — c'est chaque occurrence qu'il faut retirer.
         |
         | Les 27 de `_socle.scss` sont un héritage ASSUMÉ : ils neutralisent
         | les utilitaires de Bootstrap (`.text-muted`, `.bg-white`) qui
         | écrivaient des gris fixes, illisibles en thème sombre. Un
         | utilitaire de framework ne se bat pas autrement.
         |
         | Les 17 autres disparaissent au Lot 2 : un `!important` qui ne
         | sert qu'à battre Bootstrap devient inutile dès que le socle gagne
         | par l'ordre de la cascade. Le plafond descendra alors à 27.
         */
        'important' => 44,

        /*
         | `@media (max-width: …)` — la loi 3 les interdit.
         |
         | 18 subsistent, dont 3 dans des feuilles gelées (`_phone.scss`,
         | `_carte-publique-matiere.scss`). Les 15 autres partent au Lot 2.
         |
         | Les bornes `575.98px` et `767.98px` sont celles de Bootstrap :
         | elles ne figurent pas dans `$ruptures`, et c'est le signe le
         | plus net qu'on a suivi un autre système que le nôtre.
         */
        'max-width' => 18,

        // Styles en ligne hors e-mails et PDF, où ils sont imposés.
        'style-en-ligne' => 98,

        // Liens morts : un `href="#"` a l'air cliquable et ne mène nulle part.
        // 1 aujourd'hui : la fermeture de la surcouche QR de la page
        // publique, un motif `:target` qui fonctionne sans JavaScript.
        // L'intention est bonne, la forme est à reprendre — Lot 4.
        'lien-mort' => 1,

        // Soulignement : la loi 8 fait porter la distinction par la couleur.
        // 9 aujourd'hui, tous dans les feuilles historiques. Ils
        // disparaissent au Lot 2, où la distinction repassera par la
        // couleur.
        'souligne' => 9,
    ],

    /*
    |--------------------------------------------------------------------------
    | BUDGET CSS
    |--------------------------------------------------------------------------
    |
    | La taille du bundle compilé, en octets. Un lot qui l'augmente de plus
    | de la tolérance s'explique dans son commit.
    |
    | Le trafic public arrive par scan de QR Code, souvent en 3G : chaque
    | kilo-octet se paie en secondes devant un écran blanc.
    |
    */
    'css' => [
        'reference' => 364551,
        'tolerance' => 0.05,   // 5 %
    ],

];
