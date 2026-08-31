// =============================================================================
// SELECT RICHE — habiller la liste déroulante, sur BUREAU seulement.
//
// LE PROBLÈME QU'IL RÉSOUT
// La liste ouverte d'un <select> est dessinée par le SYSTÈME D'EXPLOITATION,
// pas par la page. Aucune feuille de style ne peut l'atteindre : on ne peut
// imposer aux <option> que `background` et `color`, rien d'autre. Sous
// Windows, cela donne une liste en surbrillance bleue système, dans la police
// du système, à angles vifs — au milieu d'une interface qui n'a ni ce bleu,
// ni cette police, ni ces angles.
//
// POURQUOI SEULEMENT SUR BUREAU
// Sur téléphone, le sélecteur natif est un panneau plein écran ou une roue.
// Il est MEILLEUR que tout ce qu'on redessinerait : plus grand, utilisable au
// pouce, connu de l'utilisateur, et accessible sans effort. Le remplacer y
// serait une régression déguisée en amélioration.
//
// La bascule se fait donc sur `(hover: hover) and (pointer: fine)` — la
// présence d'un vrai curseur — et non sur une largeur d'écran : une tablette
// large reste tactile, un petit portable reste au curseur.
//
// DÉGRADATION SANS JAVASCRIPT
// Le <select> natif reste dans le HTML, inchangé, et demeure la source de
// vérité du formulaire : c'est LUI qui porte le nom, la valeur, la validation
// et l'envoi. Si ce fichier ne se charge pas, on obtient exactement ce qu'on
// avait avant — une liste native qui fonctionne. Rien ne dépend du script.
//
// CE QUI EST DÉLIBÉRÉMENT ÉCARTÉ
// Les listes très longues (le sélecteur d'indicatif pays et ses ~200 entrées)
// gardent le natif : une liste maison de 200 lignes sans recherche est pire
// que la native, qui accepte au moins la frappe rapide du système.
// =============================================================================

const CLASSE_OUVERT = 'is-ouvert';

/** Le seuil au-delà duquel on laisse le natif faire son travail. */
const MAX_OPTIONS = 40;

export default function selectRiche() {
    const bureau = window.matchMedia('(hover: hover) and (pointer: fine)');

    if (!bureau.matches) {
        return;
    }

    const selects = document.querySelectorAll('select:not([multiple]):not([data-natif])');

    if (selects.length === 0) {
        return;
    }

    selects.forEach((select) => {
        if (select.options.length === 0 || select.options.length > MAX_OPTIONS) {
            return;
        }

        habiller(select);
    });
}

