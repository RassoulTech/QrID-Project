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
            const original = button.textContent;
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

                button.textContent = done;
                button.setAttribute('aria-live', 'polite');
                setTimeout(() => { button.textContent = original; }, 2000);
            } catch {
                // Échec : on sélectionne au moins la valeur pour l'utilisateur.
                source.select?.();
            }
        });
    });
}
