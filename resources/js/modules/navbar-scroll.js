/**
 * Navbar : ombre et fond opaque après 40 px de défilement.
 *
 * Activation : <nav class="site-nav" data-navbar-scroll>
 *
 * SANS JAVASCRIPT : la navbar reste affichée, lisible et entièrement
 * fonctionnelle — fond blanc, bordure inférieure, tous les liens actifs.
 * Seul l'effet d'ombre au défilement disparaît. Aucune perte d'usage.
 *
 * Écoute passive et lecture différée par requestAnimationFrame : aucun
 * calcul de mise en page pendant le défilement.
 */

const THRESHOLD = 40; // px

export default function navbarScroll() {
    const nav = document.querySelector('[data-navbar-scroll]');
    if (!nav) return;

    let ticking = false;

    const update = () => {
        nav.classList.toggle('is-scrolled', window.scrollY > THRESHOLD);
        ticking = false;
    };

    const onScroll = () => {
        if (ticking) return;
        ticking = true;
        window.requestAnimationFrame(update);
    };

    window.addEventListener('scroll', onScroll, { passive: true });

    // État initial : la page peut être chargée déjà défilée (ancre, retour).
    update();
}
