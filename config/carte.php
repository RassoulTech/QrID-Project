<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Texture du corps de la carte publique
    |--------------------------------------------------------------------------
    |
    | Trois partis pris, visibles côte à côte sur /design-system :
    |
    |   a  trame de modules, l'écho du QR Code
    |   b  réseau de points reliés, le graphe professionnel
    |   c  formes organiques floutées, la lumière diffuse
    |
    | UNE VARIABLE PLUTÔT QU'UN CHOIX ÉCRIT EN DUR : basculer d'un parti pris
    | à l'autre ne doit pas demander un déploiement, seulement un réglage
    | d'environnement. C'est ce qui permet de trancher sur pièces plutôt que
    | sur description.
    |
    */

    'texture' => env('CARTE_TEXTURE', 'a'),

];