function habiller(select) {
    const enveloppe = document.createElement('div');
    enveloppe.className = 'selr';

    /*
     | LE NATIF RESTE, MAIS SORT DU PARCOURS AU CLAVIER.
     |
     | On ne le retire pas du DOM : il porte le name, la valeur et la
     | validation du navigateur. On le masque visuellement et on lui pose
     | `tabindex="-1"` pour qu'il n'y ait pas DEUX arrêts de tabulation au
     | même endroit — un piège classique : l'utilisateur au clavier tombe sur
     | un contrôle invisible et croit que le focus a disparu.
     |
     | `aria-hidden` n'est PAS posé : le champ reste associé à son <label>, et
     | c'est ce label que la liste maison reprend.
     */
    select.classList.add('selr__natif');
    select.tabIndex = -1;

    const declencheur = document.createElement('button');
    declencheur.type = 'button';
    declencheur.className = 'selr__declencheur';
    declencheur.setAttribute('role', 'combobox');
    declencheur.setAttribute('aria-expanded', 'false');
    declencheur.setAttribute('aria-haspopup', 'listbox');

    if (select.id) {
        declencheur.setAttribute('aria-labelledby', `${select.id}-etiquette ${select.id}-valeur`);
    }

    const valeur = document.createElement('span');
    valeur.className = 'selr__valeur';
    if (select.id) {
        valeur.id = `${select.id}-valeur`;
    }

    const chevron = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    chevron.setAttribute('class', 'selr__chevron');
    chevron.setAttribute('viewBox', '0 0 24 24');
    chevron.setAttribute('fill', 'none');
    chevron.setAttribute('stroke', 'currentColor');
    chevron.setAttribute('stroke-width', '2.4');
    chevron.setAttribute('stroke-linecap', 'round');
    chevron.setAttribute('stroke-linejoin', 'round');
    chevron.setAttribute('aria-hidden', 'true');
    chevron.innerHTML = '<path d="m6 9 6 6 6-6"/>';

    declencheur.append(valeur, chevron);

    const liste = document.createElement('div');
    liste.className = 'selr__liste';
    liste.setAttribute('role', 'listbox');
    liste.hidden = true;

    select.parentNode.insertBefore(enveloppe, select);
    enveloppe.append(select, declencheur, liste);

    // ── Construction des options ─────────────────────────────────────────
    const options = [];

    Array.from(select.children).forEach((noeud) => {
        if (noeud.tagName === 'OPTGROUP') {
            const titre = document.createElement('div');
            titre.className = 'selr__groupe';
            titre.setAttribute('role', 'presentation');
            titre.textContent = noeud.label;
            liste.append(titre);

            Array.from(noeud.children).forEach((o) => options.push(creerOption(o, liste)));

            return;
        }

        if (noeud.tagName === 'OPTION') {
            options.push(creerOption(noeud, liste));
        }
    });

    let index = Math.max(0, options.findIndex((o) => o.natif.selected));

    // ── Synchronisation ──────────────────────────────────────────────────
    const rafraichir = () => {
        const choisi = options.find((o) => o.natif.selected) || options[0];

        valeur.textContent = choisi ? choisi.natif.textContent.trim() : '';
        valeur.classList.toggle('is-vide', Boolean(choisi?.natif.disabled));

        options.forEach((o) => {
            const actif = o.natif.selected;
            o.bouton.setAttribute('aria-selected', actif ? 'true' : 'false');
            o.bouton.classList.toggle('is-choisi', actif);
        });
    };

    const ouvrir = () => {
        liste.hidden = false;
        enveloppe.classList.add(CLASSE_OUVERT);
        declencheur.setAttribute('aria-expanded', 'true');

        index = Math.max(0, options.findIndex((o) => o.natif.selected));
        surligner(index);
        options[index]?.bouton.focus();
    };

    const fermer = ({ rendreFocus = true } = {}) => {
        liste.hidden = true;
        enveloppe.classList.remove(CLASSE_OUVERT);
        declencheur.setAttribute('aria-expanded', 'false');

        if (rendreFocus) {
            declencheur.focus();
        }
    };

    const surligner = (i) => {
        index = (i + options.length) % options.length;
        options.forEach((o, n) => o.bouton.classList.toggle('is-survol', n === index));
        options[index].bouton.scrollIntoView({ block: 'nearest' });
    };

    const choisir = (i) => {
        const o = options[i];

        if (!o || o.natif.disabled) {
            return;
        }

        select.value = o.natif.value;

        /*
         | « change » ET « input », tous deux à bulles.
         |
         | Le module auto-filtre écoute `change` sur le <select> pour soumettre
         | la liste. Sans cet événement, choisir une période dans la liste
         | habillée ne déclencherait plus rien : le filtre paraîtrait cassé,
         | et par la faute de l'habillage.
         */
        select.dispatchEvent(new Event('input', { bubbles: true }));
        select.dispatchEvent(new Event('change', { bubbles: true }));

        rafraichir();
        fermer();
    };

    // ── Interactions ─────────────────────────────────────────────────────
    declencheur.addEventListener('click', () => (liste.hidden ? ouvrir() : fermer()));

    declencheur.addEventListener('keydown', (e) => {
        if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(e.key)) {
            e.preventDefault();
            ouvrir();
        }
    });

    options.forEach((o, i) => {
        o.bouton.addEventListener('click', () => choisir(i));
        o.bouton.addEventListener('mousemove', () => surligner(i));
    });

    liste.addEventListener('keydown', (e) => {
        switch (e.key) {
            case 'ArrowDown': e.preventDefault(); surligner(index + 1); options[index].bouton.focus(); break;
            case 'ArrowUp': e.preventDefault(); surligner(index - 1); options[index].bouton.focus(); break;
            case 'Home': e.preventDefault(); surligner(0); options[index].bouton.focus(); break;
            case 'End': e.preventDefault(); surligner(options.length - 1); options[index].bouton.focus(); break;
            case 'Escape': e.preventDefault(); fermer(); break;
            case 'Tab': fermer({ rendreFocus: false }); break;
            default: break;
        }
    });

    // Un clic hors du champ referme, comme n'importe quel menu.
    document.addEventListener('click', (e) => {
        if (!liste.hidden && !enveloppe.contains(e.target)) {
            fermer({ rendreFocus: false });
        }
    });

    /*
     | LE NATIF PEUT ENCORE CHANGER SANS NOUS.
     |
     | `telephone.js` écrit dans le select du pays, une réinitialisation de
     | formulaire remet les valeurs d'origine, et le retour arrière du
     | navigateur restaure l'état précédent. Sans cette écoute, l'habillage
     | afficherait alors une valeur périmée — le pire défaut possible pour un
     | champ : montrer autre chose que ce qui sera envoyé.
     */
    select.addEventListener('change', rafraichir);

    rafraichir();
}

function creerOption(natif, liste) {
    const bouton = document.createElement('button');

    bouton.type = 'button';
    bouton.className = 'selr__option';
    bouton.setAttribute('role', 'option');
    bouton.tabIndex = -1;
    bouton.textContent = natif.textContent.trim();

    if (natif.disabled) {
        bouton.classList.add('is-inactif');
        bouton.disabled = true;
    }

    liste.append(bouton);

    return { natif, bouton };
}
