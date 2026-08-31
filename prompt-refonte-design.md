# REFONTE DE DESIGN — PROMPT COMPLET, UNE FOIS POUR TOUTES

Ce document est le prompt d'exécution de la refonte visuelle de **QrID**.
Il n'est pas un souhait : chaque chiffre qu'il contient a été mesuré sur la
branche `main`, et chaque critère d'acceptation est vérifiable par une
commande.

**Règle d'usage** : on traite un lot, on le fait valider, on passe au suivant.
Jamais deux lots dans le même commit. Toute demande qui n'appartient à aucun
lot va dans `V2.md` et se dit à voix haute.

---

# PARTIE 0 — L'ÉTAT DES LIEUX, MESURÉ

À lire avant d'écrire une ligne. Ce qui suit dit **où en est le chantier**,
pas où il devrait en être.

## 0.1 Ce qui est déjà posé, et qui ne se refait pas

| Fichier | Rôle | Statut |
|---|---|---|
| `resources/sass/_tokens.scss` (410 l.) | **La source unique de valeurs** : palette deux thèmes, 9 tailles de police, 13 espacements, 6 rayons, 4 ombres × 2 thèmes, 4 durées, 1 courbe, 8 ruptures, 4 zones tactiles | **Acquis. Ne pas réécrire.** On y ajoute, on n'y retire pas. |
| `resources/sass/_socle.scss` (788 l.) | La couche qui **branche** les tokens sur le balisage réel. 11 sections : shell, typographie, focus, boutons, champs, surfaces, badges, messages, tableaux, médaillon, animations | **Acquis. C'est le point d'entrée de tout ajout.** |
| `resources/sass/_theme.scss` (144 l.) | **Alias seuls, zéro valeur.** Les noms historiques (`--surface`, `--bord`, `--accent`…) pointent vers les tokens | Acquis. On garde les alias : 448 lignes de `_carte-publique.scss` les emploient. |
| `audit-design.json` | Le relevé brut : chaque couleur, taille, espacement en dur, avec fichier et ligne | Acquis comme donnée. **Le script qui l'a produit n'est pas dans le dépôt** — voir Lot 0. |
| `docs/AUDIT-DESIGN.md` (186 l.) | L'audit commenté du 30 août 2026 | Acquis comme constat historique. Ne pas le modifier : c'est l'état AVANT. |

## 0.2 Ce qui est fait, et qui est mesuré

- Médaillon de marque : **3,37:1 sur 18 pages → 12,51:1**. Le vert accent ne
  porte plus de texte.
- Badges d'état : « En attente » passait `#FFC107` sur `#F2F3F1`, soit
  **1,46:1**, affiché 23 fois dont la liste des paiements. Chaque teinte douce
  est désormais calculée pour passer 4,5:1 **sur son propre fond**.
- Bouton contour en thème sombre : fond blanc résiduel, **1,18:1** → fond
  transparent.
- Options de `select` : `background` + `color` explicites, sinon Windows rend
  clair sur clair en thème sombre. Chevron décliné par thème — une image
  encodée ne peut pas lire une propriété CSS.
- Champs : **48 px de haut, 16 px de police exactement** — en dessous, iOS
  zoome à la mise au point et ne se dézoome pas.
- Page publique : lignes de coordonnées 44 → **56 px**, tuiles de réseau →
  **64 px**, barre d'actions 46 → **52 px**, nom sur l'échelle `h1` et jamais
  tronqué.
- 663 tests passent.

## 0.3 Ce qui N'EST PAS fait — le périmètre exact de ce prompt

**La dette source est à peine entamée : 1 576 valeurs en dur subsistent sur
1 598.** Le socle *surcharge* les anciennes feuilles ; il ne les a pas
réécrites. Le rendu obéit aux tokens pour les familles qu'il couvre ; les
8 108 lignes historiques gardent leurs valeurs partout ailleurs.

Répartition mesurée des valeurs en `px` restantes, par feuille — c'est la
carte du chantier :

| Feuille | Lignes | Occurrences `px` | Couverte par le socle ? |
|---|---:|---:|---|
| `_app-shell.scss` | 1 138 | **368** | Non — le plus gros chantier |
| `_admin.scss` | 1 736 | **363** | Non — famille `.adm-*` entière |
| `_components.scss` | 761 | **222** | Partiellement |
| `_auth.scss` | 819 | **190** | Partiellement (`.auth-*`) |
| `_phone.scss` | 530 | 163 | Non — **gelé**, voir 0.5 |
| `_dashboard.scss` | 487 | 117 | Non |
| `_carte-publique.scss` | 460 | 113 | Partiellement |
| `_carte-publique-matiere.scss` | 407 | 73 | Non — **gelé**, voir 0.5 |
| `_topbar.scss` | 121 | 37 | Non |
| `_contact.scss` | 157 | 35 | Non |
| `_whatsapp.scss` | 120 | 27 | Non |
| `_brand.scss` | 139 | 22 | Oui (médaillon) |
| `_card.scss` | 259 | 19 | Partiellement |
| `_base.scss` | 188 | 18 | Oui (shell) |
| `_variables.scss` | 68 | 11 | **Non — source concurrente, voir 0.4** |
| `_theme-dark.scss` | 479 | 2 | Oui (alias) |

Restent également : `/design-system` non refaite, `docs/DESIGN.md` non
réécrit, et **aucune vérification navigateur** — Playwright a refusé la
connexion, les contrastes sont vérifiés par calcul sur les valeurs livrées,
pas sur le rendu.

## 0.4 QUATRE CONTRADICTIONS À TRAITER EN PRIORITÉ

Elles ne se voient pas à l'écran. Elles réinjecteront l'ancien système dans
tout ce qu'on écrira ensuite, y compris par la main d'un humain de bonne foi
qui lit la documentation du projet.

### (1) `docs/DESIGN.md` documente un AUTRE système que `_tokens.scss`

Quatre divergences, toutes structurantes :

| Sujet | `docs/DESIGN.md` dit | `_tokens.scss` dit | Conséquence |
|---|---|---|---|
| Vert de marque | `$brand-600 #0B5D3B` | `$vert-fonce #0B3B2E` | **Deux verts de marque** dans le produit |
| Espacement | `3 = 16px`, base 4 px, 7 valeurs | `3 = 12px`, `4 = 16px`, 13 valeurs | Un `esp(3)` écrit d'après la doc vaut 12 px, pas 16 : **décalage silencieux partout** |
| Rayons | `base .5rem`, `lg .75rem` | `sm 8`, `md 14`, `lg 22`, `xl 32` | `lg` vaut 12 px pour la doc, 22 px pour le code |
| Typographie | échelle `clamp()` | carte `mobile`/`bureau` en px fixes | Deux méthodes incompatibles |

→ **`docs/DESIGN.md` est périmé et dangereux. Il se réécrit intégralement
(Lot 6), et rien d'autre ne le cite entre-temps.**

### (2) `php artisan design:check` est cité mais n'existe pas

Le commentaire d'en-tête de `_tokens.scss` affirme que la commande signale
toute valeur en dur. `app/Console/Commands/` ne contient pas ce fichier. Une
règle sans garde-fou n'est pas une règle, c'est une intention. → Lot 0.

### (3) `_variables.scss` est une seconde source de valeurs

68 lignes, 11 valeurs en px, et `_base.scss` y puise encore
(`background:$blanc`, `color:$texte`, `font-family:$font-base`). Or
`_tokens.scss` s'ouvre sur « LE SEUL FICHIER QUI CONTIENT DES VALEURS ». Les
deux affirmations ne peuvent pas être vraies ensemble. → Lot 1.

### (4) La famille `'QrID'` est promise et absente

`$font` commence par `'QrID'`, les cinq `@font-face` de `_base.scss` sont en
commentaire, et **`public/fonts/` n'existe pas**. Le repli système fonctionne,
donc rien n'est cassé — mais le produit annonce une police qu'il ne sert pas.
Deux issues seulement, à trancher au Lot 1 : déposer les cinq `.woff2`
(jamais un CDN), ou retirer `'QrID'` de la pile et l'assumer.

