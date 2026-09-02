# QrID — DESIGN SYSTEM MASTER

> **LOGIQUE** : avant de construire une page, vérifier `design-system/qrid/pages/[page].md`.
> S'il existe, ses règles **écrasent** ce fichier. Sinon, suivre strictement ce fichier.

**Projet :** QrID · **Source :** plugin `ui-ux-pro-max` + tokens mesurés du projet
**Généré :** 31 août 2026

---

## 0. ARBITRAGE — ce qui vient du plugin, ce qui vient du projet

Le plugin a proposé une palette navy `#1E3A8A` + or `#B45309` et les polices
Poppins / Open Sans. **Refusé, et pour deux raisons factuelles :**

1. La palette QrID est **déjà mesurée** — 18 couples de contraste vérifiés dans
   `_tokens.scss`, minimum 4,52:1. Changer la teinte rouvre les 18.
2. Une police Google est un aller-retour DNS + TLS de plus. Le trafic public
   arrive **par scan de QR Code, souvent en 3G**. Zéro police distante.

| Ce qu'on PREND du plugin | Ce qu'on GARDE du projet |
|---|---|
| **Style : Swiss Modernism 2.0** — grille 12 colonnes, espacement mathématique, accent unique | La palette verte de `_tokens.scss` |
| **Style : Executive Dashboard** — KPI 4–6 max, métrique 48px, carte min 280px | La pile de polices système |
| **Style : Trust & Authority** — badges, métriques, preuves (landing) | L'échelle typographique `$type` |
| Les specs de composants (boutons, cartes, champs, modales) | Les 13 espacements `$espaces` |
| Les anti-patterns et la checklist de livraison | Les 8 ruptures `$ruptures` |
| Les règles d'animation et de layout | 48px de champ, 16px de police |

---

## 1. GRILLE — Swiss Modernism 2.0

C'est la règle qui règle le problème des espacements. **Plus aucun bloc placé
au jugé.**

```
--grid-cols: 12
--grid-gap:  16px  (esp(4))   mobile
             24px  (esp(6))   ≥ 768
             32px  (esp(7))   ≥ 1024
--base-unit: 4px   (l'échelle $espaces)
--max-width: 1280px  (rupture xxl)
```

| Largeur | Colonnes utilisées | Gouttière de page |
|---|---|---|
| < 480 | 1 | `esp(4)` 16px |
| 480–767 | 2 | `esp(4)` 16px |
| 768–1023 | 6 | `esp(6)` 24px |
| 1024–1279 | 12 | `esp(7)` 32px |
| ≥ 1280 | 12, largeur plafonnée à 1280 | `esp(7)` 32px, centré |

**Trois interdits de grille :**
- Aucun bloc ne porte de largeur en `px` : il occupe des colonnes.
- Aucun vide vertical > `esp(12)` 96px sur mobile, > `esp(13)` 128px sur bureau.
- Aucun bloc ne reste seul sur une ligne de 12 colonnes avec moins de la
  moitié remplie. S'il est seul, il se centre et se plafonne à 8 colonnes.

---

## 2. COULEUR — verrouillée, ne pas rouvrir

**Accent unique** (règle Swiss) : le vert. Aucune seconde couleur décorative.

| Rôle | Token | Clair | Sombre |
|---|---|---|---|
| Marque / primaire | `--marque` | `#0B3B2E` | `#1FBC8A` |
| Trait / icône **jamais fond de texte** | `--accent-trait` | `#1E9E7A` | `#1E9E7A` |
| Fond doux | `--marque-doux` | `#E4F2EC` | `rgba(#1FBC8A,.16)` |
| Page | `--page` | `#F7F8F7` | `#0F1714` |
| Surface | `--carte` | `#FFFFFF` | `#16211D` |
| Surélevé | `--surelev` | `#F2F3F1` | `#1D2B26` |
| Texte | `--texte` | `#0A1F1A` | `#EAF1EE` |
| Texte 2 | `--texte-2` | `#42524C` | `#B8C7C1` |
| Texte 3 | `--texte-3` | `#5C6B66` | `#93A59E` |

États : `--succes`, `--alerte`, `--danger`, `--info`, `--neutre`, chacun avec
son `-fond`. Tous mesurés ≥ 4,5:1 **sur leur propre fond doux**.

