// =============================================================================
// LE SÉLECTEUR DE LANGUE — fermeture au clic extérieur et à Échap
// =============================================================================
//
// CE MODULE N'EST PAS NÉCESSAIRE AU SÉLECTEUR.
//
// <details>/<summary> ouvre et ferme nativement : au clic, au doigt, à la
// touche Entrée et à la barre d'espace. Sans ce fichier, le menu reste
// entièrement utilisable — il faut simplement recliquer sur le déclencheur
// pour le refermer.
//
// Le module ajoute les deux gestes qu'on attend d'un menu et que <details>
// ne connaît pas : cliquer AILLEURS le ferme, et Échap le ferme. Sans eux,
// un menu oublié ouvert reste posé sur la page pendant toute la visite.
//
// Un seul écouteur sur le document, quel que soit le nombre de sélecteurs
// dans la page : la barre de navigation et le panneau mobile en portent
// chacun un, et deux écouteurs par instance n'apporteraient rien.
// =============================================================================

export default function langue() {
    const menus = document.querySelectorAll('[data-langue]');

    if (menus.length === 0) {
        return;
    }

    const fermerTout = (sauf) => {
        menus.forEach((menu) => {
            if (menu !== sauf) {
                menu.open = false;
            }
        });
    };

    // Un clic hors de tout sélecteur les ferme tous. `closest` remonte
    // l'arbre : un clic SUR le menu — donc sur un bouton de langue — ne
    // déclenche pas la fermeture avant que le formulaire ne parte.
    document.addEventListener('click', (evenement) => {
        const dedans = evenement.target.closest('[data-langue]');

        fermerTout(dedans);
    });

    document.addEventListener('keydown', (evenement) => {
        if (evenement.key !== 'Escape') {
            return;
        }

        menus.forEach((menu) => {
            if (menu.open) {
                menu.open = false;

                // Le focus revient au déclencheur : sans cela, Échap laisse
                // le clavier au milieu de nulle part et la tabulation
                // suivante repart du haut de la page.
                menu.querySelector('summary')?.focus();
            }
        });
    });
}