## 0.5 CE QUI EST GELÉ — on n'y touche pas

- **La carte PVC** : `carte-qrid.html` est la référence figée. Angles vifs
  (`rayon(none)`), composition validée, aucune valeur modifiable. Le rendu
  écran doit correspondre à ce qui sort de l'imprimeur.
- **Les maquettes de téléphone de la landing** (`_phone.scss`, 530 lignes) :
  gelées pour ce chantier, sauf si un contraste y échoue.
- **`profile/printable.blade.php`** : bloc `<style>` de 194 lignes, légitime —
  le rendu PDF n'accède à aucune feuille externe.
- **Les 14 gabarits d'e-mail** : ~90 attributs `style=` légitimes — aucun
  client de messagerie ne lit une feuille externe, beaucoup ignorent les
  propriétés CSS personnalisées. Ils s'**alignent** à la main sur les tokens
  (Lot 5), ils ne les consomment pas.
- **`docs/AUDIT-DESIGN.md`** : constat daté, on ne le retouche pas.

---

# PARTIE 1 — LE CONCEPT DE DESIGN

## 1.1 Ce que c'est, en une phrase

**Une identité institutionnelle sénégalaise, tenue par le vide et par une
échelle, jamais par l'effet.** Le blanc domine, le vert foncé signe, l'accent
ne fait que souligner. Rien ne bouge sans raison, rien n'a besoin d'être
survolé pour être lu.

## 1.2 Les sept partis pris, et leur raison

1. **Le blanc domine, le vert signe.** Le vert foncé `#0B3B2E` est réservé aux
   surfaces d'autorité — bouton principal, médaillon, sections sombres. Il ne
   remplit jamais une grande zone par décoration. Le vert accent `#1E9E7A` est
   une couleur de **trait et d'icône** : `#FFFFFF` dessus plafonne à 3,37:1,
   il ne porte donc jamais de texte.
   *Pourquoi ce vert* : au Sénégal, le vert porte la confiance et la réussite.
   Désaturé et sombre, il évoque le sérieux institutionnel, pas le « vert
   startup ».

2. **L'échelle avant le jugement.** Neuf tailles de police, treize
   espacements, six rayons. Une valeur hors échelle n'est pas un ajustement,
   c'est une régression : l'audit a relevé `7px` employé 21 fois et `12,5px`
   40 fois — personne ne les avait choisis.

3. **Mobile d'abord, strictement.** On écrit le style pour 320 px, et chaque
   `@media` ne fait qu'**ajouter**. Aucune requête `max-width`.

4. **Lisible au repos.** Le survol enrichit, il ne révèle jamais. La couleur
   du texte est identique dans les cinq états ; au survol, seul le fond change.

5. **Le poids est un critère.** Zéro police distante, zéro image externe, SVG
   en ligne. Le trafic public arrive par scan de QR Code, souvent en 3G. La
   page publique ne charge même pas le JavaScript de l'application.

6. **Le JavaScript améliore, il ne porte jamais.** Sans script, tout reste
   utilisable. Le serveur est la source de vérité : validation, autorisation,
   état, navigation.

7. **Deux thèmes, une seule marque.** Les surfaces changent, la marque reste
   reconnaissable — à une exception documentée : sur fond sombre le vert foncé
   se confond avec la surface, l'accent remonté `#1FBC8A` prend le relais.

## 1.3 Ce qui n'a pas droit de cité

Dégradé décoratif · ombre lourde · verre dépoli · néomorphisme · icône sans
fonction · animation qui déplace la mise en page · police distante · couleur
hors tokens · survol qui révèle · texte sous 4,5:1 · `href="#"` · le mot
« Laravel » dans l'interface.

---

# PARTIE 2 — LES LOIS NON NÉGOCIABLES

Elles s'appliquent à chaque ligne écrite pendant ce chantier, et à toutes
celles écrites après.

**Loi 1 — Une seule source de valeurs.**
Toute couleur, taille, espacement, rayon, ombre, durée et rupture vient de
`_tokens.scss`. Ailleurs, on **référence** : `var(--texte)`, `esp(6)`,
`typo(h2)`, `rayon(md)`, `$d-normal`, `@include ecran(lg)`. Une valeur
littérale hors `_tokens.scss` est une régression, et `php artisan design:check`
doit la signaler.

**Loi 2 — Aucun `!important` nouveau.**
Le socle gagne par l'ordre de cascade, pas par la surenchère. Le dépôt en
compte **55**, dont **23 dans `_socle.scss`** — ceux-là sont un héritage assumé
et **plafonné** (utilitaires Bootstrap `.text-muted`, `.bg-white`, qui
écrivaient des gris fixes illisibles en thème sombre). Les **32 autres**, dans
les feuilles historiques, disparaissent au Lot 2 : un `!important` qui ne sert
qu'à battre Bootstrap devient inutile dès que le socle gagne par l'ordre.
Après le Lot 2, le plafond est 23 et il ne remonte jamais.

**Loi 3 — Mobile first strict.**
Style de base = 320 px. Les `@media` **ajoutent**. Zéro `max-width`. Zéro
valeur qu'un écran plus large devrait défaire.

**Loi 4 — 4,5:1 pour tout texte, 3:1 pour tout tracé.**
Dans les **deux** thèmes, dans les **cinq** états. Un couple non mesuré est un
couple non livrable.

**Loi 5 — 44 px minimum pour toute cible tactile.**
`$tactile-min` (WCAG 2.5.5). Boutons, champs, liens de pagination, entrées de
menu, icônes cliquables. Aucune exception.

**Loi 6 — Le composant avant le balisage.**
S'il manque un composant, on l'**ajoute à la bibliothèque**. Jamais
`<button class="btn btn-primary">`, toujours `<x-button>`.

**Loi 7 — Le survol enrichit, il ne révèle pas.**
Ne jamais définir une `color` uniquement dans `:hover`. Ne jamais poser
`color` sur `a:link` ou `a:visited` — `a:link` a une spécificité de (0,1,1),
supérieure à `.btn-dark` (0,1,0) : la règle globale écrase la couleur du
bouton **au repos**, et `.btn-dark:hover` (0,2,0) la reprend au survol. Le
texte n'apparaît alors qu'au survol.

**Loi 8 — Aucun lien souligné.**
La distinction passe par la couleur. **Exception unique** : le contour de
focus clavier, renforcé (2 px, décalage 2 px), jamais supprimé.

**Loi 9 — Compte ≠ Profil.**
Le **compte** (`users`) : créer un compte, inscription, connexion, mon compte.
Le **profil** (`profiles`) : créer mon profil, mon profil professionnel,
publier mon profil. Le mot « profil » n'apparaît pas avant le tableau de bord.

**Loi 10 — Aucun texte en dur dans une vue.**
Tout passe par `lang/fr` et `lang/en`. La page publique s'affiche dans la
langue du **visiteur**, pas du porteur.

---

# PARTIE 3 — LE SYSTÈME D'ESPACEMENT, ÉCRIT EN ENTIER

C'est la partie que l'audit a montrée la plus dégradée : **781 usages pour 63
valeurs distinctes**. Ce qui suit ne laisse plus de place au jugement local.

## 3.1 L'échelle, et rien d'autre

```
esp(0)  = 0        esp(7)  = 32px
esp(1)  = 4px      esp(8)  = 40px
esp(2)  = 8px      esp(9)  = 48px
esp(3)  = 12px     esp(10) = 64px
esp(4)  = 16px     esp(11) = 80px
esp(5)  = 20px     esp(12) = 96px
esp(6)  = 24px     esp(13) = 128px
```

`esp()` lève une erreur de compilation sur une valeur hors carte. C'est
volontaire : le build échoue plutôt que de laisser passer un `esp(3.5)`.

## 3.2 Le rythme vertical — tableau normatif

Chaque ligne est une **décision**, pas une suggestion. À gauche la valeur
mobile (base), à droite ce que la rupture ajoute.

### Sections de page

