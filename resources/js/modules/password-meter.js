/**
 * Indicateur de robustesse du mot de passe.
 *
 * Activation :
 *   <input data-pw-meter-input>
 *   <div data-pw-meter hidden>
 *     <span class="pw-meter__seg"> × 4
 *     <span data-pw-meter-label>
 *
 * SANS JAVASCRIPT : l'indicateur garde son attribut [hidden] et n'apparaît
 * jamais. Aucun cadre vide, aucune place perdue, aucun champ bloqué — c'est
 * une indication de confort, jamais une condition d'envoi.
 *
 * IL N'IMPOSE RIEN. La seule règle qui vaut est celle du serveur
 * (Rules\Password::defaults(), au moins 8 caractères) : un mot de passe jugé
 * « faible » ici est accepté si le serveur l'accepte. Afficher une exigence
 * que le formulaire ne applique pas ferait mentir l'interface.
 */

const NIVEAUX = ['Très faible', 'Faible', 'Correct', 'Robuste'];

/**
 * Score de 0 à 4. Longueur d'abord — c'est ce qui compte le plus — puis
 * variété des caractères.
 */
function score(valeur) {
    if (!valeur) return 0;

    let points = 0;

    if (valeur.length >= 8) points += 1;
    if (valeur.length >= 12) points += 1;
    if (valeur.length >= 16) points += 1;

    const varietes = [/[a-z]/, /[A-Z]/, /\d/, /[^\w\s]/].filter((re) => re.test(valeur)).length;

    if (varietes >= 3) points += 1;

    // Moins de 8 caractères : le serveur refusera, on ne promet rien de mieux
    // que le premier cran.
    if (valeur.length < 8) return 1;

    return Math.min(4, Math.max(1, points));
}

export default function passwordMeter() {
    const champs = document.querySelectorAll('[data-pw-meter-input]');
    if (!champs.length) return;

    champs.forEach((champ) => {
        // L'indicateur est le premier frère du champ à porter le repère.
        const bloc = champ.closest('.f')?.querySelector('[data-pw-meter]');
        if (!bloc) return;

        const etiquette = bloc.querySelector('[data-pw-meter-label]');

        // Le JavaScript est là : on peut révéler l'indicateur.
        bloc.hidden = false;

        const rafraichir = () => {
            const niveau = score(champ.value);

            bloc.classList.remove('pw-meter--1', 'pw-meter--2', 'pw-meter--3', 'pw-meter--4');

            if (niveau === 0) {
                if (etiquette) etiquette.textContent = '';
                return;
            }

            bloc.classList.add(`pw-meter--${niveau}`);

            if (etiquette) etiquette.textContent = NIVEAUX[niveau - 1];
        };

        champ.addEventListener('input', rafraichir);
        rafraichir();   // un champ prérempli par le navigateur doit être évalué
    });
}
