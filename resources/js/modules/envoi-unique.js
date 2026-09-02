/*
 | UN FORMULAIRE NE PART QU'UNE FOIS.
 |
 | ═══════════════════════════════════════════════════════════════════════════
 | LE PROBLÈME
 | ═══════════════════════════════════════════════════════════════════════════
 | Vingt-six formulaires, aucune protection. Sur une connexion lente — c'est
 | la norme et non l'exception pour ce produit — la page ne réagit pas dans
 | la seconde qui suit le clic. Le geste naturel est alors de recliquer.
 |
 | Le serveur reçoit deux envois : deux messages de contact identiques, deux
 | paiements en attente dont personne ne confirmera le second, deux relances.
 |
 | Le serveur reste la seule vraie garantie — un module JavaScript se
 | désactive, se charge en retard, ou ne se charge pas. Ce fichier ne le
 | remplace pas : il évite au client de provoquer le problème, et lui dit
 | surtout que quelque chose se passe.
 |
 | ═══════════════════════════════════════════════════════════════════════════
 | TROIS PIÈGES, ET LEUR TRAITEMENT
 | ═══════════════════════════════════════════════════════════════════════════
 | 1. DÉSACTIVER TROP TÔT PERD LE BOUTON.
 |    Un bouton `disabled` n'est pas envoyé avec le formulaire. Le désactiver
 |    dans le gestionnaire de `submit` ferait donc disparaître son name/value
 |    des données reçues. On attend la fin du cycle courant — le formulaire
 |    est déjà parti quand la désactivation prend effet.
 |
 | 2. LE BOUTON « PRÉCÉDENT » RESSUSCITE UNE PAGE MORTE.
 |    Le navigateur restaure la page depuis son cache mémoire, DANS L'ÉTAT où
 |    elle était : bouton désactivé, texte « Envoi… ». Le client revient sur
 |    son formulaire et ne peut plus rien envoyer. `pageshow` remet tout en
 |    état — c'est la moitié du travail que ce fichier fait réellement.
 |
 | 3. UN FORMULAIRE INVALIDE NE PART PAS.
 |    Si la validation native du navigateur refuse l'envoi, l'événement
 |    `submit` n'est jamais émis — donc rien n'est désactivé. Mais un envoi
 |    annulé par un autre script, lui, émet `submit` puis l'annule : on
 |    vérifie `defaultPrevented` avant de verrouiller quoi que ce soit.
 */

const ETAT = 'data-envoi-en-cours';

/** Le texte affiché pendant l'envoi, lu depuis le document — jamais écrit ici. */
function libelleAttente(bouton) {
    return bouton.getAttribute('data-envoi-libelle')
        || document.documentElement.getAttribute('data-libelle-envoi')
        || '…';
}

function verrouiller(formulaire) {
    const boutons = formulaire.querySelectorAll(
        'button[type="submit"], button:not([type]), input[type="submit"]'
    );

    boutons.forEach((bouton) => {
        // La largeur est figée AVANT de changer le texte : sans cela le
        // bouton rétrécit ou s'élargit d'un coup, et la mise en page saute
        // sous le pouce au moment précis où l'on vient d'appuyer.
        const largeur = bouton.getBoundingClientRect().width;

        if (largeur > 0) {
            bouton.style.minWidth = `${Math.ceil(largeur)}px`;
        }

        const etiquette = bouton.querySelector('[data-envoi-texte]') || bouton;

        bouton.setAttribute('data-envoi-original', etiquette.textContent ?? '');
        etiquette.textContent = libelleAttente(bouton);

        bouton.disabled = true;
        bouton.setAttribute('aria-busy', 'true');
    });

    formulaire.setAttribute(ETAT, 'oui');
}

function deverrouiller(formulaire) {
    formulaire.removeAttribute(ETAT);

    formulaire.querySelectorAll('[data-envoi-original]').forEach((bouton) => {
        const etiquette = bouton.querySelector('[data-envoi-texte]') || bouton;

        etiquette.textContent = bouton.getAttribute('data-envoi-original') ?? '';

        bouton.removeAttribute('data-envoi-original');
        bouton.removeAttribute('aria-busy');
        bouton.style.minWidth = '';
        bouton.disabled = false;
    });
}

export default function envoiUnique() {
    document.addEventListener('submit', (evenement) => {
        const formulaire = evenement.target;

        if (!(formulaire instanceof HTMLFormElement)) {
            return;
        }

        // Un formulaire qui s'exclut explicitement — une recherche qui se
        // soumet à chaque frappe, par exemple — n'a rien à verrouiller.
        if (formulaire.hasAttribute('data-envoi-libre')) {
            return;
        }

        // Déjà parti : on empêche le second envoi plutôt que de le laisser
        // passer et de compter sur le serveur pour le rattraper.
        if (formulaire.hasAttribute(ETAT)) {
            evenement.preventDefault();

            return;
        }

        // Annulé par un autre gestionnaire (confirmation refusée, validation
        // maison) : rien n'est parti, il n'y a rien à verrouiller.
        if (evenement.defaultPrevented) {
            return;
        }

        // Après le cycle courant : le formulaire est parti, le bouton peut
        // maintenant être désactivé sans que sa valeur manque à l'envoi.
        window.setTimeout(() => verrouiller(formulaire), 0);
    });

    /*
     | LE RETOUR ARRIÈRE.
     |
     | `persisted` distingue une page restaurée depuis le cache mémoire d'un
     | chargement normal. Dans le premier cas, l'état du formulaire est celui
     | qu'on avait laissé — verrouillé — alors que la navigation est terminée.
     |
     | On déverrouille dans les deux cas : sur un chargement normal il n'y a
     | rien de verrouillé, et l'appel ne coûte rien.
     */
    window.addEventListener('pageshow', () => {
        document.querySelectorAll(`form[${ETAT}]`).forEach(deverrouiller);
    });
}
