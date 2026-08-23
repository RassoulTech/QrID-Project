/**
 * Aperçu d'une image avant envoi + dépôt par glisser-déposer.
 *
 * SANS JAVASCRIPT : le champ fichier reste un champ fichier. L'utilisateur
 * choisit son image, elle part avec le formulaire, et il la voit à l'étape
 * suivante. Rien n'est bloqué, seul le confort disparaît.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * DEUX DÉPÔTS, UNE SEULE MÉCANIQUE
 * ═══════════════════════════════════════════════════════════════════════
 * L'étape 1 porte désormais un portrait ET une bannière de couverture. Ils
 * se comportent exactement pareil : choisir, glisser-déposer, prévisualiser,
 * refuser ce qui est trop lourd. Deux copies de ce fichier auraient divergé
 * à la première correction — c'est toujours la seconde qu'on oublie.
 *
 * Seuls changent le préfixe des attributs, le plafond et les libellés.
 */

const DEPOTS = [
    {
        prefixe: 'photo',
        maxOctets: 2 * 1024 * 1024,
        tropLourd: 'Photo trop lourde (2 Mo maximum)',
        changer: 'Changer la photo',
        alt: 'Aperçu de votre photo',
    },
    {
        prefixe: 'cover',
        maxOctets: 4 * 1024 * 1024,
        tropLourd: 'Bannière trop lourde (4 Mo maximum)',
        changer: 'Changer la bannière',
        alt: 'Aperçu de votre bannière',
    },
];

export default function photoPreview() {
    DEPOTS.forEach(brancher);
}

function brancher({ prefixe, maxOctets, tropLourd, changer, alt }) {
    const drop = document.querySelector(`[data-${prefixe}-drop]`);

    if (!drop) {
        return;
    }

    const input = drop.querySelector(`[data-${prefixe}-input]`);
    const thumb = drop.querySelector(`[data-${prefixe}-thumb]`);
    const label = drop.querySelector(`[data-${prefixe}-label]`);

    if (!input || !thumb) {
        return;
    }

    let objectUrl = null;

    const render = (file) => {
        if (!file || !file.type.startsWith('image/')) {
            return;
        }

        // Le poids est revalidé côté serveur : ce contrôle ne fait qu'éviter
        // à l'utilisateur un aller-retour inutile sur une connexion lente.
        if (file.size > maxOctets) {
            if (label) label.textContent = tropLourd;
            input.value = '';

            return;
        }

        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = URL.createObjectURL(file);

        thumb.innerHTML = '';

        const img = document.createElement('img');
        img.src = objectUrl;
        img.alt = alt;
        thumb.appendChild(img);

        if (label) label.textContent = changer;
    };

    input.addEventListener('change', () => render(input.files[0]));

    ['dragenter', 'dragover'].forEach((type) => {
        drop.addEventListener(type, (e) => {
            e.preventDefault();
            drop.classList.add('is-over');
        });
    });

    ['dragleave', 'drop'].forEach((type) => {
        drop.addEventListener(type, () => drop.classList.remove('is-over'));
    });

    drop.addEventListener('drop', (e) => {
        e.preventDefault();

        const file = e.dataTransfer?.files?.[0];

        if (!file) {
            return;
        }

        // On réinjecte le fichier dans le champ : c'est LUI qui sera envoyé.
        const transfer = new DataTransfer();
        transfer.items.add(file);
        input.files = transfer.files;

        render(file);
    });
}
