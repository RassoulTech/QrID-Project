<?php

return [

    /*
    |--------------------------------------------------------------------------
    | IDENTITÉ LÉGALE DE L'ÉDITEUR
    |--------------------------------------------------------------------------
    |
    | Source : avis d'immatriculation ANSD du 10/07/2026 et accusé
    | d'enregistrement RCCM du 09/07/2026, tribunal de grande instance de Thiès.
    |
    | ═══════════════════════════════════════════════════════════════════
    | CE QUI NE FIGURE PAS ICI, ET POURQUOI
    | ═══════════════════════════════════════════════════════════════════
    | Le NUMÉRO DE CARTE D'IDENTITÉ et la DATE DE NAISSANCE du gérant
    | apparaissent sur les documents d'immatriculation. Ils ne sont exigés par
    | aucune mention légale, et les publier sur un site ouvert reviendrait à
    | exposer des données personnelles sensibles à toute la Terre — y compris
    | à qui voudrait usurper cette identité.
    |
    | On publie ce que la loi demande : la dénomination, l'adresse, le contact,
    | les numéros d'immatriculation de L'ENTREPRISE, et le responsable de
    | publication. Rien de plus.
    |
    | ═══════════════════════════════════════════════════════════════════
    | POURQUOI UN FICHIER DE CONFIGURATION
    | ═══════════════════════════════════════════════════════════════════
    | Ces valeurs apparaissent sur trois pages, dans les factures à venir et
    | dans les échanges avec un agrégateur de paiement. Écrites trois fois,
    | elles divergeraient au premier changement d'adresse — et une mention
    | légale fausse est pire qu'absente : elle engage.
    |
    */

    'editeur' => [
        // Une entreprise individuelle n'a pas de raison sociale distincte de
        // son titulaire : la dénomination EST le nom du gérant.
        'denomination' => env('LEGAL_DENOMINATION', 'DIONE MOUHAMED'),
        'forme' => env('LEGAL_FORME', 'Entreprise individuelle'),

        'rccm' => env('LEGAL_RCCM', 'SN.THS.2026.A.4360'),
        'ninea' => env('LEGAL_NINEA', '013218369'),

        'adresse' => env('LEGAL_ADRESSE', 'Quartier Escale, Thiès, Sénégal'),
        'ville' => env('LEGAL_VILLE', 'Thiès'),
        'pays' => env('LEGAL_PAYS', 'Sénégal'),

        'telephone' => env('LEGAL_TELEPHONE', '+221 77 383 13 64'),

        // Le responsable de publication engage sa responsabilité sur le
        // contenu du site : la loi exige qu'il soit nommé.
        'responsable' => env('LEGAL_RESPONSABLE', 'Mouhamed Dione'),
        'qualite' => env('LEGAL_QUALITE', 'Gérant'),
    ],

    /*
    |--------------------------------------------------------------------------
    | HÉBERGEUR
    |--------------------------------------------------------------------------
    |
    | Son nom et son adresse doivent figurer sur le site — c'est vers lui que
    | se tourne une autorité si l'éditeur reste injoignable. Une valeur fausse
    | ou absente ne se remarque qu'au moment où elle compte.
    |
    | À CHANGER EN MÊME TEMPS QUE L'HÉBERGEUR, jamais après.
    |
    */

    'hebergeur' => [
        'nom' => env('LEGAL_HOST_NAME', 'Render Services, Inc.'),
        'adresse' => env('LEGAL_HOST_ADDRESS', '525 Brannan Street, Suite 300, San Francisco, CA 94107, États-Unis'),
        'site' => env('LEGAL_HOST_SITE', 'https://render.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Date de dernière mise à jour, affichée sur les trois pages.
    |--------------------------------------------------------------------------
    |
    | À AVANCER À CHAQUE MODIFICATION DE FOND. Une date qui ne bouge pas alors
    | que le texte change fait perdre à ces pages leur seule valeur probante :
    | savoir quelles conditions s'appliquaient à quelle date.
    |
    */

    'mise_a_jour' => env('LEGAL_UPDATED_AT', '16 août 2026'),

];
