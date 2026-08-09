/**
 * Aperçu de la photo avant envoi + dépôt par glisser-déposer.
 *
 * SANS JAVASCRIPT : le champ fichier reste un champ fichier. L'utilisateur
 * choisit son image, elle part avec le formulaire, et il la voit à l'étape
 * suivante. Rien n'est bloqué, seul le confort disparaît.
 */
const MAX_BYTES = 2 * 1024 * 1024;

export default function photoPreview() {
    const drop = document.querySelector('[data-photo-drop]');
    if (!drop) return;

    const input = drop.querySelector('[data-photo-input]');
    const thumb = drop.querySelector('[data-photo-thumb]');
    const label = drop.querySelector('[data-photo-label]');
    if (!input || !thumb) return;

    let objectUrl = null;

    const render = (file) => {
        if (!file || !file.type.startsWith('image/')) return;

        // Le poids est revalidé côté serveur : ce contrôle ne fait qu'éviter
        // à l'utilisateur un aller-retour inutile sur une connexion lente.
        if (file.size > MAX_BYTES) {
            label.textContent = 'Photo trop lourde (2 Mo maximum)';
            input.value = '';
            return;
        }

        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = URL.createObjectURL(file);

        thumb.innerHTML = '';
        const img = document.createElement('img');
        img.src = objectUrl;
        img.alt = 'Aperçu de votre photo';
        thumb.appendChild(img);

        if (label) label.textContent = 'Changer la photo';
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
        if (!file) return;

        // On réinjecte le fichier dans le champ : c'est LUI qui sera envoyé.
        const transfer = new DataTransfer();
        transfer.items.add(file);
        input.files = transfer.files;

        render(file);
    });
}
