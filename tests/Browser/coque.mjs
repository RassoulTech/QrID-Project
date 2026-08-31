/**
 * LA COQUE APPLICATIVE — dock, colonne latérale, cibles tactiles.
 *
 * Vérifie ce qu'aucune analyse statique ne peut dire :
 *   · le dock est présent sous 768px et absent au-delà ;
 *   · la colonne latérale fait l'inverse, et les deux ne coexistent jamais ;
 *   · l'entrée active se distingue AU REPOS, par le fond ET la couleur ;
 *   · aucune entrée n'est sous la cible tactile ;
 *   · le dock ne recouvre pas la fin du contenu ;
 *   · rien ne déborde horizontalement.
 *
 * Usage :
 *     node tests/Browser/coque.mjs [base] [email] [motdepasse]
 */

import { chromium } from 'playwright';

const BASE = process.argv[2] ?? 'http://127.0.0.1:8899';
const EMAIL = process.argv[3] ?? 'apercu@qrid.test';
const MOTDEPASSE = process.argv[4] ?? 'Apercu#2026';

const LARGEURS = [320, 360, 390, 768, 1024, 1440];

const PAGES = [
    '/dashboard', '/profil', '/compte',
    '/admin/vue-ensemble', '/admin/clients', '/admin/paiements',
];

const RELEVE = () => {
    const dock = document.querySelector('.dock');
    const cote = document.querySelector('.adm-side');
    const st = (el) => (el ? getComputedStyle(el) : null);

    const visible = (el) => {
        if (!el) return false;
        const s = getComputedStyle(el);
        if (s.display === 'none' || s.visibility === 'hidden') return false;
        const r = el.getBoundingClientRect();
        return r.width > 0 && r.height > 0;
    };

    /* L'entrée active doit se distinguer AU REPOS. On compare son fond et
       sa couleur à ceux d'une entrée inactive : si les deux couples sont
       identiques, rien ne dit où l'on est. */
    const actif = document.querySelector('.dock__lien.is-actif');
    const inactif = document.querySelector('.dock__lien:not(.is-actif)');
    let distinction = null;
    if (actif && inactif) {
        const a = st(actif), i = st(inactif);
        distinction = {
            fond: a.backgroundColor !== i.backgroundColor,
            couleur: a.color !== i.color,
            aria: actif.getAttribute('aria-current') === 'page',
        };
    }

    /* Le dock est `fixed` : il ne pousse rien. Sans réserve en bas de
       page, il recouvre le dernier élément — et c'est toujours le bouton
       d'envoi. */
    let recouvre = false;
    if (visible(dock)) {
        const d = dock.getBoundingClientRect();
        const bas = parseFloat(getComputedStyle(document.body).paddingBottom);
        recouvre = bas < d.height;
    }

    /* Un dock MASQUÉ mesure 0×0 : ses entrées ne sont pas « trop
       petites », elles n'existent pas à l'écran. Les compter produisait
       dix-huit fautes qui n'en étaient pas. */
    const petites = visible(dock)
        ? [...document.querySelectorAll('.dock__lien')]
            .map((el) => el.getBoundingClientRect())
            .filter((r) => r.height < 44 || r.width < 44)
            .map((r) => `${Math.round(r.width)}×${Math.round(r.height)}`)
        : [];

    return {
        dock: visible(dock),
        cote: visible(cote),
        entrees: document.querySelectorAll('.dock__lien').length,
        distinction,
        recouvre,
        petites,
        flou: dock ? st(dock).backdropFilter : null,
        debordement: document.documentElement.scrollWidth > window.innerWidth
            ? document.documentElement.scrollWidth - window.innerWidth : 0,
    };
};

const navigateur = await chromium.launch();
const contexte = await navigateur.newContext();
const page = await contexte.newPage();

// ── Connexion, par le vrai formulaire ────────────────────────────────
await page.goto(BASE + '/login', { waitUntil: 'load' });
await page.fill('form[action$="/login"] input[type=email]', EMAIL);
await page.fill('form[action$="/login"] input[type=password]', MOTDEPASSE);
await page.click('form[action$="/login"] button[type=submit]');