**Couleurs de tiers** (imposées, jamais nos teintes) : WhatsApp `#25D366`,
Wave `#1DC8FF`, Orange Money `#FF7900`, Free `#CD1719`.

---

## 3. TYPOGRAPHIE — 9 tailles, pas une de plus

Pile système. `font-family: system-ui, -apple-system, 'Segoe UI', Roboto,
'Helvetica Neue', Arial, sans-serif`.

| Rôle | Mobile | Bureau | Graisse | Interlettrage |
|---|---:|---:|---:|---|
| display | 34 | 52 | 800 | −.03em |
| h1 | 26 | 36 | 800 | −.025em |
| h2 | 21 | 27 | 700 | −.02em |
| h3 | 17 | 20 | 700 | −.01em |
| body-lg | 17 | 18 | 400 | 0 |
| body | 16 | 16 | 400 | 0 |
| body-sm | 14 | 14 | 400 | 0 |
| caption | 13 | 13 | 500 | 0 |
| overline | 11 | 11 | 700 | +.08em |

- Interligne corps : **1,62** (le plugin demande 1,5–1,75 ✓).
- Longueur de ligne : **68ch** maximum (le plugin demande 65–75 ✓).
- `body` reste à 16px sur mobile : en dessous, iOS zoome à la mise au point.

---

## 4. ESPACEMENT — 13 valeurs

```
esp(0) 0    esp(4) 16   esp(8)  40   esp(12) 96
esp(1) 4    esp(5) 20   esp(9)  48   esp(13) 128
esp(2) 8    esp(6) 24   esp(10) 64
esp(3) 12   esp(7) 32   esp(11) 80
```

| Usage | Mobile | ≥768 | ≥1024 |
|---|---|---|---|
| Section vitrine, padding vertical | 64 | 96 | 128 |
| Section applicative, padding vertical | 32 | 48 | 48 |
| Carte, padding intérieur | 20 | 24 | 24 |
| Carte dense (liste, KPI), padding | 16 | 20 | 20 |
| Écart entre cartes | 16 | 24 | 24 |
| Libellé → champ | 8 | 8 | 8 |
| Champ → aide / erreur | 8 | 8 | 8 |
| Groupe → groupe suivant | 20 | 20 | 20 |
| Dernier champ → actions | 32 | 32 | 32 |
| **Écart minimum entre deux cibles tactiles** | **8** | 8 | 8 |

Marges **vers le bas uniquement**. Les écarts s'expriment en `gap` sur le
parent, jamais en marge sur l'enfant.

---

## 5. COMPOSANTS

### Boutons

```
hauteur       44px minimum (52px pour l'action primaire d'un écran)
padding       12px 24px
rayon         999px (pilule) — le parti pris actuel, conservé
police        14px / 600
transition    background-color 200ms ease-out
cursor        pointer
gap icône     8px
```

| Variante | Fond | Texte | Bordure |
|---|---|---|---|
| `primary` | `--marque` | `--texte-inv-marque` | — |
| `outline-primary` | transparent | `--marque` | 1px `--marque` |
| `secondary` | `--surelev` | `--texte` | 1px `--bordure` |
| `danger` | `--danger` | `#FFF` | — |

**Trois variantes maximum par écran.** Quatre boutons de même poids côte à
côte n'ont plus de hiérarchie : le premier devient `primary`, les autres
`secondary`, et au-delà de trois actions secondaires, elles passent dans un
menu.

Survol : le **fond** change. Jamais la couleur du texte, jamais un `scale`.

### Cartes

```
fond      var(--carte)
rayon     14px  (rayon(md))
padding   20px mobile / 24px bureau
ombre     var(--ombre-1) au repos
bordure   1px var(--bordure)
survol (si cliquable)  ombre → var(--ombre-2), cursor pointer
```

### Champs

```
hauteur          48px
police           16px  (exactement — iOS)
padding          0 16px
rayon            8px  (rayon(sm))
bordure          1px var(--bordure-f)   ≥ 3:1, WCAG 1.4.11
focus            outline 2px var(--marque), offset 2px
```

