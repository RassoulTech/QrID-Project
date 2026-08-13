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
 *
 * ═══════════════════════════════════════════════════════════════════════
 * DEUX CHEMINS VERS LA MÊME ACTION, ET UN SEUL EST ACCESSIBLE
 * ═══════════════════════════════════════════════════════════════════════
 * La CARTE est cliquable : c'est le geste attendu, on retourne une carte en
 * la touchant. Mais elle reste un <div> — sans rôle, sans état annoncé, hors
 * du parcours de tabulation.
 *
 * Le BOUTON demeure donc le chemin de référence : c'est lui qui porte
 * aria-pressed, lui dont l'étiquette change, lui qu'atteint la tabulation.
 * La carte n'est qu'un raccourci à la souris et au doigt, et elle DÉLÈGUE au
 * bouton plutôt que de dupliquer la bascule — sans quoi deux sources de
 * vérité finiraient par se désynchroniser.
 */
export default function pvcFlip() {
    const cartes = document.querySelectorAll('[data-pvc]');
    if (!cartes.length) return;

    cartes.forEach((carte) => {
        const bouton = carte.querySelector('[data-pvc-toggle]');
        const commande = carte.querySelector('[data-pvc-commande]');
        const etiquette = carte.querySelector('[data-pvc-label]');
        const scene = carte.querySelector('.pvc__scene');

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

        if (!scene) return;

        scene.addEventListener('click', (e) => {
            /*
             | On ne détourne JAMAIS un clic destiné à autre chose.
             |
             | La carte contient des liens et du texte sélectionnable. Un clic
             | sur un lien du verso doit suivre ce lien ; une sélection de
             | texte ne doit pas se terminer par un retournement, ce qui rend
             | toute copie impossible.
             */
            if (e.target.closest('a, button')) return;

            const selection = window.getSelection();
            if (selection && selection.toString().length > 0) return;

            bouton.click();
        });
    });
}
