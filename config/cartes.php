<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PRODUCTION DES CARTES PHYSIQUES
    |--------------------------------------------------------------------------
    |
    | SEUIL DE LOT — on n'envoie pas une carte à l'imprimeur, on envoie un lot.
    | Le coût unitaire d'une commande de vingt cartes est sans commune mesure
    | avec celui de vingt commandes d'une carte. Passé ce seuil, l'exploitant
    | est alerté par e-mail et dans le récapitulatif du soir.
    |
    | DÉLAI ANNONCÉ — il est écrit à l'écran de paiement et dans l'e-mail. Une
    | promesse tenue à trois semaines vaut mieux qu'une promesse manquée à une
    | semaine : c'est le silence, pas l'attente, qui fait écrire un client.
    |
    | COÛT UNITAIRE — sert au suivi de marge en administration. À ajuster selon
    | le devis réel de l'imprimeur, transport compris.
    |
    */

    'seuil_lot' => (int) env('CARTES_SEUIL_LOT', 20),

    'delai_jours' => (int) env('CARTES_DELAI_JOURS', 21),

    'cout_unitaire_fcfa' => (int) env('CARTES_COUT_FCFA', 1200),

];
