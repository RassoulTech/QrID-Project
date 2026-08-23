// =============================================================================
// « ENREGISTRER » — ouvrir les Contacts, plutôt que déposer un fichier
// =============================================================================
//
// CE QUI SE PASSAIT, ET POURQUOI CE N'ÉTAIT PAS SATISFAISANT
// -----------------------------------------------------------------------------
// Le lien menait à une vCard servie en « attachment ». Sur iOS, cela ouvre
// directement l'application Contacts : parfait. Sur Android, Chrome DÉPOSE le
// fichier dans les téléchargements, affiche une pastille, et il faut ensuite
// la retrouver, la toucher, choisir « Contacts », confirmer le compte. Cinq
// gestes après un scan qui en promettait un.
//
// CE QU'ON PEUT RÉELLEMENT FAIRE — et ce qu'on ne peut pas
// -----------------------------------------------------------------------------
// Il n'existe AUCUNE API web qui ouvre l'écran « nouveau contact » de façon
// portable. La Contact Picker API lit les contacts, elle n'en crée pas. Le
// seul levier est le schéma d'intention d'Android :
//
//     intent://…#Intent;action=android.intent.action.INSERT;
//               type=vnd.android.cursor.dir/contact;S.name=…;end
//
// Chrome sur Android l'ouvre sur le formulaire de création de contact, déjà
// rempli. C'est exactement le geste attendu.
//
// La répartition est donc :
//
//   Android  →  intention native, formulaire de contact prérempli
//   iOS      →  vCard servie en ligne, Contacts s'ouvre seul
//   Ailleurs →  la vCard, comme avant
//
// CE MODULE NE PORTE RIEN. Sans lui, le lien reste la vCard, qui fonctionne
// partout. Il ne fait que RÉÉCRIRE la destination sur Android, et seulement
// là. C'est une amélioration, pas une dépendance.
//
// LES DONNÉES SONT DANS LE BALISAGE, en data-*. Les relire depuis la page
// évite un aller-retour serveur au moment précis où le visiteur a décidé
// d'agir — et évite de dupliquer côté client des règles de formatage qui
// vivent déjà dans VCardService.
// =============================================================================

export default function enregistrerContact() {
    const lien = document.querySelector('[data-enregistrer-contact]');

    if (!lien) {
        return;
    }

    /*
     | ═══════════════════════════════════════════════════════════════════
     | SUR ORDINATEUR, LE FICHIER NE SERT À RIEN — ON PROPOSE LE QR
     | ═══════════════════════════════════════════════════════════════════
     | Un navigateur de bureau n'a pas d'application Contacts à ouvrir : le
     | téléchargement d'un .vcf est le seul comportement possible, et il ne
     | rend service à personne. Le visiteur récupère un fichier qu'il ne
     | saura pas quoi faire, referme, et le contact n'est jamais enregistré.
     |
     | Le geste utile est ailleurs : sortir son téléphone et scanner. Le
     | bouton ouvre donc le QR en plein écran — celui qui est déjà dans la
     | page, sans une requête de plus.
     |
     | Aucun QR dans la page (le profil n'en a pas) : on laisse le lien tel
     | quel. Un fichier vaut encore mieux que rien.
     */
    const bureau = !/Android|iPhone|iPad|iPod/i.test(navigator.userAgent);

    if (bureau) {
        if (document.getElementById('qr')) {
            lien.setAttribute('href', '#qr');
            lien.removeAttribute('target');
            lien.dataset.enregistrerBureau = 'qr';

            // LE LIBELLÉ DIT CE QUE LE BOUTON FAIT. « Enregistrer » qui
            // ouvre un QR Code serait une promesse tenue de travers.
            const texte = lien.querySelector('[data-enregistrer-texte]');

            if (texte) {
                texte.textContent = texte.dataset.bureau || texte.textContent;
            }
        }

        return;
    }

    /*
     | iOS N'A PAS D'ÉQUIVALENT DE L'INTENTION ANDROID, et c'est définitif :
     | aucune API web n'ouvre l'écran « nouveau contact » sur iPhone. La
     | fiche est servie « inline » avec le bon type MIME, ce qui fait ouvrir
     | à Safari sa feuille d'aperçu de contact, avec « Ajouter les
     | contacts ». C'est le maximum atteignable, et c'est ce que font tous
     | les produits de cette catégorie.
     */
    if (!/Android/i.test(navigator.userAgent)) {
        return;
    }

    const champs = [
        ['name', lien.dataset.nom],
        ['phone', lien.dataset.telephone],
        ['email', lien.dataset.email],
        ['company', lien.dataset.entreprise],
        ['job_title', lien.dataset.fonction],
        ['postal', lien.dataset.adresse],
        ['notes', lien.dataset.notes],
    ];

    const extras = champs
        .filter(([, valeur]) => valeur)
        // encodeURIComponent seul laisse passer « ; » et « # », qui sont les
        // séparateurs du schéma lui-même : un nom contenant l'un des deux
        // couperait l'intention en morceaux et ouvrirait n'importe quoi.
        .map(([clef, valeur]) => `S.${clef}=${encodeURIComponent(valeur).replace(/[;#]/g, escapeHex)}`)
        .join(';');

    /*
     | AUCUN « scheme= » DANS L'INTENTION.
     |
     | La première version déclarait « scheme=qrid » : Android cherchait
     | alors une application capable d'ouvrir qrid://contact, n'en trouvait
     | aucune, et retombait sur l'adresse de repli — c'est-à-dire le
     | téléchargement qu'on voulait éviter.
     |
     | Une intention INSERT se résout par son ACTION et son TYPE MIME, pas
     | par un schéma d'URI. Sans schéma déclaré, Android interroge
     | directement l'application Contacts.
     */
    const intention =
        'intent://contact#Intent' +
        ';action=android.intent.action.INSERT' +
        ';type=vnd.android.cursor.dir/contact' +
        (extras ? ';' + extras : '') +
        // S.browser_fallback_url : si le système ne sait pas ouvrir
        // l'intention — un navigateur exotique, un fabricant qui a retiré
        // l'application Contacts — Chrome suit cette adresse à la place. Le
        // visiteur retombe alors sur la vCard, jamais sur une page d'erreur.
        ';S.browser_fallback_url=' + encodeURIComponent(lien.href) +
        ';end';

    lien.setAttribute('href', intention);

    // Le lien ne s'ouvre plus dans un onglet : une intention n'a pas de page.
    lien.removeAttribute('target');
}

function escapeHex(caractere) {
    return '%' + caractere.charCodeAt(0).toString(16).toUpperCase();
}
