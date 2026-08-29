<?php

/*
|--------------------------------------------------------------------------
| E-MAILS
|--------------------------------------------------------------------------
|
| Un e-mail part dans la langue de SON DESTINATAIRE, jamais dans celle de
| qui l'a déclenché. C'est le contrat HasLocalePreference porté par le
| modèle User qui l'assure : `Mail::to($user)` lit `preferredLocale()` et
| rend le message dans cette langue, y compris depuis un worker qui n'a ni
| session, ni cookie, ni requête.
|
| Sans ce mécanisme, un administrateur francophone qui prolonge
| l'abonnement d'un client anglophone lui enverrait un message en
| français — parce que c'est LUI qui a cliqué.
|
| Chaque message a deux gabarits, HTML et texte brut. Ils partagent les
| MÊMES clés : une phrase corrigée d'un seul côté se remarquerait le jour
| où quelqu'un lit la version texte, c'est-à-dire jamais.
|
*/

return [

    'commun' => [
        'bonjour' => 'Bonjour :nom,',
        'pied_produit' => 'plateforme d\'identité professionnelle numérique.',
        'pied_raison' => 'Cet e-mail vous est envoyé car une action a été effectuée avec votre adresse.',
        'lien_brut' => 'Si le bouton ne s\'affiche pas, copiez ce lien dans votre navigateur :',
        'lien_a_partager' => 'Votre lien à partager',
        'ignorer' => 'Si vous n\'êtes pas à l\'origine de cette demande, ignorez simplement ce message.',
    ],

    'confirmation' => [
        'sujet' => 'Confirmez votre inscription — :marque',
        'titre' => 'Confirmez votre inscription',
        'intro' => 'Plus qu\'une étape pour activer votre présence professionnelle numérique et démarrer votre essai gratuit de :jours jours : confirmez votre adresse e-mail.',
        'bouton' => 'Confirmer mon inscription',
        'validite' => 'Ce lien est valable :minutes minutes. Tant que vous ne l\'avez pas ouvert, aucun compte n\'est créé.',
    ],

    'deja_inscrit' => [
        'sujet' => 'Vous avez déjà un compte — :marque',
        'titre' => 'Vous avez déjà un compte',
        'intro' => 'Une demande d\'inscription vient d\'être faite avec cette adresse e-mail, mais elle est <strong>déjà associée à un compte :marque</strong>. Aucun nouveau compte n\'a été créé.',
        'intro_texte' => 'Une demande d\'inscription vient d\'être faite avec cette adresse e-mail, mais elle est déjà associée à un compte :marque. Aucun nouveau compte n\'a été créé.',
        'si_vous' => 'Si c\'était vous, connectez-vous simplement :',
        'bouton' => 'Me connecter',
        'oubli' => 'Mot de passe oublié ?',
        'oubli_lien' => 'Réinitialisez-le ici',
        'ignorer' => 'Si vous n\'êtes pas à l\'origine de cette demande, ignorez ce message : votre compte reste inchangé.',
    ],

    'bienvenue' => [
        'sujet' => 'Votre compte est actif — :marque',
        'titre' => 'Votre compte est actif',
        'essai' => 'Votre adresse est confirmée et votre compte est actif. Votre essai gratuit de :jours jours a démarré aujourd\'hui.',
        'essai_fin' => 'Il court jusqu\'au :date.',
        'etape' => 'Il reste une étape : créer votre carte. Comptez cinq minutes — nom, fonction, coordonnées, et le choix d\'un modèle. Vous obtenez ensuite un lien et un QR Code à partager immédiatement.',
        'bouton' => 'Créer ma carte',
        'groupe_titre' => 'Un groupe WhatsApp est réservé à nos clients.',
        'groupe_texte' => 'Entraide, questions, et réponses rapides de notre équipe.',
        'groupe_lien' => 'Rejoindre le groupe',
        'groupe_texte_brut' => 'Un groupe WhatsApp est réservé à nos clients — entraide, questions, et réponses rapides de notre équipe :',
        'sans_paiement' => 'Pendant l\'essai, votre carte est publiable et consultable sans aucun paiement. Aucun moyen de paiement ne vous est demandé.',
    ],

    'paiement_reussi' => [
        'sujet' => 'Paiement confirmé — :montant FCFA',
        'titre' => 'Paiement confirmé',
        'intro' => 'Votre paiement de <strong>:montant FCFA</strong> est encaissé et votre abonnement est actif. Conservez ce message : il vaut reçu.',
        'intro_texte' => 'Votre paiement de :montant FCFA est encaissé et votre abonnement est actif. Conservez ce message : il vaut reçu.',
        'bouton_carte' => 'Voir ma carte en ligne',
        'bouton_espace' => 'Ouvrir mon espace',
        'pieces' => 'Votre QR Code et le fichier prêt pour l\'impression sont joints à ce message. Ils restent également téléchargeables depuis votre espace.',
        'question' => 'Une question sur ce paiement ? Répondez à ce message en citant la référence ci-dessus.',
        'lignes' => [
            'reference' => 'Référence',
            'date' => 'Date',
            'formule' => 'Formule',
            'moyen' => 'Moyen de paiement',
            'montant' => 'Montant',
            'echeance' => 'Valable jusqu\'au',
        ],
    ],

    'paiement_echoue' => [
        'sujet' => 'Votre paiement n\'a pas abouti',
        'titre' => 'Paiement non abouti',
        'rien_preleve' => '<strong>Aucune somme n\'a été prélevée.</strong> Votre paiement de :montant FCFA pour la formule :formule n\'est pas allé à son terme, et votre abonnement n\'a pas été modifié.',
        'rien_preleve_texte' => 'Aucune somme n\'a été prélevée. Votre paiement de :montant FCFA pour la formule :formule n\'est pas allé à son terme, et votre abonnement n\'a pas été modifié.',
        'causes' => 'Cela arrive le plus souvent pour une raison simple : solde insuffisant au moment de l\'opération, code de confirmation non saisi à temps, ou page fermée avant la fin. Vous pouvez réessayer immédiatement.',
        'bouton' => 'Réessayer le paiement',
        'litige' => 'Si une somme apparaissait malgré tout sur votre compte, répondez à ce message : nous la retrouvons et nous la traitons.',
    ],

    'mot_de_passe_change' => [
        'sujet' => 'Votre mot de passe a été modifié',
        'titre' => 'Votre mot de passe a été modifié',
        'intro' => 'Le mot de passe de votre compte a été modifié le <strong>:date</strong>.',
        'intro_texte' => 'Le mot de passe de votre compte a été modifié le :date.',
        'si_vous' => '<strong>Si c\'est bien vous</strong>, il n\'y a rien à faire : ce message est une simple confirmation.',
        'si_vous_texte' => 'Si c\'est bien vous, il n\'y a rien à faire : ce message est une simple confirmation.',
        'sinon' => '<strong>Si ce n\'est pas vous</strong>, votre compte est en danger. Demandez immédiatement un nouveau mot de passe pour reprendre la main :',
        'sinon_texte' => 'Si ce n\'est pas vous, votre compte est en danger. Demandez immédiatement un nouveau mot de passe pour reprendre la main :',
        'bouton' => 'Reprendre le contrôle de mon compte',
        'ip' => 'Adresse IP à l\'origine de la modification : :ip',
        'toujours_envoye' => 'Ce message de sécurité est envoyé à chaque changement de mot de passe et ne peut pas être désactivé.',
    ],

    'reinitialisation' => [
        'sujet' => 'Réinitialisation de votre mot de passe — :marque',
        'titre' => 'Réinitialisation de mot de passe',
        'intro' => 'Vous avez demandé la réinitialisation du mot de passe de votre compte :marque. Cliquez sur le bouton ci-dessous pour en choisir un nouveau.',
        'bouton' => 'Choisir un nouveau mot de passe',
        'validite' => 'Ce lien est valable :minutes minutes.',
        'ignorer' => 'Si vous n\'êtes pas à l\'origine de cette demande, ignorez ce message : votre mot de passe restera inchangé.',
    ],

    'carte_publiee' => [
        'sujet' => 'Votre carte est en ligne — :marque',
        'titre' => 'Votre carte est en ligne',
        'intro' => 'Votre carte est publiée. Toute personne qui ouvre le lien ci-dessous voit vos coordonnées et peut les enregistrer dans son téléphone en un geste.',
        'bouton' => 'Voir ma carte',
        'telechargements' => 'Depuis votre espace, vous pouvez télécharger le QR Code de cette carte et le fichier prêt pour l\'impression.',
    ],

    'rappel_carte' => [
        'sujet_1' => 'Votre carte attend d\'être publiée',
        'sujet_2' => 'Votre carte n\'est toujours pas en ligne',
        'titre' => 'Votre carte attend d\'être publiée',
        'premier' => 'Votre carte est enregistrée, mais elle n\'est pas encore en ligne : son lien ne répond donc à personne. Il ne manque qu\'un clic pour la publier.',
        'second' => 'Votre carte est toujours enregistrée sans être publiée. Si quelque chose vous a arrêté — un champ qui ne convient pas, une photo qui ne passe pas, un doute sur le rendu — répondez simplement à ce message : nous regardons avec vous.',
        'gratuit' => 'Publier ne coûte rien pendant votre essai gratuit, et reste réversible : vous pouvez retirer votre carte à tout moment.',
        'bouton' => 'Publier ma carte',
        'rang_1' => 'C\'est notre premier rappel à ce sujet.',
        'rang_2' => 'C\'est notre second et dernier rappel à ce sujet.',
    ],

    'abonnement_expire' => [
        'sujet' => 'Votre carte n\'est plus consultable',
        'titre' => 'Votre carte n\'est plus consultable',
        'intro' => 'Votre abonnement est arrivé à échéance le :date. Depuis cette date, le lien public de votre carte ne répond plus.',
        'intactes' => '<strong>Vos données sont intactes.</strong> Rien n\'a été supprimé : votre carte, vos coordonnées, vos liens et votre QR Code sont conservés. Votre adresse publique reste la même, donc les cartes que vous avez déjà imprimées ou distribuées redeviendront valables telles quelles.',
        'intactes_texte' => 'Vos données sont intactes. Rien n\'a été supprimé : votre carte, vos coordonnées, vos liens et votre QR Code sont conservés. Votre adresse publique reste la même, donc les cartes que vous avez déjà imprimées ou distribuées redeviendront valables telles quelles.',
        'renouveler' => 'Un renouvellement remet tout en ligne en quelques secondes.',
        'bouton' => 'Réactiver ma carte',
        'adresse_conservee' => 'Adresse conservée pour votre carte : :url',
    ],

    'abonnement_expirant' => [
        'sujet' => 'Votre abonnement arrive à échéance',
        'sujet_aujourdhui' => 'Votre abonnement se termine aujourd\'hui',
        'sujet_demain' => 'Votre abonnement se termine demain',
        'titre' => 'Votre abonnement arrive à échéance',
        'aujourdhui' => 'Votre abonnement :formule se termine <strong>aujourd\'hui</strong>.',
        'demain' => 'Votre abonnement :formule se termine <strong>demain</strong>, le :date.',
        'dans_jours' => 'Votre abonnement :formule se termine dans <strong>:jours jours</strong>, le :date.',
        'aujourdhui_texte' => 'Votre abonnement :formule se termine aujourd\'hui.',
        'demain_texte' => 'Votre abonnement :formule se termine demain, le :date.',
        'dans_jours_texte' => 'Votre abonnement :formule se termine dans :jours jours, le :date.',
        'consequence' => 'Passé cette date, le lien public de votre carte cessera de répondre : les personnes qui l\'ouvriront, ou qui scanneront votre QR Code, ne verront plus vos coordonnées.',
        'rien_supprime' => '<strong>Rien n\'est supprimé.</strong> Votre carte, vos coordonnées et votre lien sont conservés en l\'état. Un renouvellement les remet en ligne immédiatement, sans rien ressaisir et sans changer d\'adresse — les cartes déjà imprimées restent valables.',
        'rien_supprime_texte' => 'Rien n\'est supprimé. Votre carte, vos coordonnées et votre lien sont conservés en l\'état. Un renouvellement les remet en ligne immédiatement, sans rien ressaisir et sans changer d\'adresse — les cartes déjà imprimées restent valables.',
        'bouton' => 'Renouveler mon abonnement',
    ],

    /*
     | ALERTES ET CONTACT — destinées à l'ÉQUIPE, pas aux clients.
     |
     | Elles sont traduites au même titre : rien n'impose que l'équipe soit
     | francophone, et le contrat HasLocalePreference s'applique déjà à
     | tous les comptes, administrateurs compris.
     */
    'alerte' => [
        'action_requise' => 'ACTION REQUISE',
        'pour_information' => 'POUR INFORMATION',
        'bouton' => 'Ouvrir dans l\'administration',
        'automatique' => 'Message automatique destiné à l\'équipe. Il n\'a pas été envoyé au client.',
    ],

    'contact' => [
        'sujet' => '[Contact] :motif — :nom',
        'bandeau' => 'FORMULAIRE DE CONTACT',
        'titre' => 'Message de :nom',
        'lignes' => [
            'nom' => 'Nom',
            'adresse' => 'Adresse',
            'telephone' => 'Téléphone',
            'compte' => 'Compte client',
            'recu_le' => 'Reçu le',
        ],
        'oui' => 'oui',
        'non' => 'non',
    ],

    'contact_suite' => [
        'message' => '--- MESSAGE ---',
        'reponse' => '<strong>Répondez directement à ce message</strong> : votre réponse partira vers :adresse.',
        'reponse_texte' => 'Répondez directement à ce message : votre réponse partira vers :adresse.',
    ],

];