**Champ avec icône** : `padding-inline-start: 44px` et l'icône positionnée à
`inset-inline-start: 14px`, taille 18px. Sans ce padding, l'icône chevauche le
texte.

### KPI — Executive Dashboard

```
--kpi-font-size    32px mobile / 48px bureau
--card-min-width   280px
--sparkline-height 32px
```

**4 à 6 KPI maximum** par écran. Au-delà, ce n'est plus un tableau de bord,
c'est un rapport. Grille : `repeat(auto-fit, minmax(280px, 1fr))`.

### Badges vs boutons — la distinction est obligatoire

| | Badge (état) | Bouton (action) |
|---|---|---|
| Rayon | pilule | pilule |
| Hauteur | **24px** | **44px minimum** |
| Police | 11px overline | 14px / 600 |
| Curseur | `default` | `pointer` |
| Survol | **aucun** | fond qui change |

Un état qui a la taille d'un bouton fait cliquer les gens dessus.

---

## 6. ANIMATION — règles du plugin, appliquées

| Règle | Valeur |
|---|---|
| Micro-interaction | **150–300ms** |
| Courbe | `ease-out` à l'entrée, `ease-in` à la sortie. **Jamais `linear`** |
| Propriétés animables | `transform` et `opacity` **uniquement** |
| Animation infinie | **Chargement seulement.** Jamais décorative |
| `prefers-reduced-motion` | Respecté : l'animation disparaît, l'état reste |
| Squelette de chargement | `animate-pulse` sur la forme finale, dimensions réservées |

**Interdits d'animation :**
- Un carrousel ou un défilement en boucle infinie sur un élément décoratif.
- Un `scale` au survol qui décale la mise en page.
- Une animation qui anime `width`, `height`, `top` ou `left`.
- Une transition > 500ms sur une interaction directe.

---

## 7. LAYOUT — les cinq pièges du plugin

1. **Éléments fixes** : ne jamais empiler deux éléments fixes sans écart
   calculé. Tout conteneur sous une barre fixe reçoit un padding égal à la
   hauteur de cette barre. **Aucun contenu ne passe derrière une barre fixe.**
2. **Barre flottante** : si elle flotte, elle a un écart des bords
   (`esp(4)` 16px). Si elle est collée, elle est pleine largeur et opaque.
   Pas d'entre-deux.
3. **`100vh`** : toujours `100dvh` avec repli `100vh`.
4. **Zéro défilement horizontal.** Les contenus larges défilent dans leur
   propre conteneur `overflow-x:auto`, jamais la page.
5. **Échelle de `z-index`** : `10` contenu surélevé · `20` barres fixes ·
   `30` dock · `40` offcanvas · `50` modales. Aucune autre valeur.

---

## 8. ANTI-PATTERNS — ne pas produire

- ❌ Émoji comme icône → SVG en ligne, jeu unique, `viewBox` 24×24
- ❌ `cursor` par défaut sur un élément cliquable
- ❌ Survol qui décale la mise en page
- ❌ Texte sous 4,5:1 · tracé sous 3:1
- ❌ Changement d'état instantané, ou > 500ms
- ❌ Focus invisible
- ❌ Dégradé décoratif · verre dépoli · ombre lourde
- ❌ Deuxième couleur d'accent
- ❌ Contenu masqué par une barre fixe
- ❌ Défilement horizontal
- ❌ Chiffre affiché non mesuré
- ❌ Police ou image depuis un CDN

---

## 9. CHECKLIST DE LIVRAISON

- [ ] Aucun émoji en icône ; jeu d'icônes unique
- [ ] `cursor: pointer` sur tout ce qui est cliquable
- [ ] Survols en 150–300ms, `ease-out`, sans décalage
- [ ] Contraste 4,5:1 vérifié en clair **et** en sombre
- [ ] Focus visible au clavier
- [ ] `prefers-reduced-motion` respecté
- [ ] Rendu vérifié à **375 / 768 / 1024 / 1440**
- [ ] Aucun contenu derrière une barre fixe
- [ ] Aucun défilement horizontal
- [ ] Cibles ≥ 44px, écart ≥ 8px
- [ ] Grille 12 colonnes respectée, aucun bloc en largeur `px`
- [ ] Aucun vide vertical hors échelle
