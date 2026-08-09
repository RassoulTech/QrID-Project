/**
 * Compteur de caractères sous un champ texte.
 *
 * Activation :
 *   <textarea name="bio" maxlength="500" data-counter="bioCount"></textarea>
 *   <span id="bioCount" class="form-text"></span>
 *
 * SANS JAVASCRIPT : l'attribut natif `maxlength` limite déjà la saisie, et
 * la règle `max:500` du Form Request tranche côté serveur. Seul l'affichage
 * du décompte disparaît.
 *
 * Le serveur valide toujours : ce compteur n'est qu'un confort de saisie.
 */
export default function charCounter() {
    const fields = document.querySelectorAll('[data-counter]');
    if (!fields.length) return;

    fields.forEach((field) => {
        const output = document.getElementById(field.dataset.counter);
        const max = field.getAttribute('maxlength');
        if (!output || !max) return;

        const render = () => {
            output.textContent = `${field.value.length} / ${max} caractères`;
        };

        field.addEventListener('input', render);
        render();
    });
}
