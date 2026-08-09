/**
 * Révélation au défilement — IntersectionObserver.
 *
 * Activation :
 *   <div data-reveal>                        décalage calculé automatiquement
 *   <div data-reveal data-reveal-delay="240">  décalage explicite en ms
 *   <div data-reveal="scale">                 ajoute un léger agrandissement
 *
 * Effet : opacité 0 → 1, translateY(30px) → 0, sur 600 ms.
 * Décalage de 80 ms entre les éléments d'un même groupe (même parent).
 *
 * SANS JAVASCRIPT : la page reste ENTIÈREMENT LISIBLE. Aucune classe de
 * masquage n'existe dans le HTML — c'est ce script qui ajoute `.is-hidden`
 * au chargement. S'il ne s'exécute pas, rien n'est jamais caché.
 *
 * L'observateur cesse d'observer chaque élément après son déclenchement,
 * puis se déconnecte entièrement quand le groupe est épuisé.
 */

const STEP = 80; // ms entre deux éléments d'un même groupe

export default function scrollReveal() {
    const items = Array.from(document.querySelectorAll('[data-reveal]'));
    if (!items.length) return;

    // Mouvement réduit : on ne masque rien, tout reste affiché immédiatement.
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    // Navigateur sans IntersectionObserver : les éléments restent visibles.
    if (!('IntersectionObserver' in window)) return;

    const positions = new Map();

    items.forEach((el) => {
        const parent = el.parentElement;
        const index = positions.get(parent) ?? 0;
        positions.set(parent, index + 1);

        const delay = el.dataset.revealDelay ?? index * STEP;
        el.style.transitionDelay = `${delay}ms`;

        // Masquage effectué ICI, jamais dans le markup.
        el.classList.add('is-hidden');
    });

    let remaining = items.length;

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;

                entry.target.classList.remove('is-hidden');

                observer.unobserve(entry.target);
                remaining -= 1;

                if (remaining === 0) observer.disconnect();
            });
        },
        { threshold: 0.12, rootMargin: '0px 0px -60px 0px' }
    );

    items.forEach((el) => observer.observe(el));
}
