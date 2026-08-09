/**
 * Compteur du délai avant qu'un nouvel e-mail de confirmation soit accepté.
 *
 * Activation :
 *   <button data-resend-button>
 *   <p data-resend-note data-resend-wait="42">Il vous reste 3 renvois.</p>
 *
 * SANS JAVASCRIPT : le bouton reste UTILISABLE et le texte affiché est celui
 * rendu par le serveur (le nombre de renvois restants). Le délai est de toute
 * façon appliqué côté serveur par RegistrationService::resend(), qui répond le
 * même message dans tous les cas — l'utilisateur ne peut ni contourner la
 * règle, ni rester bloqué par elle.
 *
 * Ce module ne fait donc qu'une chose : éviter un clic qui ne servirait à rien.
 */
export default function resendTimer() {
    const note = document.querySelector('[data-resend-note]');
    if (!note) return;

    const bouton = document.querySelector('[data-resend-button]');
    let restant = Number.parseInt(note.dataset.resendWait ?? '0', 10);

    // Rien à attendre, ou bouton déjà désactivé par le serveur (limite
    // atteinte) : on ne touche à rien, le message du serveur reste affiché.
    if (!Number.isFinite(restant) || restant <= 0 || !bouton || bouton.disabled) return;

    const texteInitial = note.textContent.trim();

    const rendre = () => {
        note.textContent = `Nouvel envoi possible dans ${restant} s.`;
    };

    bouton.disabled = true;
    rendre();

    const minuteur = window.setInterval(() => {
        restant -= 1;

        if (restant <= 0) {
            window.clearInterval(minuteur);
            bouton.disabled = false;
            note.textContent = texteInitial;   // « Il vous reste N renvois. »
            return;
        }

        rendre();
    }, 1000);
}
