/**
 * Aperçu en direct de la couleur choisie.
 *
 * SANS JAVASCRIPT : la couleur cochée au chargement est déjà appliquée par
 * la variable CSS écrite dans la vue. L'aperçu se met alors à jour à la
 * soumission de l'étape, pas au clic. Le choix reste entièrement possible.
 */
export default function colorPreview() {
    const group = document.querySelector('[data-color-preview]');
    if (!group) return;

    const target = document.querySelector(group.dataset.target || '');
    if (!target) return;

    group.addEventListener('change', (e) => {
        const input = e.target.closest('input[type="radio"]');
        if (!input) return;

        target.style.setProperty('--tpl-color', input.value);
    });
}
