<?php

return [

    /*
    |--------------------------------------------------------------------------
    | COMPTES VITRINE — les cartes qui ne s'éteignent jamais
    |--------------------------------------------------------------------------
    |
    | Adresses e-mail, séparées par des virgules, dont la carte doit rester
    | EN LIGNE en permanence. À chaque démarrage de l'application, leur
    | abonnement est reporté d'un an et leur carte publiée.
    |
    |     DEMO_ACCOUNT_EMAILS=vous@exemple.sn,demo@exemple.sn
    |
    | POURQUOI : une carte de démonstration expire comme celle de n'importe
    | quel client. Sans passerelle de paiement en production, elle ne peut
    | alors être rallumée qu'à la main — et l'on découvre qu'elle est éteinte
    | devant le prospect, jamais avant.
    |
    | CE QUE CE N'EST PAS : une porte dérobée. Seules les adresses écrites
    | ici sont touchées, exactement comme ADMIN_EMAIL désigne les
    | administrateurs. Aucun compte n'est créé, aucun paiement n'est inventé :
    | l'abonnement est posé sur la formule GRATUITE, donc le chiffre
    | d'affaires ne bouge pas et les statistiques comptent ces comptes pour ce
    | qu'ils sont.
    |
    | Vide par défaut : sans déclaration, rien ne se produit.
    |
    */

    'emails' => env('DEMO_ACCOUNT_EMAILS', ''),

];
