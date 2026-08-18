// =============================================================================
// AUTO-FILTRE — soumettre la liste dès qu'un choix change.
//
// LE PROBLÈME QU'IL RÉSOUT
// Sur les listes filtrables, choisir « 30 derniers jours » dans un menu ne
// produisait RIEN tant qu'on n'avait pas cliqué « Filtrer ». Le geste naturel
// — je choisis, je regarde le résultat — ne donnait aucun retour, et l'écran
// laissait croire que le filtre ne fonctionnait pas.
//
// DÉGRADATION SANS JAVASCRIPT
// Le bouton « Filtrer » reste dans le formulaire et fait exactement le même
// travail. Si ce fichier ne se charge pas, la liste se filtre toujours — un
// clic de plus, rien de cassé. C'est la règle du projet : le JavaScript
// améliore, il ne porte jamais.
//
// POURQUOI SEULEMENT LES MENUS ET LES DATES
// Un champ de recherche libre soumis à chaque frappe rechargerait la page à
// chaque lettre. Il garde donc son bouton, et sa touche Entrée.
// =============================================================================

export default function autoFiltre() {
    const formulaires = document.querySelectorAll('[data-auto-filtre]');

    if (formulaires.length === 0) {
        return;
    }

    formulaires.forEach((formulaire) => {
        const champs = formulaire.querySelectorAll('select, input[type="date"]');

        champs.forEach((champ) => {
            champ.addEventListener('change', () => {
                /*
                 | ON REPART TOUJOURS DE LA PAGE 1.
                 |
                 | Sans cela, filtrer depuis la page 3 conserve « page=3 » : on
                 | obtient une liste vide sur un filtre qui a pourtant des
                 | résultats, et l'on conclut que le filtre est cassé.
                 */
                const page = formulaire.querySelector('[name="page"]');

                if (page) {
                    page.remove();
                }

                formulaire.submit();
            });
        });

        /*
         | LE BOUTON RESTE, MAIS S'EFFACE.
         |
         | Il demeure dans le HTML pour qui n'a pas de JavaScript, et pour la
         | navigation au clavier. On le masque seulement quand le script a pris
         | le relais — jamais en CSS, sinon il disparaîtrait aussi pour ceux
         | qui en ont besoin.
         */
        formulaire.querySelectorAll('[data-auto-filtre-bouton]').forEach((bouton) => {
            bouton.hidden = true;
        });
    });
}
