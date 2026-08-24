/**
 * Permutation recto / verso de la carte.
 *
 * SANS JAVASCRIPT : les deux faces restent affichées L'UNE SOUS L'AUTRE et le
 * bouton n'apparaît jamais — il porte [hidden] dans le HTML. Le client voit
 * donc TOUT, sans aucune interaction.
 *
 * C'est pour cela que la mise en scène 3D est portée par .is-flippable,
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
 * La carte n'est qu'un raccourci, et elle DÉLÈGUE au bouton plutôt que de
 * dupliquer la bascule — sans quoi deux sources de vérité finiraient par se
 * désynchroniser.
 */
export default function cardDuo() {
    const duos = document.querySelectorAll('[data-card-duo]');

    if (!duos.length) {
        return;
    }

    duos.forEach((duo) => {
        const bouton = duo.querySelector('[data-card-duo-toggle]');
        const commande = duo.querySelector('[data-card-duo-commande]');
        const etiquette = duo.querySelector('[data-card-duo-label]');
        const scene = duo.querySelector('.card-duo__scene');

        if (!bouton || !commande) {
            return;
        }

        // Le script est là : on peut empiler les faces et révéler la commande.
        duo.classList.add('is-flippable');
        commande.hidden = false;

        bouton.addEventListener('click', () => {
            const versoVisible = duo.classList.toggle('is-flipped');

            bouton.setAttribute('aria-pressed', versoVisible ? 'true' : 'false');

            if (etiquette) {
                etiquette.textContent = versoVisible ? 'Voir le recto' : 'Voir le verso';
            }
        });

        if (!scene) {
            return;
        }

        scene.addEventListener('click', (e) => {
            /*
             | On ne détourne JAMAIS un clic destiné à autre chose : un clic
             | sur un lien doit suivre ce lien, et une sélection de texte ne
             | doit pas se terminer par un retournement — ce qui rendrait
             | toute copie impossible.
             */
            if (e.target.closest('a, button')) {
                return;
            }

            const selection = window.getSelection();

            if (selection && selection.toString().length > 0) {
                return;
            }

            bouton.click();
        });
    });
}
