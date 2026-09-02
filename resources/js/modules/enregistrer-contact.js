// =============================================================================
// « ENREGISTRER » — ouvrir les Contacts, jamais déposer un fichier
// =============================================================================
//
// CE QU'ON VEUT, ET CE QUE LE WEB PERMET
// -----------------------------------------------------------------------------
// Un scan doit mener à un contact enregistré en un geste. Télécharger un
// fichier .vcf n'est pas ce geste : c'est un détour qui se termine le plus
// souvent dans un dossier de téléchargements que personne ne rouvre.
//
// Il n'existe aucune API web portable qui ouvre l'écran « nouveau contact ».
// Chaque système a sa voie, et il n'y en a que deux :
//
//   ANDROID  le schéma d'intention. Chrome et Samsung Internet le résolvent
//            par l'ACTION et le TYPE MIME, et ouvrent le formulaire de
//            création de contact déjà rempli. C'est le rôle de ce module.
//
//   iOS      la fiche vCard elle-même, servie SANS en-tête de disposition.
//            Safari la reconnaît alors comme un contact et ouvre sa feuille
//            « Ajouter aux contacts » au lieu de la déposer dans Fichiers.
//            C'est le contrôleur qui s'en charge — voir
//            PublicProfileController::vcard(). Rien à faire ici.
//
// D'où un module qui ne traite QUE le cas Android. Sur iOS il n'a rien à
// faire ; sur ordinateur non plus, où le lien reste la fiche — ce qui est le
// comportement attendu d'un navigateur de bureau.
//
// CE MODULE NE PORTE RIEN. Sans lui, le lien reste la fiche, qui fonctionne
// partout. Il ne fait que RÉÉCRIRE la destination sur Android.
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

    if (/iPad|iPhone|iPod/.test(navigator.userAgent)) {
        iOS(lien);

        return;
    }

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
     | ═══════════════════════════════════════════════════════════════════
     | « intent: » ET NON « intent://contact »
     | ═══════════════════════════════════════════════════════════════════
     | LE DÉFAUT QUE CELA CORRIGE, ET IL EXPLIQUE LES ESSAIS PRÉCÉDENTS.
     |
     | La forme « intent://contact#Intent;… » donne à l'intention une URI de
     | DONNÉES valant « //contact ». Or une intention INSERT de contact
     | attend soit aucune donnée, soit une URI content:// désignant une fiche
     | existante. Avec « //contact », Android cherche une application capable
     | d'ouvrir cette adresse-là, n'en trouve aucune, et suit l'adresse de
     | repli — c'est-à-dire exactement le téléchargement qu'on voulait
     | supprimer. L'intention était bien construite, elle ne désignait rien.
     |
     | La forme documentée pour une intention SANS données est « intent: »
     | suivi directement du fragment. La résolution se fait alors sur
     | l'action et le type MIME, ce qui désigne l'application Contacts.
     |
     | S.browser_fallback_url reste : un navigateur qui ne connaît pas le
     | schéma — Firefox pour Android, par exemple — suit cette adresse plutôt
     | que d'afficher une erreur. Le visiteur retombe sur la fiche, jamais
     | sur une page morte.
     */
    const intention =
        'intent:#Intent' +
        ';action=android.intent.action.INSERT' +
        ';type=vnd.android.cursor.dir/contact' +
        (extras ? ';' + extras : '') +
        ';S.browser_fallback_url=' + encodeURIComponent(lien.href) +
        ';end';

    lien.setAttribute('href', intention);

    // Le lien ne s'ouvre plus dans un onglet : une intention n'a pas de page.
    lien.removeAttribute('target');
}

function escapeHex(caractere) {
    return '%' + caractere.charCodeAt(0).toString(16).toUpperCase();
}


/*
 | ═══════════════════════════════════════════════════════════════════════
 | iOS — LA FEUILLE DE PARTAGE, PARCE QUE SAFARI A CHANGÉ D'AVIS
 | ═══════════════════════════════════════════════════════════════════════
 | La réponse vCard est servie SANS Content-Disposition précisément pour que
 | Safari se fie au type MIME et ouvre sa fiche de contact. Cela a fonctionné,
 | puis a cessé : les versions récentes rangent le fichier dans Fichiers et
 | laissent l'utilisateur le rouvrir à la main. Deux gestes de plus, et le
 | second se perd dans un dossier que personne ne consulte.
 |
 | LA FEUILLE DE PARTAGE AVEC FICHIER est la voie qui reste. `navigator.share`
 | avec un fichier .vcf ouvre la feuille du système, où « Contacts » figure :
 | on y arrive sur l'écran d'ajout, prérempli.
 |
 | IL N'EXISTE TOUJOURS AUCUNE API QUI ÉCRIT DIRECTEMENT DANS LE CARNET.
 | Ni sur iOS, ni sur Android. Ce que l'on peut faire de mieux, des deux
 | côtés, c'est ouvrir l'écran de création prérempli — un seul geste restant.
 |
 | STRICTEMENT ADDITIF. On n'empêche le lien de suivre son cours qu'une fois
 | le fichier récupéré ET le partage jugé possible. À la moindre difficulté —
 | pas de `canShare`, réseau coupé, refus — on ne fait rien, et le lien
 | fonctionne exactement comme avant.
 */
function iOS(lien) {
    if (typeof navigator.canShare !== 'function' || typeof navigator.share !== 'function') {
        return;
    }

    lien.addEventListener('click', async (evenement) => {
        let fichier;

        try {
            const reponse = await fetch(lien.href, { headers: { Accept: 'text/vcard' } });

            if (!reponse.ok) {
                return;
            }

            const texte = await reponse.text();

            fichier = new File(
                [texte],
                (lien.dataset.nom || 'contact').replace(/\s+/g, '-').toLowerCase() + '.vcf',
                { type: 'text/vcard' }
            );
        } catch {
            return;   // le lien suit son cours : comportement d'avant
        }

        if (!navigator.canShare({ files: [fichier] })) {
            return;
        }

        /*
         | LE preventDefault N'ARRIVE QU'ICI, et c'est délibéré.
         |
         | À ce point seulement on sait que le fichier existe et que le
         | système accepte de le partager. L'annuler plus tôt aurait laissé
         | l'utilisateur sans rien si l'une des deux conditions manquait.
         |
         | Safari exige que `share` parte d'un geste de l'utilisateur. Le
         | `await` du fetch consomme ce geste sur certaines versions ; le
         | repli reste alors le lien, que l'on n'a pas encore annulé.
         */
        evenement.preventDefault();

        try {
            await navigator.share({ files: [fichier], title: lien.dataset.nom });
        } catch {
            // Annulation ou refus : la page reste où elle est, la carte
            // entière est toujours à l'écran. Rien à dire.
        }
    });
}
