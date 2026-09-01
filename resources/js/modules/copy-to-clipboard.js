/**
 * Copie une valeur dans le presse-papiers.
 *
 * Activation :
 *   <input id="lienPublic" value="https://..." readonly>
 *   <button data-copy="lienPublic" data-copy-done="Copié !">Copier</button>
 *
 * SANS JAVASCRIPT : la valeur reste affichée dans un champ `readonly`
 * sélectionnable. L'utilisateur la copie à la main (Ctrl+C ou appui long
 * sur mobile). L'information n'est jamais inaccessible.
 *
 * Aucune donnée n'est envoyée : opération purement locale, aucun appel réseau.
 */
export default function copyToClipboard() {
    const buttons = document.querySelectorAll('[data-copy]');
    if (!buttons.length) return;

    buttons.forEach((button) => {
        button.addEventListener('click', async () => {
            const source = document.getElementById(button.dataset.copy);
            if (!source) return;

            const value = source.value ?? source.textContent.trim();

            /*
             | LE LIBELLÉ SE CHANGE, PAS LE BOUTON ENTIER.
             |
             | On écrivait `button.textContent = done`, ce qui remplace TOUT le
             | contenu — y compris une icône SVG. Le bouton « Copier » en porte
             | une : après la première copie, elle disparaissait
             | définitivement, puisque `original` ne capture que le texte.
             |
             | `[data-copy-label]` désigne la seule partie à remplacer. Sans cet
             | élément, on retombe sur l'ancien comportement, qui reste juste
             | pour un bouton sans icône.
             */
            const cible = button.querySelector('[data-copy-label]') ?? button;
            const original = cible.textContent;
            const done = button.dataset.copyDone || 'Copié';

            try {
                // API moderne, disponible uniquement en contexte sécurisé.
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(value);
                } else {
                    // Repli : sélection du champ, l'utilisateur voit quoi copier.
                    source.select?.();
                    source.setSelectionRange?.(0, value.length);
                    document.execCommand('copy');
                }

                cible.textContent = done;
                button.setAttribute('aria-live', 'polite');
                setTimeout(() => { cible.textContent = original; }, 2000);
            } catch {
                // Échec : on sélectionne au moins la valeur pour l'utilisateur.
                source.select?.();
            }
        });
    });
}
