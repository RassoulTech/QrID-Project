# PROMPT — REFONTE DESIGN QrID

**Référence unique : `design-system/qrid/MASTER.md`.**
Générée par le plugin `ui-ux-pro-max` (styles *Swiss Modernism 2.0* +
*Executive Dashboard* + *Trust & Authority*), avec les tokens mesurés du
projet verrouillés dedans.

Avant d'écrire une ligne : lire MASTER.md. Toute valeur vient de là.
`prompt-refonte-design.md` (2 400 lignes) devient de la documentation de fond,
plus le document de travail.

---

## RÈGLE

Chaque défaut ci-dessous est **constaté sur une capture d'écran de la
production** (`qrid-uutz.onrender.com`), pas déduit. On les corrige dans
l'ordre. Un lot = un commit. On vérifie à 375 / 768 / 1024 / 1440, thème clair
et sombre.

Commandes de contrôle, à lancer après chaque lot :

```bash
php artisan design:check && php artisan design:contraste
```

---

## LOT A — LES DÉFAUTS BLOQUANTS (constatés)

### A1. Du contenu passe derrière la navbar — landing bureau

Un bouton vert foncé apparaît **sous** la barre blanche, à droite. La navbar
est collée (`top:0; left:0; right:0`) et le contenu ne se dégage pas.

**Fix** — MASTER §7.1 : la navbar est opaque et pleine largeur ; le premier
conteneur reçoit `padding-block-start` égal à sa hauteur (72px bureau, 56px
mobile). Aucun contenu ne passe derrière.

### A2. Icône de recherche par-dessus le texte — admin ET espace client

Le placeholder s'affiche « ⌕Rechercher un client » : la loupe est posée sur la
première lettre. Défaut présent sur les deux espaces, donc dans le composant.

**Fix** — MASTER §5 *Champ avec icône* : `padding-inline-start: 44px`, icône à
`inset-inline-start: 14px`, 18px. Corrigé une fois, dans le composant.

### A3. Défilement horizontal — tableau de bord client

Le panneau « Activité du compte » est coupé au bord droit de l'écran, et le
bouton WhatsApp est tronqué.

**Fix** — MASTER §7.4 : zéro défilement horizontal. La grille passe en
`repeat(auto-fit, minmax(280px, 1fr))`, et le bouton WhatsApp flottant se
place à `esp(4)` des deux bords, `z-index: 20`, jamais au-dessus d'une action.

### A4. La carte est une vignette perdue dans le vide

Bloc « Ma carte » : la carte fait environ 200px dans un bloc de 950px. Le
produit central du service est l'élément le plus petit de son propre écran.

**Fix** :
- Carte à `min(420px, 100%)` — elle occupe sa colonne, pas une miniature.
- Grille du bloc : carte à gauche (7 colonnes), actions à droite (5), une
  seule colonne sous 768px.
- Le bouton « Voir le verso » ne dépasse jamais la largeur de la carte.

### A5. Quatre boutons empilés, sans hiérarchie

« QR en PNG », « QR en SVG », « Carte imprimable », « Modifier ma carte » :
quatre pilules de largeurs inégales, empilées, de même poids visuel.

**Fix** — MASTER §5 *Boutons*, trois variantes maximum :
- `Modifier ma carte` → **primary**, pleine largeur de sa colonne.
- `Carte imprimable` → **secondary**.
- `QR en PNG` / `QR en SVG` → un seul bouton **« Télécharger le QR »** avec un
  menu à deux entrées. Deux formats ne méritent pas deux boutons.

### A6. Des états dessinés comme des boutons

« Photo enregistrée » et « Bannière enregistrée » sont des pilules vertes de
la taille d'un bouton. On ne sait pas ce qui est cliquable.

**Fix** — MASTER §5 *Badges vs boutons* : badge = 24px de haut, 11px,
`cursor: default`, aucun survol. Un état ne ressemble jamais à une action.

### A7. Le lien public est illisible

Le champ affiche `https://...` tronqué. Le client ne voit pas son propre lien.

**Fix** : afficher le lien en entier, sur deux lignes si nécessaire
(`overflow-wrap: anywhere`), en `body-sm`. Le bouton « Copier » à côté, 44px.

---

## LOT B — LA LANDING

### B1. Le vide sous la navbar

Environ 230px de blanc vide entre la barre et le premier contenu visible.

**Fix** — MASTER §1 : aucun vide vertical hors échelle. Padding de section
vitrine : 64px mobile / 96px à 768 / 128px à 1024. Rien d'autre.

### B2. Le bandeau des métiers