| Élément | Mobile (320 → 639) | `ecran(lg)` 768+ | `ecran(xl)` 1024+ |
|---|---|---|---|
| Padding vertical d'une section vitrine | `esp(10)` 64px | `esp(12)` 96px | `esp(13)` 128px |
| Padding vertical d'une section applicative | `esp(7)` 32px | `esp(9)` 48px | `esp(9)` 48px |
| Gouttière horizontale de page | `esp(4)` 16px | `esp(6)` 24px | `esp(7)` 32px |
| Écart entre deux sections voisines | `esp(9)` 48px | `esp(11)` 80px | `esp(12)` 96px |
| Titre de section → premier contenu | `esp(6)` 24px | `esp(7)` 32px | `esp(7)` 32px |
| Sur-titre (`.t-over`) → titre | `esp(2)` 8px | `esp(2)` 8px | `esp(2)` 8px |
| Titre → chapeau (`.t-lead`) | `esp(3)` 12px | `esp(4)` 16px | `esp(4)` 16px |

### Cartes et panneaux

| Élément | Mobile | 768+ |
|---|---|---|
| Padding intérieur d'une carte | `esp(5)` 20px | `esp(6)` 24px |
| Padding d'une carte dense (liste, statistique) | `esp(4)` 16px | `esp(5)` 20px |
| En-tête de carte → corps | `esp(4)` 16px | `esp(4)` 16px |
| Corps → pied de carte | `esp(5)` 20px | `esp(6)` 24px |
| Écart entre deux cartes d'une grille | `esp(4)` 16px | `esp(6)` 24px |
| Écart entre deux cartes empilées | `esp(4)` 16px | `esp(5)` 20px |

### Formulaires — le détail qui compte le plus

| Élément | Valeur | Non négociable |
|---|---|---|
| Libellé → champ | `esp(2)` 8px | |
| Champ → texte d'aide ou erreur | `esp(2)` 8px | |
| Bas d'un groupe → libellé suivant | `esp(5)` 20px | |
| Hauteur d'un champ | `$champ-hauteur` 48px | **oui** |
| Taille de police d'un champ | 16px exactement | **oui** — iOS zoome en dessous |
| Padding horizontal d'un champ | `esp(4)` 16px | |
| Écart entre deux cases à cocher | `esp(3)` 12px | |
| Zone cliquable d'une case à cocher | 44px minimum | **oui** |
| Légende d'astérisque → premier champ | `esp(5)` 20px | |
| Dernier champ → barre d'actions | `esp(7)` 32px | |
| Écart entre deux boutons d'action | `esp(3)` 12px | |

### Listes, tableaux, navigation

| Élément | Mobile | 768+ |
|---|---|---|
| Ligne de tableau, padding vertical | `esp(4)` 16px | `esp(3)` 12px |
| Cellule, padding horizontal | `esp(4)` 16px | `esp(4)` 16px |
| Ligne de coordonnées (page publique) | 56px de haut | 56px |
| Tuile de réseau social | `$tuile-reseau` 64px | 64px |
| Entrée de menu latéral | 48px de haut, `esp(3)` de padding | idem |
| Barre d'actions de la page publique | `$action-mobile` 52px | 52px |
| Écart entre deux entrées de menu | `esp(1)` 4px | `esp(1)` 4px |

### Typographie — écarts internes

| Élément | Valeur |
|---|---|
| Paragraphe → paragraphe | `esp(4)` 16px |
| Fin de paragraphe → titre suivant | `esp(7)` 32px |
| Largeur de lecture maximale | `68ch` |
| Titre `h2` → `h3` imbriqué | `esp(5)` 20px |

## 3.3 Les trois règles qui tranchent les cas non listés

1. **On arrondit vers le haut de l'échelle, jamais entre deux crans.** Si
   `esp(4)` paraît serré et `esp(5)` large, on prend `esp(5)` : l'air ne se
   remarque pas, la compression se remarque toujours.
2. **Une seule direction de marge.** Les marges vont vers le bas
   (`margin-block-end`), jamais vers le haut. Deux marges qui se rencontrent
   produisent une valeur qu'aucun tableau ne décrit.
3. **L'écart appartient au parent.** On l'exprime en `gap` sur un conteneur
   `flex`/`grid`, pas en marge sur l'enfant. Un enfant qui porte sa propre
   marge ne se réutilise pas.

---

# PARTIE 4 — MOBILE FIRST, ÉCRIT EN ENTIER

## 4.1 Les huit ruptures, et à quoi chacune sert

| Nom | Largeur | Ce qu'elle représente | Ce qu'on y fait |
|---|---:|---|---|
| `xs` | 320px | **La base.** Aucun `@media` : c'est le style par défaut | Tout s'écrit ici d'abord |
| `sm` | 375px | iPhone SE / 13 mini | Retouche rare — l'échelle doit déjà tenir |
| `ms` | 480px | Grand téléphone en paysage | Grilles 1 → 2 colonnes pour les tuiles |
| `md` | 640px | Petite tablette | Grilles de cartes 2 colonnes |
| `lg` | 768px | **La rupture majeure.** Tablette / bureau étroit | Menu latéral fixe, tables en tableau, échelle typographique « bureau » |
| `xl` | 1024px | Bureau | Grilles 3 colonnes, gouttières élargies |
| `xxl` | 1280px | Grand bureau | Largeur de contenu plafonnée |
| `max` | 1440px | Très grand écran | Rien de neuf : on plafonne, on n'étale pas |

**Aucune requête `max-width` nulle part.** Une requête `max-width` oblige à
défaire ce qu'on vient de faire, et c'est ainsi qu'un style finit par dépendre
de l'ordre des déclarations.

Il en reste **18** à ce jour, dont **15 à retirer** (trois vivent dans des
feuilles gelées). C'est la liste exhaustive :

| Feuille | Requêtes | Note |
|---|---:|---|
| `_app-shell.scss` | 4 | `575.98px` ×3, `767.98px` ×1 — les valeurs de Bootstrap, pas les nôtres |
| `_admin.scss` | 3 | |
| `_components.scss` | 2 | |
| `_dashboard.scss` | 2 | |
| `_carte-publique.scss` | 2 | |
| `_auth.scss` | 1 | |
| `_whatsapp.scss` | 1 | |
| `_phone.scss` | 2 | **gelé** — on laisse |
| `_carte-publique-matiere.scss` | 1 | **gelé** — on laisse |

Les bornes `575.98px` et `767.98px` sont celles de Bootstrap : elles ne
figurent pas dans `$ruptures`, et c'est le signe le plus net qu'on a suivi un
autre système que le nôtre. Chaque requête se réécrit en `@include ecran()`
avec la borne de `$ruptures` la plus proche **au-dessus**, en inversant la
logique — jamais en ajoutant un cran à l'échelle pour coller à Bootstrap.

## 4.2 Les six largeurs de vérification obligatoires

Un écran n'est pas terminé avant d'avoir été vu à ces six largeurs, **dans les
deux thèmes**. Douze rendus par écran.

| Largeur | Ce qu'on y cherche |
|---:|---|
| **320px** | Le pire cas. Aucun débordement horizontal, aucun texte tronqué, aucun bouton sous 44px |
| **360px** | Le téléphone Android le plus répandu au Sénégal |
| **390px** | iPhone récent — dont l'encoche et la barre du bas |
| **768px** | La bascule : le menu passe en fixe, les tableaux redeviennent des tableaux |
| **1024px** | Le bureau réel |
| **1440px** | Le plafonnement : le contenu ne doit pas s'étirer indéfiniment |

## 4.3 Les onze règles mobiles à vérifier écran par écran

1. **Zéro défilement horizontal à 320px.** Aucune exception. Les contenus
   larges (tableaux, code, diagrammes) défilent dans leur propre conteneur
   `overflow-x: auto`, jamais la page.
2. **Toute cible ≥ 44px.** Y compris les icônes de suppression dans une liste,
   les chevrons, les liens de pagination.
3. **Tout champ à 48px de haut et 16px de police.** Les deux ensemble, sans
   exception : c'est la condition pour qu'iOS ne zoome pas.
4. **Les tableaux basculent en cartes sous 768px.** Chaque cellule porte son
   libellé. Un tableau qui défile latéralement sur téléphone n'est pas lu.
