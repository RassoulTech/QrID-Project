// =============================================================================
// FLASH AUTOMATIQUE — les messages globaux s'effacent, les erreurs de champ non
//
// CE QU'IL FAIT
// Un message flash global — « votre carte est active », « paiement annulé » —
// disparaît en fondu au bout de 30 secondes. Passé ce délai il n'informe plus :
// il encombre, et il finit par faire douter de sa fraîcheur.
//
// CE QU'IL NE TOUCHE PAS, ET C'EST LE POINT
// Les erreurs de VALIDATION DE CHAMP restent affichées indéfiniment. Elles
// désignent une correction à faire : les effacer au bout d'un moment ferait
// disparaître la consigne avant que la correction soit faite. Seuls les
// éléments portant data-flash-auto sont concernés.
//
// DÉGRADATION SANS JAVASCRIPT
// Le message est visible dans le HTML servi, sans classe qui le masque. Si ce
// fichier ne se charge pas, il reste simplement à l'écran — et le bouton de
// fermeture manuel de Bootstrap continue de fonctionner par ses attributs.
// Rien n'est jamais masqué en CSS en attendant que le script le révèle.
// =============================================================================

const DELAI = 30_000;
const FONDU = 400;

export default function flashAuto() {
    const messages = document.querySelectorAll('[data-flash-auto]');

    if (messages.length === 0) {
        return;
    }

    messages.forEach((message) => {
        let minuterie = null;

        const effacer = () => {
            message.style.transition = `opacity ${FONDU}ms ease`;
            message.style.opacity = '0';

            // On retire du flux APRÈS le fondu : masquer d'abord ferait sauter
            // le contenu qui suit avant que l'œil ait suivi la disparition.
            setTimeout(() => message.remove(), FONDU);
        };

        const armer = () => {
            minuterie = setTimeout(effacer, DELAI);
        };

        /*
         | LE SURVOL SUSPEND LE COMPTE À REBOURS.
         |
         | Quelqu'un qui a la souris sur le message est en train de le lire, ou
         | s'apprête à cliquer un lien qu'il contient. Le faire disparaître sous
         | le curseur est la seule chose pire que de le laisser trop longtemps.
         */
        message.addEventListener('mouseenter', () => clearTimeout(minuterie));
        message.addEventListener('mouseleave', armer);

        // Même raisonnement au clavier : on ne retire pas ce qui a le focus.
        message.addEventListener('focusin', () => clearTimeout(minuterie));
        message.addEventListener('focusout', armer);

        armer();
    });
}
