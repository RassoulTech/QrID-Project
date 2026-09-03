<?php

/*
|--------------------------------------------------------------------------
| THE CLIENT AREA — monitoring
|--------------------------------------------------------------------------
| Dashboard, statistics, notifications, search. The four screens where the
| client LOOKS at their activity, as opposed to editing their card
| (profile.php) or paying (payment.php).
*/

return [

    'titre' => 'Dashboard',

    'vide' => [
        'bienvenue' => 'Welcome :nom',
        'texte' => 'Create your professional profile to share it by link or QR code.',
        'creer' => 'Create my profile',
    ],

    'tete' => [
        'bonjour' => 'Hello :prenom',
        'sous' => 'Follow your card and its audience.',
        'voir_carte' => 'View my public card',
        'activer_carte' => 'Activate my card',
    ],

    'expire' => [
        'titre' => 'Your subscription has expired',
        'texte' => 'Your contacts can no longer open your card&nbsp;: the public link does not '
            .'answer. Nothing is lost — one payment puts it back online right away.',
        'reactiver' => 'Reactivate my card',
    ],

    'apercu' => [
        'titre' => 'Overview',

        'views_libelle' => 'Card views',
        'views_attente' => 'Share your link to see your first views come in.',

        'scans_libelle' => 'QR code scans',
        'scans_attente' => 'No scans yet.',

        'saves_libelle' => 'Contacts saved',
        'saves_attente' => 'Nobody has saved you yet.',

        'days_libelle' => 'Days of subscription',
        'days_attente' => 'No subscription running.',
    ],

    'carte' => [
        'titre' => 'My card',
        'lien_public' => 'Public link',
        'lien_aria' => 'Public link to your card',
        'copier' => 'Copy',
        'copie' => 'Copied',
        'photo' => 'Photo',
        'banniere' => 'Banner',
        'photo_ok' => 'Photo saved',
        'photo_absente' => 'No photo',
        'banniere_ok' => 'Banner saved',
        'banniere_absente' => 'No banner',
        'qr_png' => 'QR as PNG',
        'qr_svg' => 'QR as SVG',
        'imprimable' => 'Printable card',
        'modifier' => 'Edit my card',
    ],

    'physique' => [
        'titre' => 'My printed card',
        'sans_adresse' => 'Your PVC card is <strong>free</strong> and waiting for you. '
            .'All we need is the address to deliver it to.',
        'indiquer_adresse' => 'Give my address',
        'livraison' => 'Delivery to <strong>:ville</strong>, for :destinataire.',
        'livree_le' => 'Delivered on :date.',
        'expediee_le' => 'Shipped on :date.',
        'depart_prochain' => 'Leaving with the next print run, in about :jours days.',
        'corriger_adresse' => 'Correct the address',
    ],

    'activite' => [
        'titre' => 'Recent activity',
        'periode_aria' => 'Period shown',
        'periode_jours' => ':compte days',
        'graphique_aria' => 'Card views over the last :compte days',
        'infobulle' => ':jour · :compte view|:jour · :compte views',
        'tableau_titre' => 'Views per day',
        'colonne_jour' => 'Day',
        'colonne_vues' => 'Views',
        'vide_titre' => 'No views yet',
        'vide_texte' => 'Share your QR code or your link&nbsp;: visits will appear here, '
            .'day by day.',
    ],

    'rail' => [
        'visiteurs' => 'Latest visitors',
        'scan' => 'QR code scan',
        'consultation' => 'Direct visit',
        'enregistrement' => 'Contact saved',
        'partage' => 'Share started',
        'aucun_visiteur' => 'Nobody has opened your card yet.',
        'journal' => 'Account activity',
        'aucune_activite' => 'No activity recorded.',
        'groupe_titre' => 'Need a hand&nbsp;?',
        'groupe_texte' => 'A WhatsApp group brings :marque clients together. Questions, mutual '
            .'help, and answers from our team.',
        'groupe_rejoindre' => 'Join the group',
    ],

    'stats' => [
        'titre' => 'Statistics',
        'sous' => 'Your card\'s audience, day by day.',
        'periode' => 'Period shown',
        'derniers_jours' => 'last :compte days',
        'sur_jours' => 'Over :compte days',
        'vues_directes' => 'Direct views',
        'scans' => 'QR code scans',
        'contacts' => 'Contacts saved',
        'total' => 'Total views',
        'partages' => 'Shares started',
        'partages_aide' => 'Times WhatsApp or the share menu was opened. An actual send cannot be measured.',
        'evolution' => 'Trend',
        'evolution_aria' => 'Views and scans over the last :compte days',
        'infobulle' => ':jour · :vues view(s), :scans scan(s)',
        'legende_vues' => 'Direct views',
        'legende_scans' => 'Scans',
        'tableau_titre' => 'Views and scans per day',
        'colonne_jour' => 'Day',
        'colonne_vues' => 'Views',
        'colonne_scans' => 'Scans',
        'vide_titre' => 'No events in this period',
        'vide_texte' => 'Share your QR code or your link&nbsp;: every visit will show up here.',
        'voir_qr' => 'View my QR code',
        'derniers' => 'Latest events',
        'aucun_evenement' => 'No events recorded yet.',
    ],

    'notifications' => [
        'titre' => 'Notifications',
        'sous' => 'What matters on your account.',
        'tout_marquer' => 'Mark all as read',
        'vide_titre' => 'No notifications',
        'vide_texte' => 'You will be told whenever something deserves it&nbsp;: the first visit '
            .'to your card, a contact saved, a payment confirmed, a subscription due.',
    ],

    'recherche' => [
        'titre' => 'Search',
        'resultats_pour' => 'Results for «&nbsp;:terme&nbsp;»',
        'invite' => 'Type something in the search bar.',
        'trop_court' => 'Type at least two characters.',
        'aucun_titre' => 'No results',
        'aucun_texte' => 'Nothing matches «&nbsp;:terme&nbsp;» in your card, your payments or '
            .'your notifications.',
        'ma_carte' => 'My card',
        'paiements' => 'Payments',
        'notifications' => 'Notifications',
    ],

    'flash' => [
        'notifications_lues' => 'All your notifications are marked as read.',
    ],

    /*
     | NOTIFICATIONS ARE WRITTEN TO THE DATABASE, so they are frozen at
     | write time. They are rendered in the RECIPIENT'S language — not the
     | language of the action: a card view comes from a stranger, whose
     | language is no concern of the card's owner.
     |
     | Accepted consequence: a notification written before a language change
     | stays in the old language.
     */
    'notifs' => [
        'paiement_confirme' => 'Payment confirmed',
        'carte_consultee' => 'Your card has been viewed',
        'contact_enregistre' => 'A contact saved your details',
    ],
];