5. **Les actions primaires sont à portée du pouce** : en bas de l'écran ou
   dans une barre collante, jamais en haut à droite seulement.
6. **Le menu latéral est un offcanvas Bootstrap natif** (`data-bs-*`) sous
   768px, fixe au-dessus. Sans JavaScript, le lien reste atteignable.
7. **Une seule colonne sous 480px.** Aucune grille à deux colonnes sur
   téléphone : deux colonnes de 140px ne contiennent rien.
8. **Les images portent `max-width: 100%` et un ratio explicite.** Une image
   sans dimension déclarée fait sauter la mise en page à son arrivée.
9. **`min-height: 100dvh`, avec repli `100vh`.** La barre d'adresse mobile
   change de hauteur ; `vh` seul laisse une bande de fond.
10. **Les longues valeurs se coupent** : `overflow-wrap: anywhere` sur les
    e-mails, URL, slugs. Un e-mail long ne doit pas élargir la page.
11. **`prefers-reduced-motion` respecté** : toute animation se réduit à une
    apparition instantanée.

## 4.4 Le piège de la révélation au défilement

**C'est le module JavaScript qui ajoute `.is-hidden` au démarrage.** Le CSS
laisse les éléments **visibles par défaut**. Si le script échoue, le contenu
reste lisible. Ne jamais masquer en CSS ce que seul le JavaScript peut
révéler.

---

# PARTIE 5 — LES LOTS D'EXÉCUTION

Neuf lots. Un par commit. Validation entre chaque.

---

## LOT 0 — L'OUTILLAGE (à faire en premier, sans quoi rien n'est vérifiable)

**Pourquoi d'abord** : on ne peut pas piloter la réduction de 1 576 valeurs en
dur sans un compteur. Et une règle sans garde-fou se dégrade dès le premier
correctif pressé.

### 0.a — `php artisan design:check`

Nouvelle commande `app/Console/Commands/DesignCheck.php`.

Elle balaie `resources/sass/**/*.scss` et `resources/views/**/*.blade.php` et
signale :

- toute couleur littérale (`#rgb`, `#rrggbb`, `#rrggbbaa`, `rgb()`, `rgba()`,
  `hsl()`) hors `_tokens.scss` ;
- toute longueur littérale en `px`, `rem`, `em` hors `_tokens.scss`, sauf la
  liste blanche ci-dessous ;
- toute durée littérale (`ms`, `s`) hors `_tokens.scss` ;
- tout `!important` **ajouté** au-delà du compte de référence ;
- tout `@media` contenant `max-width` ;
- tout `text-decoration: underline` ;
- tout `style="..."` dans une vue hors e-mails et `printable`.

**Liste blanche justifiée** (à écrire dans la commande, avec la raison) :
`0`, `1px` et `2px` pour les bordures et contours, `100%`, `50%`, `1em`,
`.9em`, les valeurs des `@font-face`, `resources/views/emails/**`,
`resources/views/profile/printable.blade.php`.

Sortie : un tableau par catégorie, avec fichier, ligne, valeur, et **le total
en pied**. Code de retour `1` si le total dépasse le **plafond** enregistré
dans `config/design.php`, `0` sinon.

Le plafond est un cliquet : il ne remonte jamais. Chaque lot le baisse, et la
valeur baissée est inscrite dans le commit.

### 0.b — `php artisan design:audit`

La commande qui **produit** `audit-design.json`. Elle existe aujourd'hui hors
du dépôt, ce qui rend le chiffre de 1 598 invérifiable. Elle sort le JSON et
un résumé lisible.

Attention à l'erreur de méthode déjà commise trois fois : elle **sépare le
code du texte affiché**, n'inclut pas les commentaires, n'inclut pas les
tracés SVG, et n'écarte pas les attributs de composants — qui sont de vraies
occurrences. C'est ce qui a fait passer le relevé de 1 963 à 651 puis à 1 598.

### 0.c — `php artisan design:contraste`

Le calcul de contraste sur les valeurs livrées, pour les 18 couples de
`_tokens.scss` plus tout couple ajouté. Sortie : ratio, seuil applicable
(4,5:1 texte / 3:1 tracé), verdict. Code de retour `1` au premier échec.

### 0.d — La vérification navigateur, qui a manqué

Playwright a refusé la connexion au dernier essai. **À réparer avant le Lot 2**,
parce que la suite se juge à l'écran.

Le relevé fiable passe par le **vrai mécanisme** : le thème rendu par le
serveur, pas une classe posée en JavaScript dans une iframe — c'est cette
erreur qui annonçait 7 défauts de contraste sur la page d'accueil quand il y
en avait un.

Script `tests/Browser/contraste.spec.js` : pour chacune des 22 pages, dans les
deux thèmes, aux six largeurs, il relève tout couple texte/fond du DOM rendu
et échoue sous 4,5:1. Le texte posé sur une **image** est exclu et compté à
part — son contraste dépend de la photo et ne se vérifie pas statiquement.

### 0.e — Le budget CSS

`npm run build` puis relevé de la taille des fichiers de
`public/build/assets/`. Le chiffre d'aujourd'hui devient la **référence**. Un
lot qui l'augmente de plus de 5 % s'explique dans son commit.

### Acceptation du Lot 0

- [ ] Les trois commandes existent, sont testées, et documentées dans
      `docs/DESIGN.md` (section provisoire).
- [ ] `php artisan design:check` sort le chiffre de départ, et il est inscrit
      dans `config/design.php`.
- [ ] Playwright se connecte et le script de contraste tourne sur les 22 pages.
- [ ] La taille CSS de référence est notée.
- [ ] Les 663 tests passent toujours.

---

## LOT 1 — LA SOURCE UNIQUE, POUR DE BON

**Objectif** : à la fin de ce lot, `_tokens.scss` est réellement le seul
fichier qui contient des valeurs, et la documentation ne dit plus le contraire.

### 1.a — Absorber `_variables.scss`

Les 68 lignes se répartissent en trois populations :

1. **Ce qui double un token** (`$blanc`, `$texte`, `$vert-*`…) → remplacer
   chaque usage par `var(--…)` ou par la variable de `_tokens.scss`, puis
   supprimer la déclaration.
2. **Ce qui surcharge Bootstrap** (`$enable-gradients: false`, `$primary`,
   `$border-radius`…) → **rester** dans `_variables.scss`, qui devient
   exclusivement *le fichier de surcharge Bootstrap*, avec un en-tête qui le
   dit. Chaque valeur y **référence** un token, jamais un littéral.
3. **Ce qui n'est utilisé nulle part** → supprimer, et le dire dans le commit.

`_base.scss` ne doit plus écrire `background:$blanc` ni `color:$texte` : le
socle pose déjà ces surfaces sur `html` et `body`. Retirer les déclarations
mortes plutôt que les traduire.

### 1.b — Trancher la question de la police

Deux issues, pas de troisième :

- **Option A — on sert la police.** Déposer les cinq `.woff2` dans
  `public/fonts/`, décommenter les cinq `@font-face` de `_base.scss`, vérifier
  `font-display: swap`, mesurer le poids ajouté, ajouter
  `<link rel="preload">` pour la graisse 400 seule. **Jamais un CDN** : un
  visiteur en 3G paie une résolution DNS, un handshake TLS et un aller-retour
  de plus pour un fichier que l'hébergeur sert déjà.
- **Option B — on assume la pile système.** Retirer `'QrID'` de `$font`,
  supprimer les cinq `@font-face` commentés, et écrire dans `docs/DESIGN.md`
  que le caractère vient de l'échelle, des graisses et de l'interlettrage
  (`-0.025em` sur `h1`, `-0.03em` sur `display`).

**Recommandation : Option B pour le lancement, Option A en V2.** Le poids est
un critère du produit, et la pile système ne coûte rien. Mais c'est une
décision à prendre explicitement, pas à laisser en suspens.

### 1.c — Un seul vert de marque

`#0B5D3B` (e-mails, `docs/DESIGN.md`) disparaît. Le vert du produit est
`#0B3B2E`. Répercuter dans les 14 gabarits d'e-mail — à la main, valeur par
valeur, puisque le CSS en ligne y est imposé.

