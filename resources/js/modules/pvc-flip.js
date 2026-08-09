/**
 * Permutation recto / verso de la carte PVC.
 *
 * Activation :
 *   <div class="pvc" data-pvc>
 *     <div class="pvc__scene"> … deux .pvc__face … </div>
 *     <div class="pvc__commande" data-pvc-commande hidden>
 *       <button data-pvc-toggle><span data-pvc-label>…</span></button>
 *
 * SANS JAVASCRIPT : les deux faces restent affichées L'UNE SOUS L'AUTRE et le
 * bouton n'apparaît jamais (il porte [hidden] dans le HTML). L'utilisateur
 * voit donc TOUT — recto, verso, QR Code, lien — sans aucune interaction.
 *
 * C'est pour cela que la bascule 3D est portée par la classe .is-flippable,
 * ajoutée ICI : si ce fichier ne se charge pas, le CSS n'a aucune raison de
 * masquer une face, et rien ne disparaît.
 */
export default function pvcFlip() {
    const cartes = document.querySelectorAll('[data-pvc]');
    if (!cartes.length) return;

    cartes.forEach((carte) => {
        const bouton = carte.querySelector('[data-pvc-toggle]');
        const commande = carte.querySelector('[data-pvc-commande]');
        const etiquette = carte.querySelector('[data-pvc-label]');

        if (!bouton || !commande) return;

        // Le script est là : on peut empiler les faces et révéler la commande.
        carte.classList.add('is-flippable');
        commande.hidden = false;

        bouton.addEventListener('click', () => {
            const versoVisible = carte.classList.toggle('is-flipped');

            bouton.setAttribute('aria-pressed', versoVisible ? 'true' : 'false');

            if (etiquette) {
                etiquette.textContent = versoVisible ? 'Voir le recto' : 'Voir le verso';
            }
        });
    });
}
