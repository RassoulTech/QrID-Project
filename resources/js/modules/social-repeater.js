/**
 * Ajout et suppression de lignes de réseaux sociaux, sans rechargement.
 *
 * SANS JAVASCRIPT : le bouton « Ajouter un réseau » reste un submit ; le
 * serveur renvoie la même étape avec une ligne de plus, saisies conservées.
 * La suppression se fait en vidant les deux champs : le serveur ignore
 * silencieusement toute ligne incomplète.
 */
export default function socialRepeater() {
    const list = document.querySelector('[data-socials]');
    const template = document.querySelector('[data-social-template]');
    const addBtn = document.querySelector('[data-social-add]');
    if (!list || !template || !addBtn) return;

    const max = parseInt(list.dataset.max || '6', 10);

    // Le bouton cesse d'être un submit : le rechargement n'est plus utile.
    addBtn.setAttribute('type', 'button');
    addBtn.removeAttribute('name');
    addBtn.removeAttribute('value');

    const rows = () => list.querySelectorAll('[data-social-row]');

    // Les indices doivent rester contigus : socials[0], socials[1]…
    const reindex = () => {
        rows().forEach((row, i) => {
            row.querySelectorAll('[name]').forEach((field) => {
                field.name = field.name.replace(/socials\[\d+\]/, `socials[${i}]`);
            });
        });
        addBtn.hidden = rows().length >= max;
    };

    addBtn.addEventListener('click', () => {
        if (rows().length >= max) return;

        const fragment = template.content.cloneNode(true);
        const row = fragment.querySelector('[data-social-row]');

        row.querySelectorAll('[name]').forEach((field) => {
            field.name = field.name.replace('__i__', String(rows().length));
        });

        list.appendChild(fragment);
        reindex();
        list.lastElementChild.querySelector('select')?.focus();
    });

    list.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-social-remove]');
        if (!btn) return;

        // Jamais zéro ligne : on vide la dernière au lieu de la retirer.
        if (rows().length === 1) {
            btn.closest('[data-social-row]')
                .querySelectorAll('select, input')
                .forEach((field) => { field.value = ''; });
            return;
        }

        btn.closest('[data-social-row]').remove();
        reindex();
    });

    reindex();
}
