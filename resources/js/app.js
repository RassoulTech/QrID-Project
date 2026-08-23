// =============================================================================
// QrID — point d'entrée JavaScript
//
// PRINCIPE : le JavaScript AMÉLIORE, il ne PORTE jamais.
// Toute fonctionnalité reste utilisable si ce fichier ne se charge pas.
// Le serveur reste la source de vérité : validation, autorisation, état,
// navigation.
//
// Un module par fonctionnalité. Chaque module s'initialise UNIQUEMENT si
// l'élément qu'il pilote existe dans la page — sur la landing, seuls
// scroll-reveal et navbar-scroll font quelque chose.
// =============================================================================

// Composants Bootstrap natifs (offcanvas, collapse, dropdown, modal),
// activés par attributs data-bs-*. Aucun code d'initialisation à écrire.
import 'bootstrap';

import scrollReveal from './modules/scroll-reveal';
import navbarScroll from './modules/navbar-scroll';
import passwordToggle from './modules/password-toggle';
import passwordMeter from './modules/password-meter';
import resendTimer from './modules/resend-timer';
import pvcFlip from './modules/pvc-flip';
import copyToClipboard from './modules/copy-to-clipboard';
import confirmAction from './modules/confirm-action';
import charCounter from './modules/char-counter';
import photoPreview from './modules/photo-preview';
import socialRepeater from './modules/social-repeater';
import autoFiltre from './modules/auto-filtre';
import flashAuto from './modules/flash-auto';
import telephone from './modules/telephone';
import langue from './modules/langue';
import enregistrerContact from './modules/enregistrer-contact';

const modules = [
    scrollReveal,
    navbarScroll,
    passwordToggle,
    passwordMeter,
    resendTimer,
    pvcFlip,
    copyToClipboard,
    confirmAction,
    charCounter,
    photoPreview,
    socialRepeater,
    autoFiltre,
    flashAuto,
    telephone,
    langue,
    enregistrerContact,
];

// Chaque module renvoie tôt s'il n'a rien à faire sur la page courante.
const boot = () => modules.forEach((init) => init());

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
