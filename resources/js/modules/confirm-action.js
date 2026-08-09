/**
 * Confirmation avant une action destructrice.
 *
 * Activation :
 *   <form method="POST" data-confirm="Supprimer définitivement ce profil ?">
 *
 * SANS JAVASCRIPT : le formulaire se soumet directement. La protection
 * réelle reste SERVEUR — mot de passe exigé, Policy, jeton CSRF. Cette
 * confirmation n'est qu'un garde-fou contre le clic accidentel, jamais
 * une mesure de sécurité.
 *
 * Les suppressions sensibles gardent en plus leur modale Bootstrap avec
 * saisie du mot de passe : ce module ne la remplace pas.
 */
export default function confirmAction() {
    const forms = document.querySelectorAll('[data-confirm]');
    if (!forms.length) return;

    forms.forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });
}
