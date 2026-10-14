# Système de design — QrID, direction A « Teranga institutionnel »

> La rigueur d'une institution, l'hospitalité d'un salon dakarois. Vert baobab
> profond sur sable, la trame du claustra en filigrane, une lisière tissée pour
> signer ce qui compte, l'or réservé à ce qui engage, la latérite comme seconde
> voix.

- **Source de vérité des valeurs** : `resources/sass/_tokens.scss`. Partout
  ailleurs, on **référence** (`var(--…)`, `esp()`, `typo()`, `rayon()`,
  `plan()`, `@include ecran()`).
- **Planche vivante** (local) : `http://127.0.0.1:8000/design-system` — chaque
  composant rendu par son vrai gabarit, dans les deux thèmes.
- **Stack** : Laravel Blade + Bootstrap 5.3 surchargé en SCSS, rendu serveur.
  Le JavaScript améliore, il ne porte jamais (voir plus bas).

---

## 1. La palette et ses rôles

Deux couches : la **palette brute** (`$baobab-800`, `$sable-100`…) puis les
**rôles** déclinés par thème sur `:root` et `.theme-dark` (classe posée par le
serveur sur `<html>`, voir `App\Support\Theme`). Un composant n'écrit que des
rôles.

| Rôle (`var(--…)`) | Clair | Sombre | Usage |
|---|---|---|---|
| `--page` | sable `#F5EFE4` | nuit `#0C1713` | fond de page |
| `--carte` | ivoire `#FFFCF6` | `#13211C` | surfaces, cartes, champs |
| `--surelev` | grès `#EDE4D3` | `#1B2D26` | surface 2, bandes, survol |
| `--texte` / `--texte-2` / `--texte-3` | `#15211C` / `#56605A` / `#5B645E` | `#F2ECE0` / `#ABB3AB` / `#9AA39B` | encres |
| `--bordure` / `--bordure-f` | `#E0D5C1` / `#CDBFA6` | `#2A3D35` / `#3C5249` | filets (décoratifs) |
| `--champ-bord` | `#8F826C` | `#6B7F75` | contour de champ, ≥ 3:1 |
| `--marque` (+ `--texte-inv-marque`) | baobab `#0D3B2E` (+ ivoire) | menthe claire `#6FD1A8` (+ `#0B1A15`) | bouton principal, actif |
| `--lien` | feuille `#146A50` | `#6FD1A8` | liens, accent de texte |
| `--accent-trait` | menthe `#1E9E7A` | idem | **remplissage seulement**, jamais du texte sur clair |
| `--terre` | latérite `#A9481F` | `#EE9B72` | surtitres, numéros d'étape, alerte |
| `--or` / `--or-texte` | `#B88A2A` / `#7E5D12` | `#E2B85A` | décor premium / texte or |
| `--focus` | latérite | or `#E2B85A` | contour de focus (3px) |
| `--couverture` (+ `--sur-couverture`, `-2`, `-accent`) | baobab | `#0B3328` | surfaces vertes (claustra) |
| `--succes` `--alerte` `--danger` `--info` `--neutre` (+ `-fond`, `-trait`) | `#145C3F` `#7A5300` `#9B2F1A` | `#8FE0B9` `#F2CC74` `#F6A68E` | cachets, alertes |
| `--teinte-vert` / `--teinte-terre` / `--teinte-or` | | | fonds d'icône |
| `--graphique-1` / `-2` / `-grille` | `#007A5E` / `#B9862A` | `#2BA57A` / `#B88B34` | histogrammes (validé daltonisme) |

L'échelle `--vert-900 … --vert-50` (baobab → menthe pâle) et le **panneau**
(`--panneau*`, tuiles vertes qui ne basculent pas) restent disponibles.

**Contraste** : chaque encre est mesurée contre les trois surfaces de son thème
(commentaires dans `_tokens.scss`) ; `php artisan design:contraste` refait le
calcul à chaque passage — y compris chaque état sur **son propre fond teinté**.
Minimum relevé : 4,58:1 (latérite sur grès).

## 2. La typographie

**Une famille** : Schibsted Grotesk, variable 400 → 900, **auto-hébergée**
(`public/fonts/schibsted-grotesk-latin.woff2`, 46,8 Ko, licence OFL à côté).
`font-display: swap` + police de repli **métrique** (Arial/Roboto recalés par
`size-adjust: 103,13 %`, `ascent-override`, `descent-override` — mesurés) : le
texte s'affiche tout de suite, la bascule ne décale presque rien. Préchargée
par `layouts/partials/police.blade.php`. Jamais de CDN.

Une page qui ne doit rien télécharger pose `font-family: $font-systeme`
(décision du lot 3 pour `/p/{slug}`).

| Classe / balise | Mobile → bureau | Graisse |
|---|---|---|
| `.t-display` | 42 → 72 | 850 |
| `h1`, `.t-h1` | 30 → 44 | 820 |
| `h2`, `.t-h2` | 26 → 36 | 800 |
| `h3`, `.t-h3` | 20 → 22 | 760 |
| `.t-lead` (chapô) | 18 → 20 | 400 |
| corps | 16 | 400 |
| `.t-over` (surtitre neutre), `.t-surtitre` (latérite + lisière) | 13, capitales | 650 |
| `.indic__valeur` (chiffre), `.num` | 32 → 44, tabulaires | 820 |

