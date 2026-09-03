/*
 | LE PARTAGE EST COMPTÉ, ET RIEN D'AUTRE NE L'EST.
 |
 | ═══════════════════════════════════════════════════════════════════════════
 | CE QUI PART VERS LE SERVEUR
 | ═══════════════════════════════════════════════════════════════════════════
 | Un mot : « whatsapp », « natif », « copie » ou « qr ». Pas le message —
 | il se compose dans WhatsApp, l'application ne le voit jamais. Pas le
 | destinataire — elle ne le connaît pas davantage.
 |
 | Ce qui est enregistré est donc un partage INITIÉ, jamais un message envoyé.
 | La distinction n'est pas de la prudence rédactionnelle : c'est la limite
 | réelle de ce qu'un navigateur peut observer.
 |
 | ═══════════════════════════════════════════════════════════════════════════
 | POURQUOI `sendBeacon` ET NON `fetch`
 | ═══════════════════════════════════════════════════════════════════════════
 | Le clic ouvre WhatsApp, donc quitte la page. Une requête `fetch` en cours
 | est ANNULÉE quand le document se décharge : le compteur ne recevrait rien,
 | et seulement sur les partages qui aboutissent — c'est-à-dire ceux qu'on
 | veut justement compter.
 |
 | `sendBeacon` est fait pour ce cas : le navigateur prend l'envoi en charge
 | et le termine après le départ de la page. Il ne rend aucune réponse, ce
 | qui tombe bien — on n'en attend aucune.
 |
 | ═══════════════════════════════════════════════════════════════════════════
 | IL NE DOIT JAMAIS RETARDER NI EMPÊCHER LE PARTAGE
 | ═══════════════════════════════════════════════════════════════════════════
 | Aucun `preventDefault`, aucune attente. Le lien s'ouvre, le compteur suit.
 | Un compteur ne vaut pas un partage perdu : si l'envoi échoue, si le
 | navigateur ne connaît pas `sendBeacon` — on renonce en silence. C'est le même arbitrage que côté serveur, où l'échec d'écriture
 | d'un événement n'a pas le droit d'empêcher une carte de s'afficher.
 */

function signaler(url, canal) {
    // `sendBeacon` manque sur les navigateurs anciens. Le partage, lui,
    // fonctionne quand même : c'est la mesure qu'on perd, pas la fonction.
    if (typeof navigator.sendBeacon !== 'function') {
        return;
    }

    /*
     | AUCUN JETON CSRF : la route en est exemptée, et délibérément. La
     | carte publique ne porte pas de balise csrf-token — elle doit rester
     | cachable et sans session — et un compteur anonyme n'a rien à
     | protéger contre une falsification de requête. La limite de cadence
     | côté serveur est le contrôle qui vaut ici.
     */
    const corps = new FormData();
    corps.append('canal', canal);

    try {
        navigator.sendBeacon(url, corps);
    } catch {
        // Renoncer en silence : voir l'en-tête de ce fichier.
    }
}

export default function suiviPartage() {
    const zone = document.querySelector('[data-partage-url]');

    if (!zone) {
        return;
    }

    const url = zone.getAttribute('data-partage-url');

    if (!url) {
        return;
    }

    /*
     | UN SEUL ÉCOUTEUR, POSÉ SUR LE DOCUMENT.
     |
     | Les boutons de partage vivent dans une feuille qui s'ouvre et se
     | ferme ; certains sont révélés par JavaScript quand le partage natif
     | existe. Attacher un écouteur à chacun au chargement en manquerait.
     */
    document.addEventListener('click', (evenement) => {
        const cible = evenement.target instanceof Element
            ? evenement.target.closest('[data-partage-canal]')
            : null;

        if (!cible) {
            return;
        }

        signaler(url, cible.getAttribute('data-partage-canal'));
    });
}