Il est le **deuxième élément de la page**, en capitales de ~40px, et défile en
boucle infinie. Le visiteur voit un carrousel de mots avant la promesse.

**Fix** — MASTER §6, règle du plugin : *animation infinie = chargement
seulement, jamais décorative*.
- Le défilement s'arrête. Les six métiers deviennent une **ligne statique** de
  puces, en `caption` 13px, `--texte-3`, sur une seule ligne.
- Il descend **après** le hero et les trois chiffres.
- `prefers-reduced-motion` n'a plus rien à désactiver.

### B3. La carte n'est jamais montrée

La landing vend une carte PVC et ne l'affiche nulle part — seulement des
maquettes de téléphone.

**Fix** : une section « la carte », recto et verso, deux variantes, placée
**avant** les tarifs. Style *Trust & Authority* : on montre l'objet, puis son
prix.

### B4. « +500 professionnels »

Chiffre non mesuré, alors que `config/landing.php` pose la règle en toutes
lettres : « rien n'est mesuré ni inventé », et désactive le bloc témoignage
« tant qu'il n'y a pas de vrais clients ».

**Fix** : retirer les avatars et le `+500`.

---

## LOT C — LA COQUE

### C1. Colonne et contenu désalignés, double défilement

Capture admin : une barre de défilement grise apparaît entre la colonne sombre
et le contenu ; la colonne démarre plus haut que le contenu.

**Fix** : un seul conteneur défilant, la page. La colonne est `position:
sticky; top: 0; height: 100dvh`. Colonne et contenu partent de la même ligne.

### C2. « Aide » isolé en bas de colonne

Grand vide au-dessus, l'entrée flotte seule.

**Fix** : le bas de colonne (Aide, Déconnexion) est un bloc ancré en bas avec
`margin-block-start: auto` et un trait `1px var(--bordure)` au-dessus.

### C3. Mobile — pas de navigation permanente

**Fix** : dock en bas sous 768px, 5 entrées, opaque `--surelev`, bordure,
`--ombre-3`, `bottom: calc(esp(3) + env(safe-area-inset-bottom))`.
**Pas de `backdrop-filter`** : il fait saccader le défilement sur Android
d'entrée de gamme.
Le contenu se dégage de `calc(esp(11) + env(safe-area-inset-bottom))`.

---

## LOT D — LA CARTE

| Point | Décision |
|---|---|
| Format | CR80, `aspect-ratio: 1.586` — déjà juste, conservé |
| Rayon | `3.7cqw` — le rayon normatif CR80 (3,18 mm / 85,6 mm). Aujourd'hui `0`, ce que l'imprimeur ne livre pas |
| QR | Panneau clair autour du QR sur la variante verte, pour que le code reste **sombre sur clair** dans les deux variantes (ISO/IEC 18004) |
| Dégradé | Retiré. Aplat `#0B3B2E`. Un dégradé sur PVC produit du banding |
| Tailles | `--carte-apercu` `min(420px,92vw)` · `--carte-bloc` `min(320px,100%)` · `--carte-vignette` `min(200px,100%)` |
| Thème | La carte **ne bascule pas** avec le thème : c'est un objet physique |

**Mobile** : la carte occupe `92vw`, jamais moins. **Bureau** : elle est
plafonnée à 420px et **centrée dans sa colonne**, jamais étirée — une carte
étirée cesse d'être à l'échelle CR80.

---

## LOT E — LA PAGE PUBLIQUE

- Lignes de coordonnées : 56px, pastille ronde 40px `--marque`, libellé
  `overline` `--texte-3` au-dessus de la valeur `body` `--texte`, trait
  `1px --bordure` entre les lignes. **Toute la ligne est le lien.**
- Tuiles de réseaux : 64px, `repeat(auto-fill, minmax(64px, 1fr))`.
- Barre d'actions : 52px, collante, au-dessus de la zone sûre.
- Panneau QR en ligne dans la page — la surcouche actuelle utilise un
  `href="#"`, seul lien mort du produit.
- Aucun JavaScript applicatif chargé. C'est la page d'un scan, en 3G.

---

## ORDRE

```
A (bloquants)  →  C (coque)  →  B (landing)  →  D (carte)  →  E (page publique)
```

A d'abord : ce sont des défauts visibles en production.
C avant B et E : la coque porte tous les écrans.

## FIN DE LOT — trois lignes

1. **Fait** — avec le chiffre `design:check` avant / après.
2. **Bloqué** — et ce qu'il faut pour débloquer.
3. **Reste** — le lot suivant.