Sur mobile, texte de lecture ≥ 16 px ; champs à 16 px exactement (iOS).

## 3. Formes, espaces, mouvement

- **Rayons** `rayon()` : `xs` 6 (cachets) · `sm` 10 (boutons, champs) · `md` 16
  (cartes) · `lg` 24 (tuiles) · `xl` 32 (grands panneaux) · `full`. Carte PVC :
  sa norme CR80 (3,7 % de la largeur), pas cette échelle.
- **Ombres** `--ombre-1…4` : légères ; le **trait** sépare. En sombre, niveau 1
  nul. Aucun `backdrop-filter` sur un élément présent au défilement.
- **Espaces** `esp(n)`, base 4 : 4 · 8 · 12 · 16 · 20 · 24 · 32 · 40 · 48 · 64 ·
  80 · 96 · 128.
- **Plans** `plan()` : `contenu` 1 · `collant` 20 (barres du haut) · `barre` 30
  (barre du bas) · `menu` 40. Les composants Bootstrap restent au-dessus.
- **Mouvement** : `$d-rapide` 150 ms, `$d-normal` 250 ms, une seule courbe
  `$courbe`. `prefers-reduced-motion` coupe tout (`_animations.scss`).
- **Ruptures** `@include ecran()` : `sm` 375 · `ms` 480 · `md` 640 · `lg` 768 ·
  `xl` 1024 · `xxl` 1280 · `max` 1440. Mobile first, `min-width` seulement.

## 4. Les motifs (signatures)

- **Claustra** — `.claustra` (couverture verte + trame ivoire à 14 %),
  `.claustra--lg`, `.claustra--clair` (trame baobab sur une surface claire).
  Sur toutes les surfaces vertes.