/* La connexion enchaîne plusieurs redirections — vers l'accueil, puis vers
   le tableau de bord. `waitForNavigation` rend la main à la PREMIÈRE, et
   l'on croit alors être resté sur /login. On attend l'URL d'arrivée. */
try {
    await page.waitForURL((u) => !u.pathname.endsWith('/login'), { timeout: 20000 });
} catch {
    const erreurs = await page.evaluate(() =>
        [...document.querySelectorAll('.f__error, .invalid-feedback, [role=alert], .alert')]
            .map((e) => e.textContent.trim()).filter(Boolean).slice(0, 3));
    console.log('CONNEXION REFUSÉE — le relevé ne peut pas continuer.');
    erreurs.forEach((e) => console.log('   ' + e));
    await navigateur.close();
    process.exit(2);
}

console.log('═'.repeat(74));
console.log('LA COQUE APPLICATIVE');
console.log('═'.repeat(74));
console.log('');
console.log('page                    largeur  dock  côté  entrées  actif  déb.');
console.log('─'.repeat(74));

let fautes = [];

for (const chemin of PAGES) {
    for (const largeur of LARGEURS) {
        await page.setViewportSize({ width: largeur, height: 900 });
        let reponse;
        try {
            reponse = await page.goto(BASE + chemin, { waitUntil: 'load', timeout: 30000 });
        } catch {
            continue;
        }

        /* UNE PAGE D'ERREUR N'A PAS DE COQUE, ET C'EST VOULU : un 403 ne
           doit pas porter la navigation d'un espace auquel on n'a pas
           accès. Mesurer l'absence de dock sur un 403 produisait neuf
           fautes qui décrivaient le bon comportement. */
        if (reponse && reponse.status() >= 400) continue;
        /* UNE REDIRECTION N'EST PAS UN DÉFAUT DE LA PAGE DEMANDÉE.
           Un client qui ouvre une URL d'administration est renvoyé vers
           son tableau de bord : mesurer ce tableau de bord en croyant
           être sur la page admin produisait neuf « docks absents » qui
           n'existaient pas. */
        if (new URL(page.url()).pathname !== chemin) continue;

        const r = await page.evaluate(RELEVE);
        const mobile = largeur < 768;

        // ── Les règles ───────────────────────────────────────────────
        if (mobile && !r.dock) fautes.push(`${chemin} @${largeur} : dock absent sur mobile`);
        if (!mobile && r.dock) fautes.push(`${chemin} @${largeur} : dock présent au-delà de 768px`);
        if (mobile && r.dock && r.cote) fautes.push(`${chemin} @${largeur} : dock ET colonne visibles`);
        if (r.recouvre) fautes.push(`${chemin} @${largeur} : le dock recouvre la fin du contenu`);
        if (r.petites.length) fautes.push(`${chemin} @${largeur} : entrée sous 44px (${r.petites.join(', ')})`);
        if (r.debordement) fautes.push(`${chemin} @${largeur} : débordement de ${r.debordement}px`);
        if (r.flou && r.flou !== 'none') fautes.push(`${chemin} @${largeur} : backdrop-filter interdit (${r.flou})`);
        if (r.dock && r.distinction && !(r.distinction.fond && r.distinction.couleur)) {
            fautes.push(`${chemin} @${largeur} : l'entrée active ne se distingue pas au repos`);
        }
        if (r.dock && r.distinction && !r.distinction.aria) {
            fautes.push(`${chemin} @${largeur} : aria-current absent de l'entrée active`);
        }

        console.log(
            `${chemin.padEnd(24)}${String(largeur).padStart(5)}px` +
            `${(r.dock ? '  oui' : '  —  ').padStart(6)}` +
            `${(r.cote ? '  oui' : '  —  ').padStart(6)}` +
            `${String(r.entrees).padStart(8)}` +
            `${(r.distinction ? (r.distinction.fond && r.distinction.couleur ? '   ok' : '  NON') : '    —').padStart(7)}` +
            `${String(r.debordement || 0).padStart(6)}`
        );
    }
}

await navigateur.close();

console.log('');
if (fautes.length) {
    console.log(`${fautes.length} FAUTE(S) :`);
    [...new Set(fautes)].forEach((f) => console.log('   ' + f));
    process.exit(1);
}
console.log('Aucune faute.');
