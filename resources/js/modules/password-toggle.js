/**
 * Bascule afficher / masquer un champ mot de passe.
 *
 * Activation : <button data-password-toggle="ID_DU_CHAMP">
 *
 * SANS JAVASCRIPT : le champ reste en type="password", donc masqué.
 * L'utilisateur saisit et soumet normalement — seule la commodité de
 * relecture disparaît. Aucune dégradation de sécurité.
 */
export default function passwordToggle() {
    // Délégation : un seul écouteur couvre aussi les champs dans les modales.
    if (!document.querySelector('[data-password-toggle]')) return;

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-password-toggle]');
        if (!button) return;

        const input = document.getElementById(button.dataset.passwordToggle);
        if (!input) return;

        const reveal = input.type === 'password';
        input.type = reveal ? 'text' : 'password';

        button.setAttribute('aria-label', reveal ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
        button.setAttribute('aria-pressed', reveal ? 'true' : 'false');

        button.querySelector('[data-icon-show]')?.classList.toggle('d-none', reveal);
        button.querySelector('[data-icon-hide]')?.classList.toggle('d-none', !reveal);
    });
}