### Acceptation du Lot 1

- [ ] `grep -c "px" resources/sass/_variables.scss` retourne 0, ou chaque
      occurrence restante référence un token.
- [ ] `_base.scss` n'écrit plus aucune couleur ni police.
- [ ] `#0B5D3B` n'apparaît nulle part dans le dépôt.
- [ ] La question de la police est tranchée, et la décision est écrite.
- [ ] Le plafond de `design:check` a baissé, et le nouveau chiffre est dans le
      commit.
- [ ] Rendu identique à l'œil sur les 22 pages, sauf les changements listés.

---

## LOT 2 — LES FEUILLES HISTORIQUES, DANS L'ORDRE DU RISQUE

**Le principe** : on ne réécrit pas une feuille, on la **traduit**. Chaque
valeur littérale devient une référence. Le rendu ne doit pas bouger, sauf là
où la traduction corrige un défaut mesuré — et alors on le dit.

**L'ordre est imposé, du moins risqué au plus risqué.** On ne commence pas par
`_admin.scss` parce qu'il est le plus gros : on commence par les petites
feuilles pour éprouver la méthode sur peu de surface.

| Ordre | Feuille | px | Ce qu'il faut savoir |
|---:|---|---:|---|
| 1 | `_card.scss` | 19 | Petit, bien délimité. Sert de banc d'essai à la méthode. |
| 2 | `_brand.scss` | 22 | Le médaillon est déjà corrigé ; il reste le reste. |
| 3 | `_whatsapp.scss` | 27 | Contient `#25D366` — la couleur **de WhatsApp**, pas la nôtre : elle devient un token nommé `$marque-whatsapp`, avec un commentaire disant qu'elle est imposée par un tiers. Le fichier le dit déjà en commentaire, il suffit de le rendre exécutable. |
| 4 | `_contact.scss` | 35 | Même cas pour `#25D366`. **Il est aussi recopié dans `_dashboard.scss`** (lignes 114 et 121) : les trois usages passent par le même token, sinon la divergence revient. |
| 5 | `_topbar.scss` | 37 | Barre supérieure : vérifier la hauteur tactile de la bascule de thème et du sélecteur de langue. |
| 6 | `_dashboard.scss` | 117 | Statistiques et blocs. Attention aux variations de tendance : `.adm-stat__var.is-baisse` mesurait 2,52:1 en thème sombre. |
| 7 | `_carte-publique.scss` | 113 | **Prudence** : 448 lignes emploient les alias de `_theme.scss`. On traduit les littéraux, on ne renomme aucun alias. |
| 8 | `_auth.scss` | 190 | Trois familles de champs y vivent (`.auth-*`). Les ramener sur celle du socle, pas l'inverse. |
| 9 | `_components.scss` | 222 | Le cœur de la bibliothèque. Contient `#000` (5 occurrences) et `#0B3B2E` **recopié à la main** alors qu'il est déjà une variable. |
| 10 | `_app-shell.scss` | **368** | Le plus gros chantier de rendu : menu latéral, offcanvas, bandeau d'abonnement, bloc support. C'est là que se joue le mobile. |
| 11 | `_admin.scss` | **363** | Le plus gros en lignes (1 736). L'en-tête sombre est un parti pris volontaire : **il reste sombre**, mais ses valeurs deviennent des tokens. Contient `#1DC8FF`, `#FF7900`, `#CD1719` — les couleurs des opérateurs (Wave, Orange Money, Free) : elles deviennent des tokens nommés, avec la même mention « imposée par un tiers ». |

### La méthode, feuille par feuille

1. Lister les valeurs de la feuille depuis `audit-design.json` — elles y sont
   déjà, avec le numéro de ligne.
2. Traduire chaque littéral : couleur → `var(--…)`, longueur → `esp()` /
   `typo()` / `rayon()`, durée → `$d-*`, rupture → `@include ecran()`.
3. **Toute valeur qui ne tombe pas sur un cran** se résout par le tableau de
   la Partie 3, ou par la règle « on arrondit vers le haut ». On ne crée pas
   un cran pour sauver une valeur : `7px` n'a jamais été choisi.
4. Supprimer les `@media (max-width: …)` en inversant la logique.
5. Lancer `design:check` : le compte de la feuille doit tomber à zéro, hors
   liste blanche.
6. Vérifier les 6 largeurs × 2 thèmes sur les écrans qui emploient la feuille.
7. Un commit par feuille, avec le chiffre avant/après.

### Acceptation du Lot 2

- [ ] `design:check` retourne **0 valeur en dur** dans les 11 feuilles
      traitées.
- [ ] Aucun `@media (max-width)` ne subsiste hors feuilles gelées.
- [ ] Aucun `!important` ajouté.
- [ ] Le script de contraste passe sur les 22 pages, 2 thèmes, 6 largeurs.
- [ ] Les 663 tests passent.
- [ ] La taille CSS n'a pas augmenté de plus de 5 %.

---

## LOT 3 — LES COMPOSANTS BLADE : 44 EXISTANTS, UNE SEULE APPARENCE

**Le vrai problème n'est pas leur nombre, c'est le nombre de familles CSS
concurrentes qui décrivent la même chose.** L'audit a compté **14 racines
distinctes pour le champ** de formulaire.

### 3.a — Le champ, cas critique

Trois systèmes parallèles pour un même objet : public (`.f__*`),
authentification (`.auth-*`), administration (`.adm-champ`, `.adm-select`).
Une correction de hauteur tactile doit aujourd'hui être faite trois fois.

Décision : **`x-input` est le composant canonique.** `x-field`, `x-auth-field`
et les champs d'administration deviennent des enveloppes minces autour de lui,
ou disparaissent.