- **Lisière tissée** — `.lisiere` (8px), `.lisiere--fine` (6px), `.avec-lisiere`
  (frise au bas d'une surface ; `--haut` en tête). Hauteur multiple de 6 ou 8.
  Elle ferme ce qui compte : couverture, onglet actif, offre Standard, pied de
  page, tranche de la carte.
- **Monogramme** — `<x-brand>` : le carré de repérage du QR devient un Q à
  queue dorée (SVG en ligne). Tons `dark` (sur le thème) et `light` (sur vert).
- **Cachet**, **gros chiffres tabulaires**, **latérite seconde voix** : voir
  composants.

## 5. Les composants — l'API

| Besoin | Utiliser |
|---|---|
| Bouton | `<x-button>` — `primary` (défaut), `secondary`, `quiet`, `danger`, `ivory` / `outline-ivory` (sur vert) ; `size="sm|lg"`, `icon="…"`, `:block`. 48px (44 en petit). Envoi en cours : `aria-busy` (roue). |
| Icône | `<x-icone name="download" />` — jeu unique (Lucide, trait 1,75). Seule dans un bouton → `aria-label` sur le bouton. Bouton-icône : `.btn-icone`. |
| Champ | `<x-field>`, `<x-input>`, `<x-select>`, `<x-textarea>`, `<x-checkbox>`, `<x-phone-field>` — 52px, contour 1,5px, focus latérite 3px, erreur sous le champ. |
| Cachet (statut) | `<x-badge status="pending" />` ou `variant="success|warning|danger|info|neutral|gold"` — icône + libellé, jamais la couleur seule. |
| Alerte | `<x-alert type="warning" title="…">` — fond teinté, bande, icône. |
| Surface | `.tuile` (24) + `.tuile__tete` / `.tuile__titre` ; `.tuile--couverture` (verte, claustra), `.tuile--gres`, `.tuile--panneau` (32) ; `.panneau` / `.card` (16). |
| Indicateur | `<x-stat label value unit icon trend trend-value hint />` — gros chiffres tabulaires. |
| Vide | `<x-empty-state icon title message>` + slot `action`. |
| Fil d'Ariane | `<x-breadcrumb :items>` (`.fil`). |
| Pagination | `<x-pagination :paginator>`. |
| Étapes | `<x-wizard-progress :step :labels>` (déjà dans `<x-step-shell>`). |
| Modale | `<x-modal id title>`. |
| Opérateur | `<x-puce-operateur methode="wave" />` (point + nom) ; `<x-operator-mark>` (logo ou initiales). |
| Lien fléché | `.lien-fleche` + `<x-icone name="arrow-right" />`. |
| Préférences | `<x-language-toggle />`, `<x-theme-toggle />` (formulaires POST). |
| Aide | `<x-whatsapp-fab variante="barre|lien|bloc" />` — dans la page, **jamais flottante**. |
| Google | `<x-google-button />`. |

## 6. Les coques

- **Espace client** (`layouts/app.blade.php`) — barre ivoire collante : marque,
  **six onglets** (Tableau de bord, Statistiques, Mon profil, Mon QR Code,
  Abonnement, Compte ; l'actif signé par la **lisière**), puis aide, recherche,
  cloche, menu du compte. Sous 768px : **barre d'onglets ancrée en bas**
  (`<x-barre-onglets>`, opaque, sans flou, zone sûre, 5 entrées) ; le corps
  porte `avec-onglets-bas`. Plus de colonne, de hamburger ni d'offcanvas.
  L'abonnement, absent de la barre du bas, est dans le menu du compte (et
  l'onglet « Compte » s'allume sur ses écrans). **La liste vient de
  `App\Support\Navigation`** — ne la recopiez jamais.
- **Administration** (`layouts/admin.blade.php`) — colonne **claire** (ivoire,
  filets, rubrique active : grès + trait latérite), cachet ADMIN, nombre de
  paiements à vérifier (une requête COUNT indexée). Barre : fil d'Ariane,
  recherche, cloche, compte. Sous 768px : barre du bas (4 rubriques + « Plus »
  qui ouvre la colonne, aussi sans JavaScript via `:target`).
- **Vitrine** (`layouts/public.blade.php`) — `<x-nav-public>` et
  `<x-footer-public>` sont posés ; une page compose ses sections dans le slot.
  Ancres attendues par la barre : `#fonctionnement`, `#tarifs`, `#carte-pvc`
  (et `#contact` pour « Nous écrire »).
- Les menus de coque (compte, notifications, recherche) sont des `<details
  data-menu>` : ils s'ouvrent sans JavaScript ; `menus.js` ajoute la fermeture
  au clic extérieur et à Échap.

## 7. Pour composer un écran

1. Choisir le gabarit (`<x-app-layout>`, `<x-admin-layout title subtitle>` avec
   slot `actions`, `<x-public-layout>`, `<x-auth-layout>`). Un écran porte son
   unique `<h1>` (l'administration le pose pour vous).
2. Poser des **tuiles** en grille (CSS grid de la feuille de page), des
   composants `x-…`, des icônes `x-icone`. S'il manque un composant, l'ajouter
   à la bibliothèque — jamais `<button class="btn btn-primary">`.
3. Styles propres à l'écran : dans la feuille de page du lot
   (`_p-accueil.scss`, `_p-client.scss`, partie PAGES de `_admin.scss`…), en
   **jetons uniquement**. Vérifier les deux thèmes, 360 / 390 / 768 / 1024 /
   1440 px, sans débordement.
4. Tout texte passe par `lang/fr` et `lang/en`. Aucune donnée inventée.
5. Avant de rendre : `php artisan design:check`, `php artisan design:contraste`,
   `node tests/Browser/coque.mjs`, `php vendor/bin/phpunit`.

## 8. Les lois (non négociables)

1. **Une seule source de valeurs** : `_tokens.scss`. `design:check` compte les
   valeurs en dur contre un **plafond qui ne remonte jamais**
   (`config/design.php`) : on corrige la valeur, pas le plafond.
2. **Aucun `!important` nouveau** — le socle gagne par l'ordre d'import.
3. **Mobile first strict**, `min-width` seulement.
4. **4,5:1 pour tout texte, 3:1 pour tout tracé**, deux thèmes, tous états.
5. **44px minimum** pour toute cible tactile.
6. **Le composant avant le balisage.**
7. **Le survol enrichit, il ne révèle pas** : jamais de `color` posée seulement
   dans `:hover`, jamais de `color` sur `a:link`/`a:visited`.
8. **Aucun lien souligné** ; le focus clavier (3px, décalage 2px) n'est jamais
   supprimé.
9. **Compte ≠ Profil** — « créer un compte », « mon compte » ; « créer mon
   profil » n'apparaît qu'à partir du tableau de bord.
10. **Aucun texte en dur** dans une vue : `lang/fr`, `lang/en`.

Et : QR Code toujours sombre sur fond clair ; la page publique ne charge pas
le JavaScript de l'application ; une révélation au défilement n'est jamais un
contenu laissé à `opacity: 0` sans script (c'est le module qui pose
`.is-hidden`).

## 9. Le JavaScript — améliore, ne porte jamais

Autorisé : révélation au défilement, copie, aperçu d'image, compteurs,
confirmations, fermeture des menus, composants Bootstrap via `data-bs-*`.
Interdit : framework de rendu client, validation seulement côté client,
navigation portée par le script. Un module par fonctionnalité
(`resources/js/modules/`), qui sort s'il n'a rien à faire, et qui documente ce
qui se passe sans lui.

## 10. L'outillage

```
php artisan design:check            # plafonds : valeurs en dur, !important, max-width, style en ligne, liens morts, soulignés
php artisan design:check --detail   # chaque occurrence
php artisan design:contraste        # chaque couple de jetons, deux thèmes, fonds teintés aplatis
node tests/Browser/coque.mjs <base> <courriel> <mot de passe>   # barre du bas, navigation large, cibles, recouvrement
node tests/Browser/contraste.mjs    # contraste sur le DOM rendu
```

**Budget CSS** : `config/design.php` (`css.reference`, tolérance 5 %). Un lot
qui le dépasse s'en explique dans son commit — chaque kilo-octet se paie en
3G devant un écran blanc.
