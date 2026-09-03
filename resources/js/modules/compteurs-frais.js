/*
 | LES COMPTEURS SE RAFRAÎCHISSENT AU RETOUR SUR L'ONGLET.
 |
 | ═══════════════════════════════════════════════════════════════════════════
 | LE MOMENT UTILE EST PRÉCIS
 | ═══════════════════════════════════════════════════════════════════════════
 | Le client publie sa carte, prend son téléphone, scanne son propre QR Code
 | pour vérifier que ça marche — puis revient sur son ordinateur. C'est à cet
 | instant, et à aucun autre, que le compteur doit avoir bougé. Aujourd'hui il
 | faut recharger la page, et beaucoup concluent que rien n'a été compté.
 |
 | Un `setInterval` de trente secondes interrogerait le serveur toute la
 | journée, écran verrouillé compris, pour un chiffre que personne ne regarde.
 | La Page Visibility API dit exactement quand l'onglet redevient visible :
 | zéro requête pendant qu'on ne regarde pas, un chiffre juste au retour.
 |
 | ═══════════════════════════════════════════════════════════════════════════
 | IL NE DOIT JAMAIS ABÎMER LA PAGE
 | ═══════════════════════════════════════════════════════════════════════════
 | Aucune erreur affichée, aucun état de chargement, aucun clignotement. Si
 | la requête échoue — hors ligne, session expirée, serveur endormi — les
 | chiffres déjà à l'écran restent tels quels. Ils sont justes au moment du
 | rendu ; simplement, ils ne sont plus frais. C'est infiniment préférable à
 | un message d'erreur pour un rafraîchissement que personne n'a demandé.
 */

/** Trente secondes entre deux appels, quel que soit le nombre d'aller-retours. */
const REPOS_MS = 30_000;

let dernierAppel = 0;

/**
 * Écrit un nombre dans sa tuile, en respectant le format français.
 *
 * `Intl` plutôt qu'un remplacement à la main : l'espace des milliers est une
 * espace insécable étroite, et la taper au clavier donne une espace normale
 * qui casse le nombre en fin de ligne.
 */
function ecrire(tuile, valeur) {
    const cible = tuile.querySelector('[data-compteur-valeur]');

    if (!cible) {
        return;
    }

    const formate = new Intl.NumberFormat(document.documentElement.lang || 'fr').format(valeur);

    if (cible.textContent.trim() === formate) {
        return;   // rien n'a changé : ne pas toucher au DOM
    }

    cible.textContent = formate;

    /*
     | LA TUILE D'ATTENTE DISPARAÎT DÈS LE PREMIER ÉVÉNEMENT.
     |
     | « Partagez votre carte pour voir arriver vos premières vues » n'a plus
     | de sens à côté d'un « 1 ». La laisser afficherait deux messages
     | contradictoires sur la même tuile.
     */
    if (valeur > 0) {
        tuile.querySelector('[data-compteur-attente]')?.remove();
    }
}

async function rafraichir(url) {
    const maintenant = Date.now();

    if (maintenant - dernierAppel < REPOS_MS) {
        return;
    }

    dernierAppel = maintenant;

    let donnees;

    try {
        const reponse = await fetch(url, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });

        if (!reponse.ok) {
            return;   // session expirée, cadence dépassée : on ne dit rien
        }

        donnees = await reponse.json();
    } catch {
        return;   // hors ligne : les chiffres à l'écran restent valables
    }

    const compteurs = donnees?.compteurs;

    if (!compteurs) {
        return;
    }

    document.querySelectorAll('[data-compteur]').forEach((tuile) => {
        const cle = tuile.getAttribute('data-compteur');

        if (typeof compteurs[cle] === 'number') {
            ecrire(tuile, compteurs[cle]);
        }
    });
}

export default function compteursFrais() {
    const grille = document.querySelector('[data-compteurs-url]');

    if (!grille) {
        return;
    }

    const url = grille.getAttribute('data-compteurs-url');

    if (!url) {
        return;
    }

    // Le premier rendu vient du serveur et est à jour : on part donc du
    // principe qu'un appel vient d'avoir lieu.
    dernierAppel = Date.now();

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            rafraichir(url);
        }
    });
}