| Composant | Devenir |
|---|---|
| `components/input.blade.php` | **Canonique.** Porte le libellé, l'astérisque déduit de la règle de validation, l'aide, l'erreur, `aria-required`, `aria-describedby`, `aria-invalid` |
| `components/field.blade.php` | Enveloppe → `x-input`, ou suppression après migration des vues du parcours de création |
| `components/auth-field.blade.php` | Enveloppe → `x-input` |
| `components/auth-password.blade.php` | Enveloppe → `x-password` |
| `components/phone-field.blade.php` | Enveloppe → `x-input` + `x-phone` (sélecteur d'indicatif) |
| `components/select.blade.php`, `textarea`, `checkbox`, `radio`, `password` | Conservés, alignés sur la même anatomie |

**L'anatomie d'un champ, décrite une fois** :

```
[ .t-caption libellé ]  [ * rouge si requis ]  [ « optionnel » gris sinon ]
                        ↕ esp(2) = 8px
[ champ : 48px de haut, 16px de police, esp(4) de padding,
  rayon(sm), 1px var(--bordure-f), fond var(--carte) ]
                        ↕ esp(2) = 8px
[ .t-caption aide en var(--texte-3)  OU  erreur en var(--danger) ]
```

- L'astérisque est **porté par le composant et déduit de la règle de
  validation**, jamais écrit à la main dans une vue.
- Un marque-place n'est jamais atténué : il se lit. `$bordure-champ` à 48 %
  (3,16:1) et non `$bordure-forte` à 14 % (1,33:1) — WCAG 1.4.11 exige 3:1
  pour la délimitation d'un composant de saisie.
- L'erreur de validation de champ **reste** sous son champ et ne disparaît
  pas. Seuls les messages flash globaux s'effacent, en 30 secondes maximum, en
  fondu, et **jamais masqués par défaut dans le HTML** — sans JavaScript, ils
  restent affichés.

### 3.b — Les autres familles concurrentes

| Famille | Racines aujourd'hui | Cible |
|---|---:|---|
| Bouton | 2 (`.btn`, `.adm-btn`) | 1 — `%bouton` du socle |
| Carte | 3 (`.card`, `.carte`, `.adm-*`) | 1 |
| Tableau | 3 (`.table`, `.liste`, `.adm-table`) | 1, avec bascule en cartes sous 768px |
| Badge | 2 (`.badge`, `.tagline`) | 1 |
| Modale | 2 (`.offcanvas`, `.adm-modale`) | 1 — offcanvas Bootstrap natif |
| Alerte | 2 (`.alert`, `.mail-spam`) | 1 (les e-mails restent à part) |
| **État vide** | **0 — chaque page réinvente** | 1 — `x-empty-state` employé partout |

### 3.c — Les composants à créer

- **`x-empty-state`** existe déjà en composant mais **aucune classe CSS
  dédiée** : chaque page réinvente son vide. Lui donner une anatomie unique
  (icône `esp(10)`, titre `h3`, message `.t-lead` en `var(--texte-3)`, action).
- **`x-page-header`** : le couple sur-titre / titre / chapeau / actions,
  répété dans presque chaque vue applicative avec un espacement différent
  chaque fois.
- **`x-section`** : le conteneur de section vitrine, qui porte le padding
  vertical du tableau de la Partie 3 et rien d'autre.
- **`x-stack`** / **`x-grid`** : deux conteneurs d'espacement, `gap` pris sur
  l'échelle. C'est ce qui empêche une marge de réapparaître sur un enfant.

### Acceptation du Lot 3

- [ ] Une seule racine CSS par famille, vérifiée par `grep`.
- [ ] `x-input` rend exactement la même apparence dans les trois contextes
      (public, authentification, administration) — capture comparée.
- [ ] Aucune vue ne contient de balisage de champ écrit à la main.
- [ ] Chaque astérisque vient de la règle de validation ; aucun n'est écrit
      dans une vue.
- [ ] Tous les états vides passent par `x-empty-state`.

---

## LOT 4 — ÉCRAN PAR ÉCRAN, LES 158 VUES

**On ne traite pas écran par écran ce qui est transversal.** Les Lots 2 et 3
ont déjà tout unifié ; ce lot est une **recette**, pas une réécriture. Pour
chaque écran : les 6 largeurs, les 2 thèmes, la liste de contrôle de la
Partie 6.

### 4.1 Les 6 layouts — à traiter avant les écrans

| Layout | Fichier | Points de vigilance |
|---|---|---|
| Vitrine | `layouts/public.blade.php` | Navbar + CTA, footer complet (légal, WhatsApp). Sélecteur de langue **dans le pied** pour les pages publiques |
| Authentification | `layouts/auth.blade.php` | Centré, épuré, sans navigation. Vérifier le centrage à 320px avec clavier ouvert |
| Application | `layouts/app.blade.php` | Menu latéral (offcanvas sous 768px), bandeau d'abonnement, bloc support. **Le plus gros risque mobile** |
| Administration | `layouts/admin.blade.php` | En-tête **sombre volontairement distinct** — il reste sombre |
| Profil public | `layouts/public-profile.blade.php` | **Le plus léger** : ni navbar ni footer, pas de JS applicatif. Ne rien y ajouter |
| Erreurs | `errors/layout.blade.php` | Souvent oublié. Les six pages d'erreur passent par la recette comme les autres |

Chaque layout porte un lien d'évitement « Aller au contenu », un `<h1>` unique
par page, et une hiérarchie de titres sans saut de niveau.

### 4.2 La vitrine — 8 sections + 1 accueil

`welcome.blade.php` et `landing/sections/` : `hero`, `figures`, `showcase`,
`steps`, `trades`, `plans`, `final-cta`, `contact`, plus
`landing/partials/phone-card`.

| Section | À vérifier |
|---|---|
| `hero` | Le titre en `.t-display` : 34px mobile / 52px bureau. Aucun débordement à 320px. La maquette de téléphone ne pousse pas la page |
| `figures` | Les chiffres sur l'échelle, pas en tailles ad hoc |
| `showcase` | Images en `max-width: 100%` avec ratio déclaré |
| `steps` | Numérotation lisible au repos, pas au survol |
| `trades` | **Défaut corrigé à surveiller** : le bandeau des métiers était à `opacity:.45` → 1,86:1. L'opacité est retirée, l'estompage vient du masque latéral (5,03:1). Ne pas la réintroduire |
| `plans` | Grille 1 colonne sous 480px. Le plan recommandé se distingue par la bordure et le badge, pas par une ombre lourde. Prix en FCFA entiers, format « 3 500 FCFA » |
| `final-cta` | Fond `--marque-doux`, pas un dégradé |
| `contact` | Champs à 48px, `#25D366` devenu token |

**Vocabulaire** : tout appel à l'action de la vitrine mène à l'**inscription**
et emploie donc le vocabulaire du **compte** (« Créer un compte », « Commencer
gratuitement »). Le mot « profil » n'y apparaît pas.

### 4.3 Authentification — 7 vues

`auth/login`, `register`, `forgot-password`, `reset-password`,
`confirm-password`, `registration/pending`, `registration/expired`.

- Formulaire à une colonne, largeur plafonnée, centré verticalement seulement
  si la hauteur le permet.
- `x-auth-tabs` : les deux onglets à 44px minimum, l'onglet actif lisible au
  repos.
- `x-google-button` : hauteur alignée sur `x-button`, même rayon, même
  padding.
- Le formulaire d'inscription ne demande **aucune** information
  professionnelle et n'écrit que dans `users` et `pending_registrations`.
- Aucun texte d'inscription ne prononce le mot « profil ».

### 4.4 Espace client — 12 vues

`dashboard/active`, `dashboard/empty`, `dashboard/partials/*` (stats,
activity-chart, card-block, carte-physique, side-panel), `profil/index`,
`account/edit` + ses 3 partiels, `notifications/index`,
`statistiques/index`, `search/results`, `legal/page`.

| Écran | À vérifier |
|---|---|
| `dashboard/empty` | C'est le **premier écran** d'un compte sans profil, et l'état normal après confirmation d'e-mail. Il porte le bouton « Créer mon profil » — première apparition du mot « profil » dans tout le produit |
| `dashboard/active` | Grille des blocs : 1 colonne sous 640px, 2 sous 1024px. Le bandeau d'abonnement ne masque jamais une action |
| `partials/stats` | `x-stat` : la tendance lisible dans les deux thèmes. `.is-baisse` mesurait 2,52:1 en sombre |
| `partials/activity-chart` | Un graphique n'est pas un texte : tracé à 3:1, mais toute **étiquette** à 4,5:1. Alternative textuelle obligatoire |
| `partials/carte-physique` | Statut en clair, adresse corrigeable tant que le statut est `pending` |
| `account/edit` | Trois formulaires distincts sur une page : `errorBag` distinct pour chacun, et l'erreur reste dans le bon bloc |
| `notifications/index` | Liste paginée. État vide via `x-empty-state` |
| `statistiques/index` | Idem graphique |
| `search/results` | Résultats paginés, état vide, et **aucun résultat n'est un lien mort** |
| `legal/page` | `.prose`, largeur `68ch`, rythme de paragraphe du tableau Partie 3 |

### 4.5 Le parcours de création — 3 étapes + aperçu

`profile/wizard/step-1|2|3`, `profile/preview`, `components/step-shell`,
`components/wizard-progress`, `profile/partials/qr-placeholder`.

**Le chrono est un critère d'acceptation, pas un souhait** : moins de 3
minutes du tableau de bord à l'aperçu, rechargements compris.

- **Quatre champs obligatoires en tout** : prénom, nom, fonction (étape 1),
  téléphone (étape 2). L'étape 3 ne demande aucune saisie — ses deux champs
  arrivent pré-cochés.
- `wizard-progress` : l'étape courante lisible **au repos**, les étapes
  franchies et à venir distinguées autrement que par une seule nuance de gris.
- La navigation est serveur : chaque « Continuer » est un POST suivi d'une
  redirection. Aucune étape n'est atteignable si la précédente ne l'est pas.
- Sans JavaScript : le champ fichier fonctionne, le bouton d'ajout de réseau
  reste un `submit`, vider deux champs retire un réseau, la teinte cochée est
  déjà appliquée par une variable CSS.
- Les cartes de modèle et la zone de dépôt utilisent `$bordure-champ` (3,16:1),
  pas `$bordure-forte`.

### 4.6 Abonnement, paiement, carte physique — 4 vues

`abonnement/paiement`, `abonnement/confirmation`, `abonnement/simulation`,
`carte-physique/adresse`.

- **Message commercial exact, identique partout** : « 3 500 FCFA les 3 mois —
  votre carte PVC offerte à l'activation. » Le prix ne change jamais : le
  client ne doit à aucun moment avoir l'impression d'avoir acheté la carte.
- Les couleurs d'opérateur (Wave, Orange Money, Free) deviennent des tokens
  nommés. Le logo est un SVG en ligne, jamais une image distante.
- L'adresse de livraison se collecte **après** le choix du moyen de paiement.
  Champ téléphone avec sélecteur d'indicatif, Sénégal par défaut, pays
  d'Afrique de l'Ouest en tête.
- Délai de livraison réaliste annoncé à l'écran **et** dans l'e-mail, depuis
  une valeur de configuration.

### 4.7 Pages publiques — 5 vues, le trafic le plus exposé

`public/profile`, `public/carte-inactive`, `public/demo`, `carte/qr`,
`components/carte-publique`, `components/couverture`.

**C'est ici que la qualité se juge** : ces pages s'ouvrent après un scan, sur
le téléphone d'un inconnu, souvent en 3G.

- Rien qui charge : pas de JS applicatif, pas de police distante, pas d'image
  externe, SVG en ligne.
- Mesures déjà acquises, à ne pas régresser : coordonnées **56px**, tuiles de
  réseau **64px**, barre d'actions **52px**, nom sur l'échelle `h1` et
  **jamais tronqué**.
- **Le voile de couverture est fonctionnel, pas décoratif** : c'est lui qui
  rend le nom lisible sur une photo dont on ne sait rien. `$voile-couverture`
  est un token pour cette raison.
  **À faire ici** : mesurer le pire cas sur une couverture **claire** et
  renforcer le voile si nécessaire. `.pubc__nom`, `.pubc__role`,
  `.pubc__entreprise` ressortent à 1:1 dans l'analyse du DOM — c'est un faux
  positif (le blanc est sur un `<img>` positionné en absolu), mais la garantie
  repose entièrement sur le voile, et elle n'a pas été mesurée.
- La page s'affiche dans la langue du **visiteur**.

### 4.8 Administration — 11 vues

`admin/overview`, `clients/index`, `clients/show`, `profiles/index`,
`payments/index`, `subscriptions/index`, `cartes/index`, `templates/index`,
`settings/index`, `statistics`, `system-health`, `audit/index`.

- L'en-tête sombre est un parti pris : **il reste**. Ses valeurs deviennent
  des tokens.
- **Tous les tableaux basculent en cartes sous 768px**, chaque cellule portant
  son libellé. C'est le plus gros travail mobile de cette famille.
- `admin/statistics` contient **8 attributs `style=`** — les seuls styles en
  ligne applicatifs du projet hors e-mails et PDF. Ils partent.
- Les badges de statut sont le point noir de l'audit : `.badge.text-bg-light`
  gardait son fond clair `#F2F3F1` **y compris en thème sombre**, jusqu'à
  2,06:1. Vérifier chaque statut affiché ici.
- Toute liste est paginée, toute relation affichée est *eager loaded*.
- `admin/cartes` : écran « Cartes à produire », sélection multiple, lots,
  export CSV. Une sélection multiple sur téléphone demande des cases à 44px.

### 4.9 Erreurs — 6 vues + layout

`errors/403`, `404`, `419`, `429`, `500`, `503`.

Souvent oubliées, et ce sont les pages qu'un visiteur voit au pire moment.
Même layout, même échelle, un message utile, une action de sortie qui n'est
jamais `href="#"`.

### 4.10 Acceptation du Lot 4

Pour **chaque** écran, sans exception :

- [ ] 6 largeurs × 2 thèmes vérifiées, capture archivée.
- [ ] Zéro défilement horizontal à 320px.
- [ ] Toute cible ≥ 44px.
- [ ] Tout couple texte/fond ≥ 4,5:1, tout tracé ≥ 3:1.
- [ ] Un `<h1>` unique, hiérarchie sans saut.
- [ ] Aucun texte en dur : tout vient de `lang/`.
- [ ] Aucun `href="#"`, aucun bouton sans action.
- [ ] Aucun style en ligne.
- [ ] Rendu correct **sans JavaScript**.
- [ ] Vocabulaire compte/profil respecté.

---

## LOT 5 — E-MAILS, PDF, CARTE PVC

Trois surfaces qui **ne consomment pas** les tokens, et qui doivent malgré
tout s'y aligner.

### 5.a — Les 14 gabarits d'e-mail

`emails/layout`, `emails/partials/*`, et les 11 messages (bienvenue,
confirmation d'inscription, réinitialisation, mot de passe changé, paiement
abouti, paiement refusé, profil publié, rappel de profil, abonnement expirant,
abonnement expiré, déjà inscrit) — chacun en version HTML **et** texte.

- Le CSS en ligne est **imposé** : aucun client ne lit une feuille externe, et
  beaucoup ignorent les propriétés CSS personnalisées. On ne cherche pas à
  faire consommer les tokens.
- En revanche, **alignement à la main, valeur par valeur** : `#0B5D3B` →
  `#0B3B2E`, et les gris (`#1E293B`, `#94A3B8`, `#F1F5F9`) sur les tokens
  équivalents.
- Écrire un commentaire en tête de `emails/layout.blade.php` : quelles valeurs
  sont recopiées, depuis quel token, et qu'un changement de token oblige à
  revenir ici. Sans ce commentaire, la divergence reviendra.
- La version texte de chaque e-mail dit la même chose que la version HTML.
- L'e-mail part dans la langue du destinataire.
- Largeur 600px, une seule colonne, boutons à 44px de haut.

### 5.b — `profile/printable.blade.php`

Le bloc `<style>` de 194 lignes est légitime : le rendu PDF n'accède à aucune
feuille externe. Même traitement que les e-mails — alignement manuel et
commentaire d'en-tête.

### 5.c — La carte PVC

**Rien à faire.** `carte-qrid.html` est la référence figée. On vérifie
seulement que le rendu écran n'a pas dérivé : angles vifs, blanc dominant, nom
en vert foncé très grand, QR en vert foncé sur blanc, fonction en gris ou vert
atténué, liseré vert foncé sur un bord.

---

## LOT 6 — `/design-system` ET `docs/DESIGN.md`

Ce lot est celui qui rend la refonte **durable**. Sans lui, la prochaine page
écrite recommencera l'ancien système, en toute bonne foi.

### 6.a — La page `/design-system`, refaite

`resources/views/design-system.blade.php` : 55 attributs `style=` et 12
couleurs en dur. Elle se refait entièrement, **sans un seul style en ligne** —
une page de référence qui viole les règles qu'elle documente ne documente
rien.

Elle affiche, dans les deux thèmes :

1. **Les tokens en vrai** : chaque couleur avec son nom, sa valeur, et son
   ratio de contraste **calculé et affiché** sur les trois surfaces.
2. **L'échelle typographique** : les 9 tailles, mobile et bureau côte à côte.
3. **L'échelle d'espacement** : les 13 valeurs, visualisées.
4. **Les 6 rayons, les 4 ombres, les 4 durées.**
5. **Chaque variante de bouton dans ses CINQ états côte à côte** — c'est le
   dispositif qui fait sauter aux yeux un texte invisible au repos.
6. **Chaque champ dans ses états** : vide, rempli, focus, erreur, désactivé,
   avec aide.
7. **Chaque badge d'état** sur les trois surfaces.
8. **Le rythme vertical** : un exemple de section, de carte, de formulaire,
   avec les valeurs annotées.
9. **Les 6 largeurs** en cadres redimensionnables.

`design-system/cartes-publiques.blade.php` reçoit le même traitement.

### 6.b — `docs/DESIGN.md`, réécrit intégralement

Le fichier actuel documente un système qui n'existe plus (voir 0.4). Il se
réécrit, et il devient **la seule documentation de design du projet**.

Plan imposé :

1. Le concept, en une phrase et sept partis pris (Partie 1 de ce document).
2. Les dix lois (Partie 2).
3. Les tokens : palette, typographie, espacement, rayons, ombres, mouvement,
   ruptures, zones tactiles — **valeurs exactes, reprises de `_tokens.scss`,
   avec les ratios mesurés**.
4. Le tableau du rythme vertical (Partie 3), intégral.
5. Les règles mobile first (Partie 4), intégrales.
6. La bibliothèque de composants : anatomie de chacun, exemple d'appel Blade.
7. Les 6 layouts.
8. La règle Compte ≠ Profil.
9. La règle JavaScript.
10. Les interdits.
11. Le protocole de vérification et les trois commandes.
12. Ce qui est gelé, et pourquoi.

**Aucune valeur n'y est écrite qui ne soit dans `_tokens.scss`.** Le fichier
renvoie au token, il ne le recopie pas — sauf dans les tableaux de référence,
et alors une note dit qu'un changement de token oblige à revenir ici.

### Acceptation du Lot 6

- [ ] `/design-system` ne contient aucun style en ligne ni couleur en dur.
- [ ] Les cinq états de chaque bouton sont visibles côte à côte.
- [ ] `docs/DESIGN.md` ne contredit `_tokens.scss` sur aucun point — vérifié
      valeur par valeur.
- [ ] `#0B5D3B` et l'ancienne échelle d'espacement n'apparaissent plus dans
      `docs/`.

---

## LOT 7 — LE GARDE-FOU PERMANENT

Sans ce lot, la dette revient. Elle est revenue une fois déjà.

- **`design:check` dans le CI**, en échec bloquant. Le plafond de
  `config/design.php` est un cliquet : il ne remonte jamais.
- **`design:contraste` dans le CI**, en échec bloquant.
- **Le script Playwright de contraste** dans le CI, sur les 22 pages.
- **Un test qui vérifie l'ordre des `@import` de `app.scss`** : le socle en
  dernier, `_theme-dark` avant lui. Le dernier commit a montré qu'un ordre
  d'`@import` peut décider d'une couleur — ce n'est pas une décision de
  design, donc elle se verrouille.
- **Un test qui compte les racines CSS par famille** et échoue si une seconde
  apparaît. C'est ce qui a produit 4 systèmes de boutons et 5 de champs.
- **Un test de budget CSS** : échec au-delà de +5 % de la référence.

---

## LOT 8 — LA RECETTE FINALE

- Les 663 tests passent.
- Les trois commandes `design:*` sortent zéro défaut.
- Le script navigateur passe : 22 pages × 2 thèmes × 6 largeurs.
- Parcours complet en conditions réelles, sur un vrai téléphone, en 3G
  simulée : inscription → confirmation → tableau de bord → création de profil
  → aperçu → publication → paiement → scan de la carte → page publique.
  **Chrono du parcours de création sous 3 minutes.**
- Le même parcours **sans JavaScript**.
- Le même parcours **en anglais**.
- Les 6 pages d'erreur.
- Les 14 e-mails, ouverts dans Gmail, Outlook et Mail iOS.
- Le PDF imprimable.
- `docs/DESIGN.md` relu par quelqu'un qui n'a pas fait le chantier, avec pour
  seule consigne : écrire une page neuve en le suivant. Si la page produite ne
  ressemble pas au reste, la documentation est incomplète.

---

# PARTIE 6 — LA LISTE DE CONTRÔLE PAR ÉCRAN

À appliquer telle quelle. Un écran qui n'a pas ces 24 cases cochées n'est pas
livré.

**Structure**
1. Layout adapté, jamais un gabarit HTML recréé.
2. Un `<h1>` unique, hiérarchie sans saut de niveau.
3. Lien d'évitement présent.
4. Composants de la bibliothèque, zéro balisage local habillé.

**Espacement**
5. Chaque écart vient du tableau de la Partie 3.
6. Aucune valeur hors échelle.
7. Les écarts sont en `gap` sur le parent, pas en marge sur l'enfant.
8. Marges vers le bas uniquement.

**Mobile**
9. Zéro défilement horizontal à 320px.
10. Vérifié à 320, 360, 390, 768, 1024, 1440.
11. Toute cible ≥ 44px.
12. Champs à 48px / 16px.
13. Une seule colonne sous 480px.
14. Tableau basculé en cartes sous 768px.
15. Action primaire à portée du pouce.

**Couleur et contraste**
16. Aucune couleur en dur.
17. Tout texte ≥ 4,5:1, tout tracé ≥ 3:1, dans les deux thèmes.
18. Lisible au repos : aucune couleur définie seulement dans `:hover`.
19. Aucun lien souligné, focus visible renforcé.

**Contenu**
20. Aucun texte en dur ; `lang/fr` et `lang/en` complets.
21. Vocabulaire compte/profil respecté.
22. Montants en FCFA entiers (« 3 500 FCFA ») ; téléphones via
    `formatted_phone`.

**Robustesse**
23. Rendu correct sans JavaScript.
24. Aucun `href="#"`, aucun bouton sans action, aucune liste non paginée.

---

# PARTIE 7 — LES INTERDITS, RÉCAPITULÉS

- Valeur littérale (couleur, taille, espacement, rayon, ombre, durée) hors
  `_tokens.scss`.
- `!important` ajouté.
- `@media (max-width: …)`.
- `text-decoration: underline` sous quelque forme.
- `color` sur `a:link` ou `a:visited`.
- Couleur définie uniquement dans `:hover`.
- `style="..."` hors e-mails et `printable`.
- Classe Bootstrap brute là où un composant existe.
- URL en dur dans un `href` — toujours `route()` ou `url()`.
- `href="#"` ou bouton sans action.
- Police ou image chargée depuis un CDN.
- JavaScript qui porte une fonctionnalité.
- Framework de rendu client (Vue, React, Inertia, Livewire).
- Confondre COMPTE et PROFIL.
- Le mot « Laravel » dans l'interface.
- Dégradé criard, ombre lourde, icône décorative sans fonction.
- Toucher à la carte PVC, aux maquettes de téléphone, à
  `docs/AUDIT-DESIGN.md`.

---

# PARTIE 8 — COMMENT ON MESURE QUE C'EST FINI

| Mesure | Départ | Cible |
|---|---:|---:|
| Valeurs en dur, toutes catégories | **1 576** | **0** hors liste blanche justifiée |
| Familles CSS concurrentes pour le champ | **14** | **1** |
| Systèmes de boutons | **4** | **1** |
| Racines CSS par famille (carte, tableau, badge, modale, alerte) | 3 / 3 / 2 / 2 / 2 | 1 chacune |
| Couples sous 4,5:1, thème clair | 5 distincts, ~109 occurrences | **0** |
| Couples sous 4,5:1, thème sombre | 8 distincts, ~145 occurrences | **0** |
| `@media (max-width)` | **18**, dont 15 hors feuilles gelées | **3** (les gelées seules) |
| `!important` | **55**, dont 23 dans le socle | **23** — plafond, jamais dépassé |
| Styles en ligne applicatifs | 8 (`admin/statistics`) | **0** |
| Classes d'état vide | **0** | 1, employée partout |
| Verts de marque distincts | **2** (`#0B3B2E`, `#0B5D3B`) | **1** |
| Sources de valeurs | 2 (`_tokens`, `_variables`) | **1** |
| Commandes de vérification | **0** | **3**, dans le CI |
| Vérification navigateur | **aucune** | 22 pages × 2 thèmes × 6 largeurs |
| Tests | 663 | 663 + les tests de garde-fou |
| Taille CSS compilée | à relever au Lot 0 | ≤ +5 % |

---

# PARTIE 9 — CE QU'ON DIT À CHAQUE FIN DE LOT

Trois lignes, pas plus :

1. **Fait** — ce qui a changé, avec le chiffre avant/après de `design:check`.
2. **Bloqué** — ce qui ne peut pas avancer, et ce qu'il faut pour débloquer.
3. **Reste** — le lot suivant, et ce qu'il faut valider avant.

Tout blocage qui dépasse deux heures se signale plutôt que de s'obstiner.
Toute idée hors lots va dans `V2.md`.
