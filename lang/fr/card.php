<?php

/*
|--------------------------------------------------------------------------
| LA CARTE — publique, QR Code, carte physique
|--------------------------------------------------------------------------
| Ce que voit le VISITEUR après un scan, l'écran du QR Code, et la commande
| de la carte PVC.
|
| LA PAGE PUBLIQUE SUIT LA LANGUE DU VISITEUR, jamais celle du porteur : c'est
| le correspondant qui la lit. Son CONTENU — nom, fonction, entreprise — reste
| évidemment tel que le porteur l'a écrit.
*/

return [

    // =================================================================
    // LA CARTE PUBLIQUE
    // =================================================================
    'publique' => [
        'email' => 'E-mail',
        'telephone' => 'Téléphone',
        'site_web' => 'Site web',
        'localisation' => 'Localisation',
        'grille_aria' => 'Contacter et suivre',
        'enregistrer' => 'Enregistrer',
        'pied' => 'Carte créée avec',
        'qr_aria' => 'QR Code',
        'fermer' => 'Fermer',
    ],

    // =================================================================
    // CARTE INACTIVE
    // =================================================================
    /*
     | LE PREMIER GESTE d'un client qui vient de créer sa carte est de scanner
     | son propre QR pour voir si « ça marche ». Il tombait sur une page
     | d'erreur nue, sans aucun moyen de savoir que rien n'était cassé.
     |
     | LE NOM N'EST AFFICHÉ QU'AU PROPRIÉTAIRE. Pour tout autre visiteur, cette
     | page ne révèle rien : ni de qui il s'agit, ni même que quelqu'un a
     | réservé cette adresse.
     */
    'inactive' => [
        'titre_proprietaire' => 'Votre carte n\'est pas encore en ligne',
        'titre_visiteur' => 'Carte non active',
        'description' => 'Cette carte de visite numérique n\'est pas active.',

        'suspendue_titre' => 'Votre carte a été suspendue',
        'suspendue_texte' => 'Cette décision vient de notre équipe, et vos informations sont '
            .'intactes. Écrivez-nous : nous vous en donnerons la raison et ce qu\'il faut faire.',
        'suspendue_action' => 'Contacter le support',

        'abonnement_titre' => 'Votre abonnement n\'est plus actif',
        'abonnement_texte' => '<strong>Rien n\'est perdu.</strong> Votre carte, vos informations '
            .'et votre QR Code sont conservés tels quels. Elle redevient visible dès '
            .'l\'activation — et <strong>ni le lien ni le QR Code ne changent</strong>, ceux que '
            .'vous avez déjà partagés continueront de mener ici.',
        'abonnement_prolonger' => 'Prolonger mon abonnement',
        'abonnement_activer' => 'Activer ma carte',

        'brouillon_titre' => 'Votre carte n\'est pas encore en ligne',
        'brouillon_texte' => '<strong>Rien n\'est cassé, et votre QR Code est juste.</strong> '
            .'Il mènera exactement ici dès que la carte sera active. Il ne reste qu\'une '
            .'étape : la mettre en ligne.',
        'brouillon_action' => 'Mettre ma carte en ligne',

        'retour' => 'Retour à mon espace',

        // Vue par un tiers : aucun nom, aucune information.
        'visiteur_titre' => 'Cette carte n\'est pas active',
        'visiteur_texte' => 'Elle n\'existe pas, ou son propriétaire ne l\'a pas encore mise en '
            .'ligne. Si vous venez de scanner un QR Code, demandez à votre interlocuteur '
            .'d\'activer sa carte.',
        'visiteur_connexion' => 'C\'est la mienne — me connecter',
        'visiteur_creer' => 'Créer ma carte de visite numérique',
    ],

    // =================================================================
    // MON QR CODE
    // =================================================================
    'qr' => [
        'titre' => 'Mon QR Code',
        'sous' => 'Imprimez-le, partagez-le&nbsp;: il ouvre votre carte sur n\'importe quel téléphone.',
        'code' => 'Votre code',
        'png' => 'Télécharger en PNG',
        'svg' => 'Télécharger en SVG',
        'pdf' => 'Carte imprimable (PDF)',
        'formats' => 'Le SVG est vectoriel&nbsp;: c\'est le format à confier à un imprimeur. '
            .'Le PNG convient partout ailleurs.',
        'apercu' => 'Aperçu de la carte',
        'alt' => 'QR Code de votre profil',
    ],

    // =================================================================
    // EXEMPLE PUBLIC
    // =================================================================
    'demo' => [
        'titre' => ':nom — exemple de profil',
        'titre_nu' => 'Exemple de profil',
        'description' => 'Exemple de profil professionnel numérique. Créez le vôtre en trois minutes.',
        'bandeau' => 'Exemple de démonstration.',
        'enregistrer' => 'Enregistrer le contact',
        'appeler' => 'Appeler',
    ],

    // =================================================================
    // CARTE PHYSIQUE — l'adresse de livraison
    // =================================================================
    'physique' => [
        'titre' => 'Ma carte physique',
        'kicker' => 'Carte physique',
        'entete' => 'Où livrer votre carte&nbsp;?',
        'sous' => 'Votre carte PVC est <strong>offerte</strong> avec votre abonnement. Elle part '
            .'à la prochaine production, sous environ <strong>:jours jours</strong> après '
            .'validation de l\'adresse.',

        'expediee_le' => 'Expédiée le :date',
        'imprimee_le' => 'Imprimée le :date',

        // L'adresse est verrouillée dès la production : on ne montre pas un
        // formulaire dont la soumission serait refusée.
        'verrouillee' => 'Votre carte est déjà en production : l\'adresse ne peut plus être '
            .'modifiée. Si elle est erronée, écrivez-nous — nous interviendrons avant '
            .'l\'expédition si c\'est encore possible.',

        'destinataire' => 'Destinataire',
        'ville' => 'Ville',
        'region' => 'Région',

        'nom_destinataire' => 'Nom du destinataire',
        'telephone_destinataire' => 'Téléphone du destinataire',
        'telephone_aide' => 'C\'est ce numéro que le livreur appellera.',
        'adresse_exemple' => 'Cité Keur Gorgui, villa 42',
        'ville_exemple' => 'Dakar',
        'indications' => 'Indications pour le livreur',
        'indications_exemple' => 'En face de la pharmacie, portail vert',

        'plus_tard' => 'Plus tard',
        'enregistrer' => 'Enregistrer l\'adresse',
    ],

    'voir_verso' => 'Voir le verso',
    'protocole' => 'Protocole d\'identité numérique',

    'actions' => [
        'appeler' => 'Appeler',
        'localisation' => 'Localisation',
    ],
];
