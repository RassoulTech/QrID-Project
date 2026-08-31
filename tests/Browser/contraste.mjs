/**
 * RELEVÉ DE CONTRASTE SUR LE DOM RENDU.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI CE SCRIPT, ALORS QUE design:contraste EXISTE
 * ═══════════════════════════════════════════════════════════════════════
 * `design:contraste` vérifie les VALEURS de `_tokens.scss`. Il ne peut
 * rien dire de ce qu'un visiteur voit : une couleur héritée dépend de la
 * cascade, un fond peut être écrasé par une feuille historique, et un
 * texte posé sur une photo dépend de la photo.
 *
 * Ce script mesure le rendu réel.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * DEUX ERREURS DÉJÀ COMMISES, QUE CE SCRIPT NE REFAIT PAS
 * ═══════════════════════════════════════════════════════════════════════
 *
 * 1. LE THÈME VIENT DU SERVEUR, PAS D'UNE CLASSE POSÉE EN JAVASCRIPT.
 *    Un premier relevé basculait le thème en ajoutant `.theme-dark` dans
 *    une iframe. La feuille sombre ne s'appliquait pas complètement : le
 *    script annonçait 7 défauts sur la page d'accueil alors qu'il y en
 *    avait UN. On passe donc par le vrai mécanisme — le cookie de
 *    préférence, lu par le serveur, qui rend la classe dans le HTML.
 *
 * 2. LE TEXTE SUR UNE IMAGE EST EXCLU ET COMPTÉ À PART.
 *    `.pubc__nom` est du blanc posé sur la photo de couverture, un <img>
 *    positionné en absolu. Un parcours du DOM ne voit que du blanc sur
 *    blanc et rend 1:1 — un faux positif. Son contraste dépend de la
 *    photo et ne se vérifie pas statiquement ; il repose sur le voile.
 *
 * Usage :
 *     node tests/Browser/contraste.mjs [http://127.0.0.1:8000]
 */

import { chromium } from 'playwright';

const BASE = process.argv[2] ?? 'http://127.0.0.1:8899';

const LARGEURS = [320, 360, 390, 768, 1024, 1440];

/** Les pages publiques : mesurées sans session. */
const PAGES_PUBLIQUES = [
    '/', '/tarifs', '/login', '/register', '/forgot-password',
    '/mentions-legales', '/confidentialite', '/conditions-generales',
    '/exemple', '/p/mouhamed-dione',
];

/** Le relevé, injecté dans la page. Il ne dépend d'aucun contexte. */
const MESURE = () => {
    const lum = (r, g, b) => {
        const f = (c) => {
            c /= 255;
            return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
        };
        return 0.2126 * f(r) + 0.7152 * f(g) + 0.0722 * f(b);
    };

    const parse = (s) => {
        const m = (s || '').match(
            /rgba?\(([\d.]+),\s*([\d.]+),\s*([\d.]+)(?:,\s*([\d.]+))?\)/
        );
        return m
            ? { r: +m[1], g: +m[2], b: +m[3], a: m[4] === undefined ? 1 : +m[4] }
            : null;
    };

    const fusion = (av, ar) => ({
        r: av.r * av.a + ar.r * (1 - av.a),
        g: av.g * av.a + ar.g * (1 - av.a),
        b: av.b * av.a + ar.b * (1 - av.a),
        a: 1,
    });

    /* Une image ou un dégradé sous le texte rend la mesure impossible en
       statique : on la SIGNALE au lieu de rendre un chiffre faux. */
    const surVisuel = (el) => {
        let n = el;
        while (n && n.nodeType === 1) {
            const s = getComputedStyle(n);
            if (s.backgroundImage && s.backgroundImage !== 'none') return true;
            const c = parse(s.backgroundColor);
            if (c && c.a === 1) return false;
            n = n.parentElement;
        }
        return false;
    };

    const fond = (el) => {
        let n = el;
        const pile = [];
        while (n && n.nodeType === 1) {
            const c = parse(getComputedStyle(n).backgroundColor);
            if (c && c.a > 0) {
                pile.push(c);
                if (c.a === 1) break;
            }
            n = n.parentElement;
        }
        let base = parse(getComputedStyle(document.body).backgroundColor)
            ?? { r: 255, g: 255, b: 255, a: 1 };
        for (let i = pile.length - 1; i >= 0; i--) base = fusion(pile[i], base);
        return base;
    };

    const ratio = (a, b) => {
        const l1 = lum(a.r, a.g, a.b);
        const l2 = lum(b.r, b.g, b.b);
        return (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05);
    };

    const hex = (c) =>
        '#' + [c.r, c.g, c.b]
            .map((v) => Math.round(v).toString(16).padStart(2, '0'))
            .join('').toUpperCase();

    const vus = new Map();
    let surImage = 0;
    let petitesCibles = [];

    document.querySelectorAll('body *').forEach((el) => {
        const st = getComputedStyle(el);
        if (st.display === 'none' || st.visibility === 'hidden' || +st.opacity === 0) return;
        if (!el.offsetParent && st.position !== 'fixed') return;

        /* ── Les cibles tactiles, tant qu'on est dans le DOM ─────────── */
        const cliquable = el.matches('a[href], button, input:not([type=hidden]), select, textarea, summary, [role=button]');
        if (cliquable) {
            const r = el.getBoundingClientRect();
            if (r.width > 0 && r.height > 0 && (r.height < 44 || r.width < 44)) {
                petitesCibles.push({
                    sel: (el.className || el.tagName).toString().split(' ')[0].slice(0, 28),
                    l: Math.round(r.width), h: Math.round(r.height),
                });
            }
        }

        const t = [...el.childNodes]
            .filter((n) => n.nodeType === 3)
            .map((n) => n.textContent.trim()).join('').trim();
        if (!t || t.length < 2) return;

        const av = parse(st.color);
        if (!av) return;

        if (surVisuel(el)) { surImage++; return; }

        const ar = fond(el);
        const eff = av.a < 1 ? fusion(av, ar) : av;
        const r = ratio(eff, ar);

        const px = parseFloat(st.fontSize);
        const gras = +st.fontWeight >= 700;
        const seuil = px >= 24 || (px >= 18.66 && gras) ? 3 : 4.5;
        if (r >= seuil) return;

        const cle = hex(eff) + '|' + hex(ar) + '|' + Math.round(px);
        if (vus.has(cle)) { vus.get(cle).n++; return; }

        vus.set(cle, {
            ratio: +r.toFixed(2), seuil, n: 1, px: Math.round(px),
            texte: hex(eff), fond: hex(ar),
            sel: (el.className || '').toString().split(' ').slice(0, 2).join('.').slice(0, 30),
            ex: t.slice(0, 24),
        });
    });

    /* Dédoublonnage des petites cibles : le même bouton répété cinquante
       fois dans une liste est UN défaut, pas cinquante. */
    const cibles = new Map();
    petitesCibles.forEach((c) => {
        const k = c.sel + c.l + 'x' + c.h;
        if (cibles.has(k)) cibles.get(k).n++;
        else cibles.set(k, { ...c, n: 1 });
    });

    return {
        echecs: [...vus.values()].sort((a, b) => a.ratio - b.ratio),
        surImage,
        cibles: [...cibles.values()],
        debordement: document.documentElement.scrollWidth > window.innerWidth
            ? document.documentElement.scrollWidth - window.innerWidth : 0,
        theme: document.documentElement.className || '(clair)',
        url: location.pathname,
    };
};

