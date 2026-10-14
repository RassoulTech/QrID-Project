// =============================================================================
// LES MENUS DE LA COQUE — fermeture au clic extérieur, à Échap, un seul ouvert
// =============================================================================
//
// CE MODULE N'EST PAS NÉCESSAIRE AUX MENUS.
//
// Les menus de la coque (compte, notifications, recherche) sont des
// <details data-menu>. Ils s'ouvrent et se ferment nativement — au clic, au
// doigt, à Entrée et à la barre d'espace. Sans ce fichier, tout reste
// utilisable : on referme en recliquant sur le déclencheur.
//
// Le module ajoute les trois gestes qu'on attend d'un menu et que <details>
// ne connaît pas : cliquer AILLEURS ferme, Échap ferme (et rend le focus au
// déclencheur), ouvrir un menu ferme les autres.
//
// Un seul écouteur par type d'événement, quel que soit le nombre de menus.
// =============================================================================

export default function menus() {
    const tous = () => document.querySelectorAll('details[data-menu]');

    if (tous().length === 0) {
        return;
    }

    const fermerTout = (sauf = null) => {
        tous().forEach((menu) => {
            if (menu !== sauf && !menu.contains(sauf)) {
                menu.open = false;
            }
        });
    };

    // Ouvrir un menu ferme les autres — deux panneaux superposés se lisent
    // mal, et le second recouvre souvent le premier.
    tous().forEach((menu) => {
        menu.addEventListener('toggle', () => {
            if (menu.open) {
                fermerTout(menu);
            }
        });
    });

    // Un clic hors de tout menu les ferme tous. `closest` remonte l'arbre :
    // un clic DANS le menu — sur un lien, un bouton d'envoi — ne le ferme pas
    // avant que l'action ne parte.
    document.addEventListener('click', (evenement) => {
        fermerTout(evenement.target.closest('details[data-menu]'));
    });

    document.addEventListener('keydown', (evenement) => {
        if (evenement.key !== 'Escape') {
            return;
        }

        tous().forEach((menu) => {
            if (menu.open) {
                menu.open = false;

                // Le focus revient au déclencheur : sans cela, Échap laisse
                // le clavier au milieu de nulle part.
                menu.querySelector('summary')?.focus();
            }
        });
    });
}
