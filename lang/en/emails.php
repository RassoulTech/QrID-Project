<?php

/*
|--------------------------------------------------------------------------
| EMAILS
|--------------------------------------------------------------------------
|
| An email goes out in ITS RECIPIENT'S language, never in the language of
| whoever triggered it. The HasLocalePreference contract on the User model
| is what guarantees this: `Mail::to($user)` reads `preferredLocale()` and
| renders the message accordingly, including from a worker that has no
| session, no cookie and no request.
|
| Without it, a French-speaking administrator extending an English-speaking
| customer's subscription would send them a message in French — simply
| because they were the one who clicked.
|
| Every message has two templates, HTML and plain text. They share the SAME
| keys: a sentence fixed on only one side would be noticed the day someone
| reads the plain-text version, which is to say never.
|
*/

return [

    'commun' => [
        'bonjour' => 'Hello :nom,',
        'pied_produit' => 'digital professional identity platform.',
        'pied_raison' => 'You are receiving this email because an action was taken using your address.',
        'lien_brut' => 'If the button does not appear, copy this link into your browser:',
        'lien_a_partager' => 'Your link to share',
        'ignorer' => 'If you did not request this, simply ignore this message.',
    ],

    'confirmation' => [
        'sujet' => 'Confirm your registration — :marque',
        'titre' => 'Confirm your registration',
        'intro' => 'One step left to activate your digital professional presence and start your :jours-day free trial: confirm your email address.',
        'bouton' => 'Confirm my registration',
        'validite' => 'This link is valid for :minutes minutes. Until you open it, no account is created.',
    ],

    'deja_inscrit' => [
        'sujet' => 'You already have an account — :marque',
        'titre' => 'You already have an account',
        'intro' => 'A registration was just attempted with this email address, but it is <strong>already linked to a :marque account</strong>. No new account has been created.',
        'intro_texte' => 'A registration was just attempted with this email address, but it is already linked to a :marque account. No new account has been created.',
        'si_vous' => 'If that was you, simply sign in:',
        'bouton' => 'Sign in',
        'oubli' => 'Forgotten your password?',
        'oubli_lien' => 'Reset it here',
        'ignorer' => 'If you did not request this, ignore this message: your account is unchanged.',
    ],

    'bienvenue' => [
        'sujet' => 'Your account is active — :marque',
        'titre' => 'Your account is active',
        'essai' => 'Your address is confirmed and your account is active. Your :jours-day free trial started today.',
        'essai_fin' => 'It runs until :date.',
        'etape' => 'One step remains: creating your card. Allow five minutes — name, job title, contact details, and a template. You then get a link and a QR code to share right away.',
        'bouton' => 'Create my card',
        'groupe_titre' => 'A WhatsApp group is reserved for our customers.',
        'groupe_texte' => 'Peer support, questions, and quick answers from our team.',
        'groupe_lien' => 'Join the group',
        'groupe_texte_brut' => 'A WhatsApp group is reserved for our customers — peer support, questions, and quick answers from our team:',
        'sans_paiement' => 'During the trial, your card can be published and viewed without any payment. No payment method is requested.',
    ],

    'paiement_reussi' => [
        'sujet' => 'Payment confirmed — :montant FCFA',
        'titre' => 'Payment confirmed',
        'intro' => 'Your payment of <strong>:montant FCFA</strong> has been received and your subscription is active. Keep this message: it serves as your receipt.',
        'intro_texte' => 'Your payment of :montant FCFA has been received and your subscription is active. Keep this message: it serves as your receipt.',
        'bouton_carte' => 'View my card online',
        'bouton_espace' => 'Open my account',
        'pieces' => 'Your QR code and the print-ready file are attached to this message. They also remain downloadable from your account.',
        'question' => 'A question about this payment? Reply to this message quoting the reference above.',
        'lignes' => [
            'reference' => 'Reference',
            'date' => 'Date',
            'formule' => 'Plan',
            'moyen' => 'Payment method',
            'montant' => 'Amount',
            'echeance' => 'Valid until',
        ],
    ],

    'paiement_echoue' => [
        'sujet' => 'Your payment did not go through',
        'titre' => 'Payment unsuccessful',
        'rien_preleve' => '<strong>No money has been taken.</strong> Your payment of :montant FCFA for the :formule plan did not complete, and your subscription has not been changed.',
        'rien_preleve_texte' => 'No money has been taken. Your payment of :montant FCFA for the :formule plan did not complete, and your subscription has not been changed.',
        'causes' => 'This usually happens for a simple reason: insufficient balance at the time, a confirmation code not entered in time, or the page closed too early. You can try again straight away.',
        'bouton' => 'Retry the payment',
        'litige' => 'If an amount does appear on your account nonetheless, reply to this message: we will trace it and deal with it.',
    ],

    'mot_de_passe_change' => [
        'sujet' => 'Your password has been changed',
        'titre' => 'Your password has been changed',
        'intro' => 'Your account password was changed on <strong>:date</strong>.',
        'intro_texte' => 'Your account password was changed on :date.',
        'si_vous' => '<strong>If this was you</strong>, there is nothing to do: this message is simply a confirmation.',
        'si_vous_texte' => 'If this was you, there is nothing to do: this message is simply a confirmation.',
        'sinon' => '<strong>If this was not you</strong>, your account is at risk. Request a new password immediately to regain control:',
        'sinon_texte' => 'If this was not you, your account is at risk. Request a new password immediately to regain control:',
        'bouton' => 'Regain control of my account',
        'ip' => 'IP address behind the change: :ip',
        'toujours_envoye' => 'This security message is sent on every password change and cannot be turned off.',
    ],

    'reinitialisation' => [
        'sujet' => 'Reset your password — :marque',
        'titre' => 'Password reset',
        'intro' => 'You requested a password reset for your :marque account. Click the button below to choose a new one.',
        'bouton' => 'Choose a new password',
        'validite' => 'This link is valid for :minutes minutes.',
        'ignorer' => 'If you did not request this, ignore this message: your password will remain unchanged.',
    ],

    'carte_publiee' => [
        'sujet' => 'Your card is online — :marque',
        'titre' => 'Your card is online',
        'intro' => 'Your card is published. Anyone who opens the link below sees your details and can save them to their phone in one tap.',
        'bouton' => 'View my card',
        'telechargements' => 'From your account, you can download this card\'s QR code and the print-ready file.',
    ],

    'rappel_carte' => [
        'sujet_1' => 'Your card is waiting to be published',
        'sujet_2' => 'Your card is still not online',
        'titre' => 'Your card is waiting to be published',
        'premier' => 'Your card is saved, but it is not online yet: its link answers no one. Publishing it takes a single click.',
        'second' => 'Your card is still saved without being published. If something stopped you — a field that does not fit, a photo that will not upload, a doubt about the result — simply reply to this message: we will look at it with you.',
        'gratuit' => 'Publishing costs nothing during your free trial, and it is reversible: you can take your card down at any time.',
        'bouton' => 'Publish my card',
        'rang_1' => 'This is our first reminder about this.',
        'rang_2' => 'This is our second and final reminder about this.',
    ],

    'abonnement_expire' => [
        'sujet' => 'Your card is no longer viewable',
        'titre' => 'Your card is no longer viewable',
        'intro' => 'Your subscription ended on :date. Since then, your card\'s public link no longer responds.',
        'intactes' => '<strong>Your data is intact.</strong> Nothing has been deleted: your card, your contact details, your links and your QR code are all kept. Your public address stays the same, so cards you have already printed or handed out will work again as they are.',
        'intactes_texte' => 'Your data is intact. Nothing has been deleted: your card, your contact details, your links and your QR code are all kept. Your public address stays the same, so cards you have already printed or handed out will work again as they are.',
        'renouveler' => 'Renewing puts everything back online in seconds.',
        'bouton' => 'Reactivate my card',
        'adresse_conservee' => 'Address kept for your card: :url',
    ],

    'abonnement_expirant' => [
        'sujet' => 'Your subscription is about to expire',
        'sujet_aujourdhui' => 'Your subscription ends today',
        'sujet_demain' => 'Your subscription ends tomorrow',
        'titre' => 'Your subscription is about to expire',
        'aujourdhui' => 'Your :formule subscription ends <strong>today</strong>.',
        'demain' => 'Your :formule subscription ends <strong>tomorrow</strong>, on :date.',
        'dans_jours' => 'Your :formule subscription ends in <strong>:jours days</strong>, on :date.',
        'aujourdhui_texte' => 'Your :formule subscription ends today.',
        'demain_texte' => 'Your :formule subscription ends tomorrow, on :date.',
        'dans_jours_texte' => 'Your :formule subscription ends in :jours days, on :date.',
        'consequence' => 'After that date, your card\'s public link will stop responding: anyone who opens it, or scans your QR code, will no longer see your details.',
        'rien_supprime' => '<strong>Nothing is deleted.</strong> Your card, your contact details and your link are kept as they are. Renewing puts them back online immediately, with nothing to re-enter and no change of address — cards already printed remain valid.',
        'rien_supprime_texte' => 'Nothing is deleted. Your card, your contact details and your link are kept as they are. Renewing puts them back online immediately, with nothing to re-enter and no change of address — cards already printed remain valid.',
        'bouton' => 'Renew my subscription',
    ],

    /*
     | ALERTS AND CONTACT — addressed to the TEAM, not to customers.
     |
     | They are translated all the same: nothing says the team must be
     | French-speaking, and the HasLocalePreference contract already
     | applies to every account, administrators included.
     */
    'alerte' => [
        'action_requise' => 'ACTION REQUIRED',
        'pour_information' => 'FOR INFORMATION',
        'bouton' => 'Open in the admin area',
        'automatique' => 'Automated message for the team. It was not sent to the customer.',
    ],

    'contact' => [
        'sujet' => '[Contact] :motif — :nom',
        'bandeau' => 'CONTACT FORM',
        'titre' => 'Message from :nom',
        'lignes' => [
            'nom' => 'Name',
            'adresse' => 'Address',
            'telephone' => 'Phone',
            'compte' => 'Customer account',
            'recu_le' => 'Received on',
        ],
        'oui' => 'yes',
        'non' => 'no',
    ],

    'contact_suite' => [
        'message' => '--- MESSAGE ---',
        'reponse' => '<strong>Reply directly to this message</strong>: your reply will go to :adresse.',
        'reponse_texte' => 'Reply directly to this message: your reply will go to :adresse.',
    ],

];