const navigateur = await chromium.launch();
let echecsTotaux = 0;
let debordements = 0;
const cumul = new Map();

for (const theme of ['light', 'dark']) {
    const contexte = await navigateur.newContext();

    /* LE THÈME PASSE PAR LE COOKIE, donc par le SERVEUR. Le cookie n'est
       pas chiffré — c'est délibéré : une chaîne « light »/« dark » n'a
       rien de secret, et un cookie chiffré devient illisible après une
       rotation de APP_KEY. */
    const { hostname } = new URL(BASE);
    await contexte.addCookies([
        { name: 'theme', value: theme, domain: hostname, path: '/' },
    ]);

    for (const largeur of LARGEURS) {
        const page = await contexte.newPage();
        await page.setViewportSize({ width: largeur, height: 900 });

        for (const chemin of PAGES_PUBLIQUES) {
            try {
                await page.goto(BASE + chemin, { waitUntil: 'domcontentloaded', timeout: 20000 });
            } catch {
                console.log(`  ?? ${chemin} injoignable`);
                continue;
            }

            const r = await page.evaluate(MESURE);

            if (r.debordement > 0) {
                debordements++;
                console.log(
                    `  DÉBORDEMENT  ${theme.padEnd(5)} ${String(largeur).padStart(4)}px  ${chemin}  +${r.debordement}px`
                );
            }

            r.echecs.forEach((e) => {
                echecsTotaux++;
                const k = `${e.sel}|${e.texte}|${e.fond}`;
                if (!cumul.has(k)) cumul.set(k, { ...e, theme, pages: new Set([chemin]) });
                else cumul.get(k).pages.add(chemin);
            });

            r.cibles.forEach((c) => {
                const k = `cible|${c.sel}|${c.l}x${c.h}`;
                if (!cumul.has(k)) {
                    cumul.set(k, {
                        cible: true, sel: c.sel, l: c.l, h: c.h,
                        theme, pages: new Set([chemin]),
                    });
                } else cumul.get(k).pages.add(chemin);
            });
        }

        await page.close();
    }

    await contexte.close();
}

await navigateur.close();

console.log('');
console.log('═'.repeat(72));
console.log(`RELEVÉ NAVIGATEUR — ${PAGES_PUBLIQUES.length} pages × ${LARGEURS.length} largeurs × 2 thèmes`);
console.log('═'.repeat(72));

const contrastes = [...cumul.values()].filter((e) => !e.cible)
    .sort((a, b) => a.ratio - b.ratio);
const cibles = [...cumul.values()].filter((e) => e.cible);

console.log(`\nCONTRASTE SOUS LE SEUIL — ${contrastes.length} couple(s) distinct(s)`);
contrastes.forEach((e) => {
    console.log(
        `   ${String(e.ratio).padStart(5)}:1  (seuil ${e.seuil})  ${e.texte} sur ${e.fond}` +
        `  ${String(e.px).padStart(2)}px  ${e.sel.padEnd(30)} ${e.pages.size} page(s)  « ${e.ex} »`
    );
});

console.log(`\nCIBLES TACTILES SOUS 44px — ${cibles.length} distincte(s)`);
cibles.slice(0, 20).forEach((c) => {
    console.log(`   ${String(c.l).padStart(4)}×${String(c.h).padEnd(4)}  ${c.sel.padEnd(30)} ${c.pages.size} page(s)`);
});

console.log(`\nDÉBORDEMENTS HORIZONTAUX : ${debordements}`);
console.log('');

process.exit(contrastes.length > 0 || debordements > 0 ? 1 : 0);
