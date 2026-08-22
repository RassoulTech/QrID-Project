// =============================================================================
// LE CHAMP TÉLÉPHONE — drapeau, gabarit, espacement
// =============================================================================
//
// CE MODULE N'EST PAS NÉCESSAIRE AU FONCTIONNEMENT DU CHAMP.
//
// Le rendu serveur pose déjà le bon drapeau, le bon maxlength, le bon
// exemple et la bonne aide pour le pays sélectionné. Sans ce fichier, la
// saisie reste possible, la validation reste identique, rien ne casse — le
// champ cesse simplement de SUIVRE quand on change de pays.
//
// Il fait trois choses, et rien d'autre :
//
//   1. changer le drapeau visible quand le pays change ;
//   2. réappliquer le gabarit (longueur maximale, exemple, aide) ;
//   3. espacer les chiffres pendant la frappe — « 77 383 13 64 » plutôt que
//      « 773831364 », qui se relit chiffre par chiffre.
//
// L'ESPACEMENT N'ATTEINT JAMAIS LE SERVEUR. Les espaces sont retirés à la
// normalisation (IndicatifsPays::normaliser), qui ne garde que les chiffres.
// Ils existent pour l'œil du client pendant qu'il tape, pas pour la base.
// =============================================================================

export default function telephone() {
    const champs = document.querySelectorAll('[data-tel]');

    if (champs.length === 0) {
        return;
    }

    champs.forEach((champ) => {
        const pays = champ.querySelector('[data-tel-pays]');
        const numero = champ.querySelector('[data-tel-numero]');
        const drapeau = champ.querySelector('[data-tel-drapeau] use');
        const aide = champ.parentElement?.querySelector('[data-tel-aide]');

        if (!pays || !numero) {
            return;
        }

        let gabarits = {};

        try {
            gabarits = JSON.parse(champ.dataset.gabarits || '{}');
        } catch {
            // Un JSON illisible ne doit pas priver le client de son champ :
            // sans gabarit, on retombe sur la saisie libre du rendu serveur.
            return;
        }

        // ── Le découpage visuel ───────────────────────────────────────────
        const grouper = (chiffres, groupes) => {
            const morceaux = [];
            let reste = chiffres;

            groupes.forEach((taille) => {
                if (reste.length > 0) {
                    morceaux.push(reste.slice(0, taille));
                    reste = reste.slice(taille);
                }
            });

            // Les chiffres au-delà du gabarit ne sont pas jetés : un pays à
            // deux longueurs (le Bénin en a huit ou dix) doit pouvoir aller
            // au bout. Ils suivent, groupés par deux.
            while (reste.length > 0) {
                morceaux.push(reste.slice(0, 2));
                reste = reste.slice(2);
            }

            return morceaux.join(' ');
        };

        const gabaritCourant = () => gabarits[pays.value] || null;

        // ── 1 & 2. Le pays change ─────────────────────────────────────────
        const appliquer = (viderLeNumero) => {
            const gabarit = gabaritCourant();

            if (drapeau) {
                drapeau.setAttribute('href', `#drapeau-${pays.value}`);
            }

            if (!gabarit) {
                return;
            }

            numero.placeholder = gabarit.exemple;
            numero.maxLength = gabarit.max + gabarit.groupes.length - 1;

            if (aide && aide.dataset.telAide !== 'fige') {
                aide.textContent = gabarit.aide;
            }

            /*
             | LE NUMÉRO EST VIDÉ QUAND ON CHANGE DE PAYS.
             |
             | Un numéro sénégalais à neuf chiffres laissé en place sous un
             | indicatif ivoirien à dix devient un numéro qui n'appelle
             | personne — et rien à l'écran ne le signale. Le vider dit
             | clairement qu'il faut le ressaisir.
             |
             | Au premier passage (chargement de page), on ne vide rien :
             | ce serait effacer la valeur enregistrée du client.
             */
            if (viderLeNumero) {
                numero.value = '';
            } else {
                numero.value = grouper(numero.value.replace(/\D+/g, ''), gabarit.groupes);
            }
        };

        // ── 3. L'espacement pendant la frappe ─────────────────────────────
        const espacer = () => {
            const gabarit = gabaritCourant();

            if (!gabarit) {
                return;
            }

            /*
             | LE CURSEUR EST REPOSÉ À SA PLACE.
             |
             | Réécrire value remet par défaut le curseur à la fin. Corriger
             | un chiffre au milieu d'un numéro renverrait donc la frappe à
             | la fin du champ à chaque touche. On compte les chiffres à
             | gauche du curseur, puis on retrouve la position qui en laisse
             | autant après regroupement.
             */
            const avant = numero.selectionStart ?? numero.value.length;
            const chiffresAGauche = numero.value.slice(0, avant).replace(/\D+/g, '').length;

            numero.value = grouper(numero.value.replace(/\D+/g, ''), gabarit.groupes);

            let vus = 0;
            let position = numero.value.length;

            for (let i = 0; i < numero.value.length; i += 1) {
                if (/\d/.test(numero.value[i])) {
                    vus += 1;
                }

                if (vus === chiffresAGauche) {
                    position = i + 1;
                    break;
                }
            }

            if (chiffresAGauche === 0) {
                position = 0;
            }

            numero.setSelectionRange(position, position);
        };

        pays.addEventListener('change', () => appliquer(true));
        numero.addEventListener('input', espacer);

        appliquer(false);
    });
}
