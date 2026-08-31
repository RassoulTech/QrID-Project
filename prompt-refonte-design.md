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
| `_app-shell.scss` | 1 138 | **270** | Non — le plus gros chantier |
| `_admin.scss` | 1 736 | **252** | Non — famille `.adm-*` entière |
| `_components.scss` | 761 | **160** | Partiellement |
| `_auth.scss` | 819 | **135** | Partiellement (`.auth-*`) |
| `_phone.scss` | 530 | 106 | Non — **gelé**, voir 0.5 |
| `_carte-publique.scss` | 460 | 82 | Partiellement |
| `_dashboard.scss` | 487 | 78 | Non |
| `_carte-publique-matiere.scss` | 407 | 60 | Non — **gelé**, voir 0.5 |
| `_theme-dark.scss` | 479 | 29 | Oui (alias) |
| `_contact.scss` | 157 | 28 | Non |
| `_topbar.scss` | 121 | 22 | Non |
| `_card.scss` | 259 | 21 | Partiellement |
| `_whatsapp.scss` | 120 | 19 | Non |
| `design-system.blade.php` | — | 17 | Non — refaite au Lot 6 |
| `components/social-icon.blade.php` | — | 16 | Non |
| `_brand.scss` | 139 | 15 | Oui (médaillon) |
| … 11 autres fichiers | | le reste | |

Ces chiffres sont ceux que rend `php artisan design:check` aujourd'hui, pas
une estimation. Le total est **1 366**, et non les 1 598 de l'audit : le Lot 0
et le socle en ont déjà retiré 232.

Restent également : `/design-system` non refaite, `docs/DESIGN.md` non
réécrit, et **aucune vérification navigateur** — les contrastes sont vérifiés
par calcul sur les valeurs livrées, pas sur le rendu.

**Et deux chantiers neufs, demandés le 31 août** :

- **La coque mobile n'est pas une application.** L'espace client et
  l'administration replient leur colonne dans un offcanvas déclenché par un
  bouton hamburger, à **992 px** — une borne de Bootstrap qui ne figure pas
  dans `$ruptures`. Sur téléphone, la navigation demande donc deux gestes et
  ne dit jamais où l'on est. → **Lot 4 bis**.
- **La carte et la page publique doivent monter d'un cran**, en prenant appui
  sur une référence externe fournie. → **Partie 1 bis**, **Lot 4 quater** et
  **Lot 5 bis**.

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

### (2) ~~`php artisan design:check` n'existe pas~~ — RÉSOLU

**Les trois commandes existent** (`DesignCheck`, `DesignAudit`,
`DesignContraste`) et le cliquet vit dans `config/design.php`, avec sa
répartition des `!important` commentée feuille par feuille. Le Lot 0 est donc
**fait**. C'est ce qui rend tout le reste de ce document vérifiable au lieu
d'être déclaratif.

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

- ~~**La carte PVC**~~ — **DÉGELÉE le 31 août, à la demande explicite du
  client.** `carte-qrid.html` reste la **base de composition** : on en part, on
  ne recommence pas. Ce qui change est borné et listé au **Lot 5 bis**. Tout
  le reste de la composition validée (proportions, hiérarchie, place du QR,
  onde NFC, échelle en `cqw`) est conservé tel quel.
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

# PARTIE 1 BIS — LES DEUX RÉFÉRENCES FOURNIES

Deux images ont été fournies le 31 août. Elles ne sont pas des maquettes à
copier : ce sont des **intentions à transposer**. Ce qui suit dit, pour
chacune, ce qu'on prend, ce qu'on refuse, et pourquoi — parce qu'un « fais
comme ça » non instruit produit toujours un mélange de deux systèmes.

## 1bis.1 La barre de navigation mobile

**Ce qu'on voit** : un dock flottant en bas de l'écran, en forme de pilule,
cinq entrées portant chacune une icône et un libellé, des pastilles de
comptage vertes, et l'entrée active surélevée dans sa propre pilule plus
sombre. La surface est translucide et floute ce qui passe derrière.

| Ce que la référence apporte | Décision |
|---|---|
| Dock flottant en bas, en pilule | **Adopté.** C'est l'apport principal : la navigation devient permanente et visible, au lieu d'être cachée derrière un hamburger |
| Icône **et** libellé sur chaque entrée | **Adopté.** Une icône seule n'est pas comprise ; le libellé est ce qui rend un dock utilisable du premier coup |
| Cinq entrées maximum | **Adopté.** À 320 px, cinq entrées laissent 57 px chacune. Six n'en laissent que 48 : le libellé ne tient plus |
| Pastille de comptage sur l'icône | **Adopté**, avec une règle : une pastille ne s'affiche que si le nombre **appelle une action**. Un compteur décoratif est du bruit |
| Entrée active surélevée dans sa pilule | **Adopté**, à une condition : l'entrée active doit être lisible **au repos** (loi 7), donc distinguée par le fond ET par la couleur du contenu, jamais par la seule surélévation |
| **Surface translucide et floutée** | **REFUSÉ.** Voir ci-dessous — c'est la seule divergence, et elle est technique |

**Pourquoi on refuse le flou.** `backdrop-filter: blur()` oblige le
compositeur à recalculer la zone située derrière l'élément **à chaque image
défilée**. Sur les Android d'entrée de gamme qui dominent le marché
sénégalais, cela produit un saccadement visible précisément pendant le
défilement — c'est-à-dire au moment où l'on regarde le plus la barre du bas.
Le verre dépoli est d'ailleurs déjà dans la liste des interdits.

Le dock obtient exactement la même lecture « flottant au-dessus du contenu »
avec une surface **opaque** `--surelev`, une bordure `1px var(--bordure)` et
`var(--ombre-3)` — pour zéro coût de rendu. C'est la lecture qui compte, pas
le procédé.

## 1bis.2 La carte de visite fournie

**Ce qu'on voit** : un recto vert profond portant le logo et le nom en blanc,
avec un **panneau arrondi en vert plus clair** qui encadre le QR Code ; un
verso blanc dominant, avec un bandeau vert foncé portant une **pastille
avatar + nom + fonction**, un **liseré vert vertical** sur le bord gauche, et
quatre lignes de coordonnées introduites par des **pastilles circulaires
vertes**, séparées par des traits fins. Les coins sont largement arrondis, et
l'épaisseur de la pile est visible.

| Ce que la référence apporte | Décision |
|---|---|
| **Le panneau clair autour du QR** | **ADOPTÉ, et c'est l'apport le plus utile.** Voir 1bis.3 : il règle un défaut d'impression déjà documenté dans le code |
| **Coins arrondis** | **ADOPTÉ**, mais à la valeur normative, pas au jugé. Voir 1bis.4 |
| Pastilles circulaires vertes devant chaque coordonnée, traits fins de séparation | **ADOPTÉ pour la page publique.** C'est le gain le plus net en professionnalisme, et cela remplace des lignes aujourd'hui indifférenciées |
| Bandeau vert foncé portant une pastille avatar + nom + fonction | **ADOPTÉ pour la page publique**, refusé pour le verso imprimé : le verso porte la **plateforme**, pas le porteur — c'est ce qui fait de la carte un support de communication pour QrID |
| Liseré vert vertical sur un bord | **DÉJÀ dans le concept** — « un liseré vert foncé sur un bord comme signature de marque ». La référence confirme le parti pris, elle ne l'introduit pas |
| Blanc dominant au verso, vert en accent | **DÉJÀ le parti pris**, et déjà implémenté : `VarianteCarte::DEFAUT` est la **blanche** |
| Épaisseur de la pile visible | **ADOPTÉ pour l'aperçu** dans l'application : cela dit que la carte est un objet physique qu'on recevra. **Refusé sur la page publique**, qui doit rester la plus légère du produit |
| **Le vert lime `#8CC63E`** | **REFUSÉ comme teinte.** Voir 1bis.5 |

## 1bis.3 Le panneau du QR — ce que la référence corrige vraiment

`App\Enums\VarianteCarte` porte déjà cet avertissement, et il est exact :

> La norme **ISO/IEC 18004** décrit un QR Code **sombre sur fond clair**. La
> variante blanche respecte cette description ; la variante verte l'inverse.
> Les lecteurs modernes gèrent l'inversion, d'autres non, et **leur échec est
> silencieux** — le porteur croit simplement que son code est mauvais.

Le panneau de la référence résout ce problème sans rien retirer : sur la
variante verte, le QR n'est plus posé **sur** le fond vert, il est posé dans
un **panneau clair** qui lui rend un fond blanc. Le code redevient sombre sur
clair, conforme à la norme, **sur les deux variantes**.

C'est donc l'inverse d'une coquetterie : c'est la référence qui répare la
seule faiblesse fonctionnelle connue de la carte verte. **Ce point est
prioritaire dans le Lot 5 bis.**

## 1bis.4 Les coins — la contrainte physique dit l'inverse de ce qu'on croyait

`carte-qrid.html` porte `border-radius: 0` avec le commentaire « angles vifs »,
et `docs/DESIGN.md` justifie `rayon(none)` par « une contrainte physique, pas
un goût : le rendu écran doit correspondre à ce qui sort de l'imprimeur ».

**La contrainte physique dit le contraire.** Le format employé est déjà le bon
— `aspect-ratio: 1.586`, soit 85,6 × 54 mm, la norme **CR80**. Or CR80
spécifie aussi un **rayon de coin de 3,18 mm**. Une carte PVC sort de la
découpe avec des coins arrondis ; des angles vifs sont ce que l'imprimeur ne
livrera pas.

L'écran doit donc s'aligner sur le physique, dans ce sens-ci :

```
3,18 mm / 85,6 mm = 3,71 %  →  border-radius: 3.7cqw
```

`cqw` parce que toute la carte est déjà dimensionnée en unités de conteneur :
le rayon suit l'échelle sans qu'on ait à le recalculer par taille d'affichage.
C'est aussi ce qui réconcilie la référence fournie — arrondie parce que
réelle — avec le concept actuel, sans choisir un camp.

**Cette valeur est la seule exception au tableau des rayons**, et elle
s'inscrit dans `_tokens.scss` comme telle : `$rayon-carte-cr80: 3.7cqw`, avec
le calcul en commentaire.

## 1bis.5 Le vert lime — pourquoi il ne rentre pas

Le vert clair de la référence est un **lime jaune** (autour de `#8CC63E`). Le
vert accent de QrID est `#1E9E7A`, un vert **tirant vers le bleu**. Ce ne sont
pas deux nuances d'une même couleur : ce sont deux familles de teinte.

L'introduire coûterait trois choses :

1. **La marque.** Deux verts de familles différentes sur un même support ne se
   lisent pas comme une palette, mais comme une erreur d'impression.
2. **Les mesures.** Les 18 couples de contraste de `_tokens.scss` ont été
   calculés sur cette palette. Une teinte neuve les rouvre tous.
3. **L'impression.** Un lime saturé dérive fortement en CMJN sur PVC ; le
   vert désaturé actuel est stable.

**On adopte donc le RÔLE, pas la teinte** : là où la référence met du lime, on
met `$vert-accent #1E9E7A` — qui est exactement la couleur « de trait et
d'icône » de la charte, et qui n'a jamais eu de rôle de surface jusqu'ici.
C'est un emploi nouveau et légitime : `$vert-accent` ne porte pas de **texte**
(3,37:1 avec du blanc), mais il porte parfaitement un **panneau** dont le
contenu est un carré blanc.

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

**Treize lots.** Un par commit. Validation entre chaque.

**Le périmètre demandé, et où il est traité** — pour vérifier que rien ne
manque :

| Demandé | Traité en |
|---|---|
| La landing | **Lot 4 ter** |
| L'espace client, avec toutes ses pages | Lot 4 §4.4 (les 12 vues + la coque), §4.5 (le parcours), §4.6 (abonnement), **Lot 4 bis** (le dock) |
| L'espace admin, avec toutes ses pages | Lot 4 §4.8 (les 11 vues, une par une), **Lot 4 bis** (le dock) |
| La carte | **Lot 5 bis** |
| La page publique de la carte | **Lot 4 quater** |
| Les listes déroulantes, partout | **Lot 3 §3.d** |
| Les espacements, le mobile first | Parties 3 et 4, appliquées par tous les lots |

| Lot | Objet | État |
|---|---|---|
| 0 | L'outillage — les trois commandes `design:*` et le cliquet | ✅ **fait**, sauf la vérification navigateur |
| 1 | La source unique de valeurs | à faire |
| 2 | Les 11 feuilles historiques, dans l'ordre du risque | à faire |
| 3 | Les composants Blade — 14 racines de champ → 1 | à faire |
| 4 | Les 158 vues, écran par écran | à faire |
| **4 bis** | **La coque mobile : le dock** (client + admin) | **demandé le 31 août** |
| **4 ter** | **La landing** | **demandé le 31 août** |
| **4 quater** | **La page publique, au niveau de la carte** | **demandé le 31 août** |
| 5 | E-mails, PDF | à faire |
| **5 bis** | **La carte : deux variantes, une géométrie, trois tailles** | **demandé le 31 août** |
| 6 | `/design-system` et `docs/DESIGN.md` | à faire |
| 7 | Le garde-fou permanent (CI) | à faire |
| 8 | La recette finale | à faire |

**L'ordre a une conséquence à accepter.** Les lots 4 bis, 4 ter, 4 quater et
5 bis sont les plus visibles, et la tentation sera de commencer par eux. Il
faut y résister : ils touchent `_app-shell.scss` (270 valeurs en dur),
`_admin.scss` (252), `_components.scss` (160), `_phone.scss` (106) et
`_carte-publique.scss` (82) — c'est-à-dire les cinq feuilles les plus chargées
du projet. Les écrire **avant** le Lot 2 revient à écrire du neuf dans l'ancien
système, et à devoir le retraduire ensuite.

Et le Lot 3 vient avant eux pour la même raison : le dock, la landing, la page
publique et l'écran de choix de variante emploient tous les champs, les
`select`, les tableaux et les états vides. Les écrire avant que ces composants
soient unifiés, c'est produire une cinquième famille de champs pendant qu'on
réduit les quatre premières.

Une seule exception, et elle est justifiée : le **panneau QR** du Lot 5 bis
(point 2) corrige un défaut fonctionnel — un QR qui échoue silencieusement sur
certains lecteurs. Il peut partir en avance, seul, dans son propre commit.

---

## LOT 0 — L'OUTILLAGE — ✅ **FAIT**

**Pourquoi d'abord** : on ne peut pas piloter la réduction des valeurs en dur
sans un compteur. Et une règle sans garde-fou se dégrade dès le premier
correctif pressé.

**État au 31 août** — les trois commandes existent, le cliquet est posé dans
`config/design.php`, et `design:check` rend :

| Compteur | Valeur | Plafond |
|---|---:|---:|
| valeurs en dur | 1 366 | 1 366 |
| `!important` (occurrences, pas lignes) | 44 | 44 |
| `@media (max-width)` | 18 | 18 |
| styles en ligne (hors e-mails et PDF) | 98 | 98 |
| liens morts `href="#"` | 1 | 1 |
| soulignements | 9 | 9 |
| budget CSS | 364 551 o | +5 % |

Tout est **au plafond** : rien n'a régressé, et rien n'a encore baissé. Ce
sont les chiffres de départ des lots suivants. Chaque lot les baisse et
inscrit la nouvelle valeur dans son commit.

Reste ouvert dans ce lot : **la vérification navigateur** (0.d), qui n'a
jamais tourné. Elle conditionne les Lots 2 et suivants — sans elle on livre
des contrastes calculés, pas constatés.

<details><summary>Ce qui a été construit — pour mémoire</summary>

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

- [x] Les trois commandes existent (`DesignCheck`, `DesignAudit`,
      `DesignContraste`).
- [x] `php artisan design:check` sort le chiffre de départ, inscrit dans
      `config/design.php`.
- [x] La taille CSS de référence est notée : 364 551 octets, tolérance 5 %.
- [ ] **Playwright se connecte et le script de contraste tourne sur les 22
      pages** — seul point encore ouvert du lot.
- [ ] Les trois commandes sont documentées dans `docs/DESIGN.md` (Lot 6).

</details>

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

### 3.d — LES LISTES DÉROULANTES, PARTOUT

Demandé explicitement, et à raison : le `select` est le champ le plus maltraité
du produit. Il en existe **quatre racines** (`.f__select`, `.form-select`,
`.adm-select`, `select` nu), et le socle les a déjà ramenées sur une apparence
— mais le **composant Blade**, lui, n'a pas suivi.

#### Ce qui est déjà juste et qu'on garde

Le socle a réglé les trois pièges réels, et il ne faut pas les défaire :

1. **La flèche native est remplacée par un chevron déclaré par thème.** Sur
   Windows, la flèche du système reste noire sur fond sombre. Il faut **deux**
   images encodées, `--chevron` clair et `--chevron` sombre : une image encodée
   ne peut pas lire une propriété CSS.
2. **`option` reçoit un `background` et un `color` explicites.** Sans ces deux
   lignes, une liste ouverte en thème sombre s'affiche **clair sur clair** sous
   Windows. Les options sont dessinées par le système : on ne peut leur imposer
   que ces deux propriétés, et il faut le faire.
3. **Select natif, jamais redessiné.** Sur téléphone, le sélecteur natif est un
   panneau plein écran ou une roue — meilleur que toute liste réécrite, et
   utilisable au clavier comme au lecteur d'écran. Aucune liste déroulante
   maison, nulle part.

#### Les six défauts de `x-select`, mesurés dans le fichier

| # | Défaut | Ligne | Conséquence |
|---:|---|---|---|
| 1 | `<div class="mb-3">` | conteneur | **Le rythme des formulaires du produit entier est fixé par un utilitaire Bootstrap**, pas par l'échelle. `mb-3` vaut 16 px là où la Partie 3 impose `esp(5)` = 20 px entre deux groupes |
| 2 | `(facultatif)` écrit en clair | libellé | **Texte en dur dans un composant** — loi 10. La version anglaise affiche « (facultatif) » |
| 3 | `@if ($required)` + `&nbsp;*` à la main | libellé | L'astérisque est **passé par la vue**, pas déduit de la règle de validation. Un champ obligatoire côté serveur peut donc apparaître facultatif à l'écran, sans erreur ni test rouge |
| 4 | `form-label`, `form-text`, `invalid-feedback d-block` | partout | Classes Bootstrap brutes — loi 6. Le `d-block` est un contournement : il force l'affichage d'un `invalid-feedback` que Bootstrap masque par défaut |
| 5 | `padding-right`, `background-position: right …` | `%select` | Propriétés **physiques** là où le reste du socle emploie des propriétés logiques (`padding-inline-end`, `inset-inline-end`) |
| 6 | Aucun style pour `option:disabled` ni pour `optgroup` | — | Le sélecteur d'indicatif ordonne l'Afrique de l'Ouest en tête : c'est exactement un cas d'`optgroup`, et il n'est pas habillé |

Le défaut 1 est le plus lourd : il n'est pas propre au `select`. **Tous les
composants de champ portent `mb-3`.** C'est là que se joue l'espacement des
formulaires de tout le produit, et il est aujourd'hui hors échelle. Le corriger
change le rythme de chaque écran de saisie — c'est voulu, et c'est à faire une
seule fois, ici.

#### L'anatomie cible

```
%select   @extend %champ                    → 48px de haut, 16px de police
          appearance: none
          padding-inline-end: esp(9)         → 48px, la place du chevron
          background-image: var(--chevron)
          background-position: inset-inline-end esp(4) center
          background-size: 14px
          cursor: pointer

select option           background: var(--carte)  ; color: var(--texte)
select option:disabled  color: var(--texte-3)
select optgroup         font-weight: 600 ; color: var(--texte-2)
                        background: var(--surelev)
```

#### Les huit règles du `select` dans ce produit

1. **Une seule racine.** `%select` est étendu par les quatre sélecteurs
   historiques, et **aucun nouveau** ne s'ajoute.
2. **48 px de haut, 16 px de police**, comme tout champ. Un `select` plus bas
   qu'un `input` voisin est le défaut le plus visible d'un formulaire.
3. **Le chevron est décliné par thème.** Deux images encodées, jamais une.
4. **`option` porte toujours `background` + `color`.** Vérifié sous Windows,
   thème sombre, liste **ouverte** — c'est le seul moyen de voir le défaut.
5. **Propriétés logiques** : `padding-inline-end`, `inset-inline-end`.
6. **Un marque-place n'est pas une valeur.** `<option value="">` en tête, et la
   validation serveur refuse la chaîne vide sur un champ obligatoire.
7. **`optgroup` habillé**, et employé là où la liste a une hiérarchie réelle —
   le sélecteur d'indicatif en premier.
8. **Aucune liste déroulante réécrite en JavaScript**, y compris pour la
   recherche. Si une liste devient trop longue pour un `select`, la réponse est
   un champ de recherche serveur, pas un composant maison.

#### Le cas particulier de la sélection multiple en administration

L'écran « Cartes à produire » sélectionne plusieurs commandes pour former un
lot. Ce n'est **pas** un `select multiple` : sur téléphone, un `select
multiple` natif est inutilisable. C'est une **liste de cases à cocher**, une
par ligne, cible ≥ 44 px, avec une case « tout sélectionner » en tête et un
compteur de sélection dans la barre d'actions. Le tout dans un formulaire POST,
sans JavaScript obligatoire.

### Acceptation du Lot 3

- [ ] **`mb-3` a disparu de tous les composants de champ**, remplacé par
      l'espacement de la Partie 3. Le rythme est vérifié sur les trois
      formulaires les plus longs du produit.
- [ ] Aucun texte en dur dans un composant : « (facultatif) » vient de
      `lang/`.
- [ ] L'astérisque est **déduit de la règle de validation**, et un test le
      prouve sur un champ requis côté serveur mais non annoté dans la vue.
- [ ] Un `select` ouvert en **thème sombre sous Windows** affiche des options
      lisibles — vérifié, pas supposé.
- [ ] `select`, `input`, `textarea` ont exactement la même hauteur et le même
      rayon côte à côte, sur `/design-system`.
- [ ] `optgroup` et `option:disabled` sont habillés et lisibles.
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

#### La coque de l'espace client — ce qui vaut pour les douze écrans

L'espace client **partage la coque et les classes de l'administration**
(`adm-*`), et c'est une bonne décision qu'il faut garder : c'étaient deux jeux
de composants identiques — colonne, barre, recherche, avatar, contenu — et le
moindre défaut devait être corrigé deux fois. Un fond de colonne effacé par
Bootstrap l'a été exactement deux fois avant qu'on ne s'en aperçoive.

Conséquence directe : **tout ce qui suit s'écrit une fois et sert les deux
espaces.**

| Élément de coque | Spécification mobile (320) | 768+ |
|---|---|---|
| Conteneur de contenu | `padding-inline: esp(4)`, `padding-block: esp(7)` | `esp(6)` / `esp(9)` |
| Dégagement du bas | `calc(esp(11) + env(safe-area-inset-bottom))` — le dock | `esp(9)`, plus de dock |
| Barre supérieure | 56 px de haut, `--carte`, bordure basse `--bordure` | 64 px |
| Colonne latérale | Absente — remplacée par le dock | 264 px fixe, fond `--surelev` |
| Entrée de menu | 48 px de haut, `padding-inline: esp(3)`, `rayon(sm)` | idem |
| Titre de section de menu | `.t-over`, `var(--texte-3)`, `esp(2)` au-dessus | idem |
| En-tête de page | `x-page-header` : sur-titre, `h1`, chapeau, actions | idem |
| Écart en-tête → contenu | `esp(6)` | `esp(7)` |
| Grille de blocs | 1 colonne | 2 colonnes à 640, 3 à 1024 |
| Écart entre blocs | `esp(4)` | `esp(6)` |

#### Les cinq points de composition à reprendre sur tous les écrans clients

1. **L'en-tête de page est un composant, pas un `<h1>` posé à la main.**
   Aujourd'hui chaque vue compose son titre et ses actions à sa façon, avec un
   espacement différent. `x-page-header` porte les quatre éléments et le rythme.
2. **Le bandeau d'abonnement ne recouvre jamais une action.** Il se place sous
   l'en-tête, dans le flux, jamais en flottant. Un bandeau flottant sur un
   écran de 640 px de haut mange le quart utile.
3. **Chaque écran a un état vide explicite**, par `x-empty-state`. Il n'existe
   aujourd'hui **aucune classe dédiée** : chaque page réinvente son vide. Les
   états à écrire : aucune statistique encore, aucune notification, aucun
   résultat de recherche, aucune commande de carte, aucun paiement.
4. **Les graphiques ont une alternative textuelle.** Un tracé relève du seuil
   3:1, mais **toute étiquette** relève de 4,5:1 — et un graphique sans tableau
   de secours n'est lisible par personne au lecteur d'écran. Le tableau peut
   être replié dans un `<details>`, il ne peut pas être absent.
5. **`account/edit` est le piège des sacs d'erreurs.** Trois formulaires sur
   une page : chacun a son `errorBag`, et une erreur de mot de passe ne doit
   jamais s'afficher au-dessus du formulaire de suppression. À vérifier en
   soumettant chacun des trois en erreur.

#### Le bloc « Ma carte » du tableau de bord

C'est le seul endroit de l'espace client où la carte apparaît, et il devient
important maintenant qu'elle est le sujet de la landing.

- Carte à `--carte-bloc` (`min(320px, 100%)`), recto par défaut.
- Sous la carte : le statut de la commande physique en clair — `pending`,
  `in_batch`, `produced`, `shipped`, `delivered` — par `x-badge`, jamais par
  une couleur seule.
- L'adresse reste **corrigeable tant que le statut est `pending`**, et le dit.
- Le délai annoncé vient de `config('cartes.delai_jours')`, jamais d'une
  valeur écrite dans la vue.

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

> **La refonte de composition de `public/profile` est traitée à part, au
> Lot 4 quater.** Ce qui suit reste la recette de conformité applicable aux cinq
> vues ; `carte-inactive` et `demo` n'ont que celle-ci.

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

#### Le tableau, et sa bascule en cartes — la pièce maîtresse de cette famille

Neuf des onze écrans d'administration sont des listes. **C'est donc un seul
composant qui décide de la qualité mobile de tout l'espace admin**, et il n'en
existe aujourd'hui aucun : trois familles concurrentes (`.table`, `.liste`,
`.adm-table`) le décrivent.

Anatomie unique, `x-table` :

| | Sous 768 px — une carte par ligne | 768+ — un vrai tableau |
|---|---|---|
| Structure | `<table>` sémantique conservée, réagencée en CSS | `<table>` |
| Chaque cellule | Porte son libellé, repris du `<th>` | Le `<th>` est en tête de colonne |
| Séparation | Une carte `--carte`, `rayon(md)`, `esp(4)` de padding, `esp(4)` entre deux | Une bordure `1px var(--bordure)` entre les lignes |
| Ligne cliquable | Toute la carte est le lien | Toute la ligne est le lien |
| Actions de ligne | En bas de la carte, boutons `sm`, ≥ 44 px | Dernière colonne, alignée à la fin |
| En-tête de colonne | Masqué visuellement, conservé pour le lecteur d'écran | `.t-over`, `var(--texte-3)` |
| Tri | Liens serveur avec `aria-sort` | idem |

**La règle qui décide** : le `<table>` reste un `<table>` dans le HTML. On ne
génère pas deux balisages selon la largeur — ce serait deux vérités à
maintenir, et le lecteur d'écran perdrait la structure. La bascule est
**entièrement en CSS**.

Un tableau qui défile latéralement sur téléphone n'est pas lu. Neuf écrans en
dépendent.

#### Les onze écrans, un par un

| Écran | Nature | Ce qui s'y joue |
|---|---|---|
| `overview` | Tableau de bord | Grille de statistiques + alertes. **C'est l'écran d'ouverture** : les nombres qui appellent une action (cartes à produire, paiements en attente) y sont en tête, pas noyés |
| `statistics` | Graphiques | **8 attributs `style=` à retirer** — les seuls du produit hors e-mails et PDF. Étiquettes à 4,5:1, tableau de secours |
| `clients/index` | Liste | `x-table`. Recherche + filtres au-dessus, en formulaire GET |
| `clients/show` | Fiche | Le seul écran de détail. En-tête d'identité, blocs d'information, journal des actions, actions d'administration groupées et **confirmées** |
| `profiles/index` | Liste | `x-table`. Aperçu de carte en vignette (`--carte-vignette`) |
| `payments/index` | Liste | `x-table`. **Le pire foyer de badges** : 42 occurrences de `#DC3545` sur `#F2F3F1` à 4,07:1, et 23 de « En attente » à 1,46:1 |
| `subscriptions/index` | Liste | `x-table`. Dates d'échéance lisibles, jamais un simple code couleur |
| `cartes/index` | Liste + action de masse | Voir ci-dessous |
| `templates/index` | Liste | `x-table` + aperçu |
| `settings/index` | Formulaire | Le plus gros formulaire du produit. Sections, `x-page-header`, rythme de la Partie 3 |
| `audit/index` | Journal | Liste dense. `typo(body-sm)` admis ici, jamais en dessous. Filtres par date et par acteur |
| `system-health` | État | Voyants d'état : **jamais une couleur seule**. Un voyant vert et un voyant rouge doivent se distinguer en niveaux de gris |

#### `cartes/index` — l'écran opérationnel, et le plus exigeant

C'est l'écran qui fait sortir les cartes de l'imprimerie. Il porte une action
de masse, ce qu'aucun autre écran ne fait.

- **Pas de `select multiple`.** Une liste de cases à cocher, une par ligne,
  cible ≥ 44 px, plus une case « tout sélectionner » en tête. Un `select
  multiple` natif est inutilisable au doigt.
- **Barre d'actions collante** en bas, portant le compteur de sélection
  (« 12 commandes sélectionnées ») et les actions de lot. Sous 768 px elle se
  cale **au-dessus du dock**.
- **Tout passe par un formulaire POST avec `@csrf`.** Créer un lot, changer un
  statut, exporter : aucune action de masse en `fetch`.
- Les cinq statuts — `pending`, `in_batch`, `produced`, `shipped`,
  `delivered`, plus `cancelled` — par `x-badge`, chacun avec sa teinte d'état
  mesurée. **Six badges sur un même écran : c'est le test le plus dur de la
  palette d'états.**
- Le seuil de lot vient de `config('cartes.seuil_lot')`, jamais d'une valeur
  écrite dans la vue.

#### Ce qui distingue l'administration, et qui reste

L'en-tête sombre est un parti pris **volontaire** : il dit à l'exploitant qu'il
n'est plus dans l'espace client. Il reste sombre **dans les deux thèmes**, et
ses valeurs deviennent des tokens.

Attention au piège que cela crée : un en-tête sombre en thème clair est une
**surface inversée**. Tout ce qui s'y pose — texte, icônes, bascules, avatar,
badge de notification — doit être mesuré contre `$vert-fonce`, pas contre
`--carte`. C'est exactement le genre d'endroit où un `var(--texte)` hérité
donne du sombre sur sombre.

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

## LOT 4 BIS — LA COQUE MOBILE : LE DOCK

**Le problème, en une phrase** : sur téléphone, l'espace client et
l'administration cachent leur navigation derrière un bouton hamburger. Il faut
deux gestes pour changer d'écran, et à aucun moment la barre ne dit où l'on
se trouve. Ce n'est pas une application, c'est un site replié.

### 4bis.1 La borne se déplace de 992 à 768

`layouts/app.blade.php` emploie `offcanvas-lg`, qui replie la colonne sous
**992 px** — une borne de Bootstrap absente de `$ruptures`. C'est exactement
le symptôme décrit à la loi 1 : on suit un autre système que le nôtre.

Nouvelle règle, tirée de l'échelle :

| Largeur | Navigation |
|---|---|
| `< lg` (768 px) | **Dock en bas**, fixe et permanent. Pas de hamburger, pas d'offcanvas ouvert par défaut |
| `≥ lg` (768 px) | **Colonne latérale fixe**, telle qu'aujourd'hui. Le dock disparaît |

Un seul `@include ecran(lg)` gouverne la bascule, dans les deux sens. Aucune
requête `max-width`.

### 4bis.2 L'anatomie du dock — valeurs exactes

Un composant unique, `<x-dock :items="…" />`, employé par
`layouts/app.blade.php` **et** `layouts/admin.blade.php`.

```
.dock                         position: fixed
                              inset-inline: esp(4)          → 16px de chaque bord
                              bottom: calc(#{esp(3)} + env(safe-area-inset-bottom))
                              z-index: au-dessus du contenu, sous les modales
                              display: flex ; justify-content: space-between
                              background: var(--surelev)     ← OPAQUE, pas de flou
                              border: 1px solid var(--bordure)
                              border-radius: rayon(full)
                              box-shadow: var(--ombre-3)
                              padding: esp(2)                → 8px
                              @include ecran(lg) { display: none }

.dock__item                   flex: 1 1 0                    → cinq parts égales
                              min-height: 56px               ← > $tactile-min
                              display: flex ; flex-direction: column
                              align-items: center ; justify-content: center
                              gap: esp(1)                    → 4px
                              border-radius: rayon(full)
                              padding-inline: esp(1)
                              color: var(--texte-2)          ← 7,42:1, lisible AU REPOS
                              transition: background-color $d-rapide $courbe

.dock__item.is-active         background: var(--marque-doux)
                              color: var(--marque)
                              (+ aria-current="page")

.dock__icone                  width / height: 24px
                              position: relative              ← ancre la pastille

.dock__libelle                font-size: typo(overline)      → 11px
                              font-weight: typo(overline, graisse)
                              letter-spacing: typo(overline, espacement)
                              white-space: nowrap
                              (PAS de text-transform : les libellés sont déjà courts)

.dock__pastille               position: absolute
                              top: -6px ; inset-inline-end: -10px
                              min-width: 20px ; height: 20px
                              padding-inline: esp(1)
                              border-radius: rayon(full)
                              background: var(--marque)
                              color: var(--texte-inv-marque)   ← couple déjà mesuré, 12,51:1
                              font-size: typo(overline)
                              display: grid ; place-items: center
```

**Le calcul de largeur, qui décide de tout** : à 320 px, moins deux fois
`esp(4)`, il reste 288 px pour cinq entrées, soit **57,6 px chacune**. C'est
ce qui impose les deux contraintes suivantes, et elles ne sont pas
négociables :

- **Cinq entrées maximum.** À six, chaque entrée tombe à 48 px et le libellé
  ne tient plus.
- **Libellés courts, déclarés séparément.** Le libellé du dock n'est **pas**
  celui de la colonne : « Tableau de bord » ne tient pas dans 57 px, « Accueil »
  oui. Ils vivent sous une clé distincte, `navigation.client.court.*` et
  `navigation.admin.court.*`, dans `lang/fr` et `lang/en` — et l'anglais doit
  tenir aussi.

### 4bis.3 Les entrées, arrêtées

**Espace client** — cinq entrées, tirées des sept de la colonne :

| Ordre | Libellé court | Route | Actif quand | Pastille |
|---:|---|---|---|---|
| 1 | Accueil | `dashboard` | `dashboard` | — |
| 2 | Profil | `profil.index` ou `profile.create.step1` | `profil.index`, `profile.create.*`, `profile.edit`, `profile.preview` | — |
| 3 | Ma carte | `carte.qr` | `carte.qr` | — |
| 4 | Stats | `statistiques` | `statistiques` | — |
| 5 | Plus | `menu` | `menu` | notifications non lues |

« Mon abonnement », l'aide WhatsApp, la déconnexion, la langue et le thème
passent dans **Plus**. Le bandeau d'abonnement de la coque reste où il est :
c'est lui qui alerte, pas le menu.

L'entrée « Profil » garde la règle existante : **active pendant tout le
parcours de création**, sinon le menu perd son repère au milieu des trois
étapes.

**Administration** — cinq entrées, tirées des onze :

| Ordre | Libellé court | Route | Pastille |
|---:|---|---|---|
| 1 | Accueil | `admin.overview` | — |
| 2 | Clients | `admin.clients.index` | — |
| 3 | Paiements | `admin.payments.index` | paiements en attente de réconciliation |
| 4 | Cartes | `admin.cards.index` | commandes au statut `pending` |
| 5 | Plus | `menu` (variante admin) | — |

Les six autres entrées — statistiques, profils, abonnements, modèles,
paramètres, journal, état système — vivent dans **Plus**.

**La règle des pastilles** : une pastille ne s'affiche que si le nombre
**appelle une action**. « 12 clients » n'est pas une pastille, c'est une
statistique. « 12 cartes à produire » en est une. Au-delà de 99, on écrit
`99+`. À zéro, la pastille n'existe pas dans le DOM — pas un `0` grisé.

Les comptes se calculent **une fois par requête**, dans un `View::composer`
dédié à la coque, jamais dans la vue. Une pastille qui déclenche une requête
par affichage est une pastille qu'on retirera dans six mois pour cause de
lenteur.

### 4bis.4 « Plus » — la règle qui rend le dock honnête

**`Plus` est une vraie route, pas un déclencheur.**

`GET /menu` → `menu/index.blade.php`, qui rend le menu complet en pleine page,
à partir du **même partiel** que la colonne (`sidebar-links` ou `admin-links`)
— aucun second jeu d'entrées à maintenir.

Un module JavaScript intercepte ensuite le clic et ouvre l'offcanvas existant
à la place. C'est l'application exacte de la loi « le JavaScript améliore, il
ne porte jamais » :

| | Avec JavaScript | Sans JavaScript |
|---|---|---|
| Clic sur « Plus » | L'offcanvas glisse depuis le bas | La page `/menu` s'ouvre |
| Fermeture | Geste ou bouton | Retour navigateur, ou lien « Retour » en tête de page |

Un dock dont la cinquième entrée ne fonctionne pas sans script serait un dock
cassé pour un visiteur sur une connexion coupée en cours de chargement — ce
qui, en 3G, arrive.

### 4bis.5 Ce que le dock oblige à ajuster ailleurs

1. **Le contenu se dégage.** Sous `lg`, le conteneur principal reçoit
   `padding-block-end: calc(#{esp(11)} + env(safe-area-inset-bottom))` — 80 px
   plus la zone sûre. Sans quoi le dock recouvre la dernière action de chaque
   page, et ce sera toujours le bouton « Enregistrer ».
2. **Les barres d'actions collantes montent.** Toute barre `position: sticky`
   en bas d'écran se cale **au-dessus** du dock, pas en dessous.
3. **Le hamburger disparaît sous `lg`.** Deux navigations concurrentes sur le
   même écran, c'est une de trop. Il reste au-dessus de `lg` si la colonne y
   est repliable ; sinon il disparaît.
4. **Le dock ne s'affiche pas partout.** Il est **absent** de :
   `layouts/auth` (on ne navigue pas pendant une connexion),
   `layouts/public-profile` (la page la plus légère du produit, on n'y ajoute
   rien), `errors/layout`, et **le parcours de création** (`step-shell`) —
   pendant les trois étapes, la seule navigation légitime est « Continuer » et
   « Retour ». Un dock y offrirait une sortie qui perd le travail en cours.
5. **L'entrée active est décidée par le serveur** (`request()->routeIs()`),
   jamais par JavaScript. C'est déjà le motif des deux menus existants ; le
   dock le reprend tel quel.

### 4bis.6 Le mouvement — trois règles, pas une de plus

- Le dock **ne se cache pas au défilement**. Une barre qui disparaît et
  revient oblige à la chercher ; le gain de place ne vaut pas la perte de
  repère.
- Le changement d'entrée active est une transition de **fond** en `$d-rapide`
  (150 ms), courbe `$courbe`. Rien ne se déplace : `transform` et `opacity`
  seulement, jamais une propriété qui recalcule la mise en page.
- `prefers-reduced-motion` supprime la transition, pas l'état.

### Acceptation du Lot 4 bis

- [ ] Le dock s'affiche sous 768 px, la colonne au-dessus, sans aucun
      `max-width`.
- [ ] Cinq entrées, libellés courts, **aucun texte tronqué à 320 px** — en
      français **et** en anglais.
- [ ] Chaque entrée ≥ 56 px de haut ; la zone tactile couvre toute la part.
- [ ] L'entrée active est lisible **au repos**, distinguée par le fond ET la
      couleur, et porte `aria-current="page"`.
- [ ] Les pastilles n'apparaissent qu'à partir de 1, affichent `99+` au-delà
      de 99, et ne coûtent **aucune requête supplémentaire** (vérifié au
      compteur de requêtes).
- [ ] `env(safe-area-inset-bottom)` respecté : testé sur iPhone avec barre
      d'accueil.
- [ ] Le contenu ne passe jamais sous le dock — vérifié sur l'écran le plus
      long de chaque famille.
- [ ] **`/menu` fonctionne sans JavaScript**, et l'offcanvas s'y substitue
      quand le script est là.
- [ ] Le dock est absent des cinq contextes listés en 4bis.5.
- [ ] Un seul composant `x-dock`, employé par les deux layouts.
- [ ] Contraste vérifié : entrée au repos, entrée active, pastille, dans les
      deux thèmes.

---

## LOT 4 TER — LA LANDING

### 4ter.1 Le constat qui commande tout le reste

**La landing vend une carte qu'elle ne montre jamais.**

Le message commercial est « 3 500 FCFA les 3 mois — votre carte PVC offerte à
l'activation ». Or les huit sections de l'accueil montrent, en tout : deux
maquettes de **téléphone** (`x-phone` dans `hero` et `showcase`), trois cartes
flottantes décoratives, un bandeau de métiers, trois chiffres, trois étapes,
trois formules, un formulaire. **La carte physique n'apparaît pas une seule
fois.**

C'est le défaut le plus coûteux de la page : on demande à un client de payer
pour un objet qu'il n'a pas vu. Et c'est exactement ce que la référence
fournie corrige — une carte de visite montrée recto-verso, en volume, comme un
objet qu'on recevra.

**Décision** : la carte devient le sujet visuel de la landing, à égalité avec
le téléphone. Le téléphone montre **ce que le visiteur verra** après un scan ;
la carte montre **ce que le client recevra**. Les deux ensemble racontent le
produit ; l'un seul le raconte à moitié.

### 4ter.2 L'ordre des sections — conservé, avec un ajout

L'ordre actuel est réfléchi et se garde, y compris le choix documenté de placer
le contact **avant** l'appel final : « il s'adresse à qui hésite encore, et
doit rencontrer sa question ouverte avant qu'on lui redemande de créer un
compte. »

| # | Section | Rôle | Changement |
|---:|---|---|---|
| 1 | `hero` | La promesse + l'action | **La carte entre dans la scène** |
| 2 | `trades` | À qui c'est destiné | Accessibilité du défilement — voir 4ter.6 |
| 3 | `figures` | Trois chiffres de promesse | Rythme et échelle |
| 4 | `steps` | Comment ça marche | Rythme et échelle |
| 5 | `showcase` | La démonstration | **Retirer « +500 »** — voir 4ter.5 |
| — | **`carte` (NOUVELLE)** | **L'objet qu'on reçoit** | **À créer** — voir 4ter.4 |
| 6 | `plans` | Le prix | Message unifié, mise en avant |
| 7 | `contact` | Lever le dernier doute | Champs et select au socle |
| 8 | `final-cta` | Redemander l'action | Rythme |

La nouvelle section s'insère **entre `showcase` et `plans`** : on montre
l'objet juste avant d'annoncer son prix. Montrer le prix d'une chose qu'on n'a
pas vue est la façon la plus sûre de le faire paraître élevé.

### 4ter.3 Les quatre systèmes de titres — à réduire à un

La page porte aujourd'hui **quatre familles de titres** pour la même fonction :

| Classe | Section |
|---|---|
| `.hero__title` | hero |
| `.section-title` / `.section-title--underlined` | steps, plans |
| `.showcase__title` | showcase |
| `.final__title` | final-cta |

C'est le même défaut que les quatre systèmes de boutons, sur une seule page.
Tous passent sur l'échelle typographique : `.t-display` pour le hero, `h2` pour
les autres, et le trait vert de `--underlined` devient un modificateur unique
réutilisable. Le sous-titre passe sur `.t-lead`.

Même traitement pour les conteneurs : `.section`, `.section--tint`, `.hero`,
`.showcase`, `.final`, `.figures`, `.trades` portent chacun leur propre padding
vertical. Ils passent tous par **`x-section`**, qui porte le padding du tableau
de la Partie 3 (`esp(10)` mobile → `esp(12)` à 768 → `esp(13)` à 1024) et
accepte une variante de fond : `nu`, `teinte` (`--surelev`), `marque`
(`$vert-fonce`), `doux` (`--marque-doux`).

**Et `.wrap` est hors échelle** : `_base.scss` le déclare en
`max-width:1180px; padding-inline:20px`. `20px` n'est pas un cran de
`$espaces` (les crans voisins sont 16 et 24), et `1180px` n'est pas dans
`$ruptures`. Il passe à `padding-inline: esp(4)` puis `esp(6)` à 768 et
`esp(7)` à 1024, largeur plafonnée à la rupture `max` (1440) moins les
gouttières — une seule valeur, déclarée une fois, employée par toute la
vitrine.

### 4ter.4 La nouvelle section « la carte »

C'est ici que la référence fournie est transposée.

| Bloc | Spécification mobile (320) | 768+ |
|---|---|---|
| Conteneur | `x-section` variante `teinte` | idem |
| Sur-titre | `.t-over`, `var(--texte-3)` | idem |
| Titre | `h2` | idem |
| Chapeau | `.t-lead`, largeur `68ch` | idem |
| **Les deux faces** | Empilées, une par ligne, `gap: esp(6)` | Côte à côte, `gap: esp(7)` |
| Largeur de carte | `--carte-apercu` — `min(420px, 92vw)` | idem |
| **Épaisseur de pile** | Visible : deux à trois copies décalées de `2px` derrière la face, en `var(--ombre-3)` | idem |
| Légende sous chaque face | `.t-caption`, `var(--texte-3)` — « Recto » / « Verso » | idem |
| Mention | « Offerte à la première activation payée. » `.t-caption` | idem |

**Trois règles pour cette section, et elles comptent** :

1. **Les deux variantes sont montrées, pas une.** La blanche d'abord — c'est
   la variante par défaut, et celle dont le QR est conforme à la norme. Un
   sélecteur à deux boutons radio permet de basculer l'aperçu ; **sans
   JavaScript, les deux variantes s'affichent l'une après l'autre.**
2. **L'épaisseur n'apparaît que sur la landing et dans l'application.** Jamais
   sur la page publique, qui reste plate et légère.
3. **Aucune photo de mise en scène.** Pas d'image de carte posée sur un
   bureau : ce serait une image externe, un poids, et un rendu qui ne suit pas
   les tokens. La carte est du HTML, dimensionnée en `cqw`, comme partout
   ailleurs — c'est ce qui garantit qu'elle est **exacte** et non approximative.

### 4ter.5 « +500 professionnels » — à retirer

`showcase.blade.php` affiche une pile d'avatars terminée par **`+500`**,
libellée « professionnels ».

Ce nombre n'est pas mesuré. Et le projet s'est déjà donné la règle, dans
`config/landing.php`, à deux endroits :

> Trois chiffres clés — **VALEURS DE CONFIGURATION, PAS DES STATISTIQUES.** Ils
> décrivent la promesse produit : **rien n'est mesuré ni inventé.**

> Preuve sociale — **désactivée tant qu'il n'y a pas de vrais clients.**

Les trois chiffres de `figures` respectent la règle : « 3 minutes », « 1 lien »,
« 15 jours » décrivent le produit, pas une audience. `+500` la viole : il
annonce une adoption qui n'existe pas, sur la page la plus vue du site, alors
que le bloc témoignage est justement désactivé pour cette raison.

**Décision** : les avatars et le `+500` sortent. Ce qui les remplace dans la
composition est le badge « Scanner & Voir », qui décrit une action réelle. Le
jour où il y a des clients, la preuve sociale revient — par le bloc témoignage
prévu pour ça, avec de vrais noms.

Ce n'est pas un scrupule décoratif : une promesse chiffrée fausse sur une page
de vente est ce qui se retourne le plus vite contre un produit local, où les
clients se connaissent.

### 4ter.6 Le bandeau des métiers — deux points à traiter

Le défilement horizontal en boucle est en CSS pur, indépendant du défilement de
la page, avec la liste dupliquée pour rendre la boucle continue. La mécanique
est bonne. Deux choses manquent :

1. **WCAG 2.2.2 — Pause, Stop, Hide.** Toute animation qui dure plus de cinq
   secondes et démarre seule doit pouvoir être arrêtée. Une boucle infinie n'a
   pas de fin : il faut donc `prefers-reduced-motion: reduce` → l'animation
   s'arrête et le bandeau devient une liste statique, **et** le contenu reste
   entièrement lisible dans cet état. Ce n'est pas une option d'accessibilité
   parmi d'autres : un défilement horizontal permanent est un déclencheur
   vestibulaire connu.
2. **Le contraste, déjà corrigé, ne doit pas revenir.** L'audit a mesuré le
   bandeau à `opacity: .45`, soit **1,86:1**. L'opacité a été retirée et
   l'estompage vient désormais du masque latéral (5,03:1). **Ne jamais
   réintroduire d'opacité sur ce bandeau** — c'est la correction la plus facile
   à défaire par mégarde en « adoucissant » un bord.

### 4ter.7 Le hero — la scène, recomposée

Aujourd'hui : texte à gauche, téléphone à droite, trois cartes flottantes
décoratives (`float--qr`, `float--views`, `float--saved`).

| Élément | Décision |
|---|---|
| Titre | `.t-display` — 34 px mobile, 52 px bureau. Le balisage reste **dans la traduction** : l'anglais ne place pas l'adjectif au même endroit |
| Chapeau | `.t-lead`, `68ch` |
| Deux actions | « Créer un compte » (primaire) et « Voir un exemple » (contour). **Empilées et pleine largeur sous 480 px**, côte à côte au-dessus |
| Téléphone | Conservé |
| **Carte** | **Ajoutée à la scène**, en recto, légèrement inclinée derrière le téléphone — l'objet physique et l'écran dans un seul regard |
| Les trois flottantes | **Réduites à deux.** Trois étiquettes flottantes autour d'un téléphone, à 320 px, se chevauchent et forment un fouillis. On garde « QR généré » et le compteur de vues ; « Contact enregistré » sort |
| Sous 640 px | La scène passe **sous** le texte, jamais à côté. Hauteur bornée : la scène ne doit pas repousser l'action principale hors de l'écran d'ouverture |

**Le critère du hero, et il est mesurable** : à 360 × 640, le bouton « Créer un
compte » doit être **visible sans défiler**. C'est le seul rôle du hero. Si la
scène l'en empêche, la scène rétrécit.

### 4ter.8 Les variantes de bouton de la vitrine — hors système

La vitrine emploie `variant="dark"` et `variant="outline"`. Aucune des deux ne
figure dans la liste documentée (`primary`, `secondary`, `outline-primary`,
`outline-secondary`, `danger`, `outline-danger`, `link`). On trouve donc, sur la
page la plus vue, deux variantes qui n'existent pas dans le système.

Elles se ramènent au système au Lot 3 : `dark` → `primary` (le vert foncé
**est** la couleur primaire), `outline` → `outline-primary`. Le rendu ne change
pas ; ce qui change, c'est qu'il n'y a plus deux vocabulaires.

### 4ter.9 Les formules — le message avant la grille

`plans.blade.php` lit les formules depuis la table, ce qui est juste. Trois
points à tenir :

- **Le message commercial exact, en tête de section** : « 3 500 FCFA les 3 mois
  — votre carte PVC offerte à l'activation. » Il est identique ici, sur l'écran
  de paiement, dans l'e-mail et en administration. Un seul endroit le décide.
- **La mise en avant suit le prix, pas un identifiant.** C'est déjà corrigé —
  `$featured = ! $plan->isFree()` — après un défaut où la mise en avant visait
  « annuel », formule retirée du catalogue, ce qui affichait « Par mois » sur un
  abonnement de 90 jours. Ne pas revenir à un ciblage par slug.
- **Une seule colonne sous 640 px.** Trois formules côte à côte à 320 px
  donnent 96 px par colonne : la liste d'inclusions y devient illisible. La
  formule mise en avant reste **en premier** dans l'ordre empilé, pas au
  milieu.
- Le badge de mise en avant se distingue par la **bordure et le badge**, jamais
  par une ombre lourde ni un dégradé.

### 4ter.10 Les maquettes de téléphone — gel partiel levé

`_phone.scss` (530 lignes, 106 valeurs en dur) était gelé. Le gel tient sur la
**géométrie** — les proportions du châssis, l'encoche, le rayon : elles sont
justes et les rouvrir n'apporte rien.

Il est levé sur **les couleurs**. Impossible de poser une carte à côté d'un
téléphone si l'un obéit aux tokens et l'autre à 106 littéraux : la moindre
retouche de la charte les désaccorderait. Les couleurs de `_phone.scss` passent
donc aux tokens ; ses dimensions restent où elles sont, avec un commentaire
disant pourquoi.

### Acceptation du Lot 4 ter

- [ ] **La carte physique est visible sur la landing**, recto et verso, dans
      les deux variantes, sans JavaScript.
- [ ] À **360 × 640**, l'action « Créer un compte » est visible **sans
      défiler**.
- [ ] Un seul système de titres et un seul conteneur de section (`x-section`) —
      les quatre familles ont disparu.
- [ ] `.wrap` est sur l'échelle : plus de `20px` ni de `1180px`.
- [ ] `variant="dark"` et `variant="outline"` n'existent plus.
- [ ] **`+500` et les avatars ont disparu.** Aucun chiffre non mesuré ne
      subsiste sur la page.
- [ ] `prefers-reduced-motion` arrête le bandeau des métiers, et le contenu
      reste lisible à l'arrêt.
- [ ] Aucune opacité n'a été réintroduite sur le bandeau des métiers.
- [ ] Le message « 3 500 FCFA les 3 mois — votre carte PVC offerte à
      l'activation » est identique ici et sur les trois autres surfaces.
- [ ] Une seule colonne de formules sous 640 px, la mise en avant en premier.
- [ ] Les couleurs de `_phone.scss` passent par les tokens.
- [ ] Six largeurs × deux thèmes, et le poids de la page mesuré avant / après.

---

## LOT 4 QUATER — LA PAGE PUBLIQUE, AU NIVEAU DE LA CARTE

C'est la page la plus vue du produit et la moins maîtrisée : elle s'ouvre
après un scan, sur le téléphone d'un inconnu, souvent en 3G, et c'est elle qui
décide si QrID a l'air sérieux. Elle doit désormais **parler la même langue
que la carte** — c'est le sens de la référence fournie.

### 4quater.1 La composition, de haut en bas, à 320 px

| # | Bloc | Spécification |
|---:|---|---|
| 1 | **Couverture** | `aspect-ratio: 16/9`, hauteur bornée entre 180 px et 280 px. Voile `$voile-couverture` par-dessus — **fonctionnel, pas décoratif** : c'est lui qui rend le nom lisible sur une photo dont on ne sait rien |
| 2 | **Bloc d'identité** | Chevauche la couverture de `esp(7)` (32 px) vers le haut. Surface `--carte`, `rayon(lg)` 22 px, `var(--ombre-2)`, padding `esp(5)`. **Liseré `4px` `$vert-accent`** sur le bord de début — la signature de marque, confirmée par la référence |
| 3 | **Portrait** | Cercle 88 px, anneau `3px var(--carte)`, remonté de `esp(6)` pour mordre sur la couverture |
| 4 | **Nom** | Échelle `h1`, `var(--texte)`, **jamais tronqué** — trois paliers comme sur la carte : court / normal / long |
| 5 | **Fonction** | `typo(body)`, `var(--marque)` |
| 6 | **Entreprise** | `typo(body-sm)`, `var(--texte-2)` |
| 7 | **Coordonnées** | Lignes de 56 px, voir 4quater.2 |
| 8 | **Réseaux** | Grille `repeat(auto-fill, minmax(64px, 1fr))`, `gap: esp(3)`, tuiles `$tuile-reseau` 64 px |
| 9 | **Panneau QR** | Voir 4quater.3 |
| 10 | **La carte** | L'objet physique, recto/verso, voir 4quater.4 |
| 11 | **Pied** | Signature discrète de la plateforme + sélecteur de langue |
| — | **Barre d'actions** | `$action-mobile` 52 px, collante en bas, **au-dessus** de `env(safe-area-inset-bottom)`. Trois actions : Enregistrer le contact (primaire), Appeler, WhatsApp |

Au-dessus de `lg`, la page passe en deux colonnes : identité et coordonnées à
gauche, QR et carte à droite. La barre d'actions cesse d'être collante et
rejoint le bloc d'identité.

### 4quater.2 Les lignes de coordonnées — l'apport direct de la référence

C'est ici que le gain de professionnalisme est le plus net. Aujourd'hui les
lignes sont indifférenciées ; la référence les structure.

```
┌──────────────────────────────────────────────┐
│  ⬤   TÉLÉPHONE                               │   ⬤ = pastille 40px
│  40  +221 77 383 13 64                       │       background: var(--marque)
│      ────────────────────────────────────────│       couleur d'icône : var(--texte-inv-marque)
│  ⬤   E-MAIL                                  │       icône 20px, centrée
│      contact@exemple.sn                      │
│      ────────────────────────────────────────│   trait : 1px var(--bordure)
│  ⬤   SITE                                    │
│      www.exemple.sn                          │   libellé : typo(overline), var(--texte-3)
│      ────────────────────────────────────────│   valeur  : typo(body),     var(--texte)
│  ⬤   ADRESSE                                 │
│      123 avenue Cheikh Anta Diop, Dakar      │   hauteur de ligne : 56px minimum
└──────────────────────────────────────────────┘   écart pastille ↔ texte : esp(4)
```

- **Chaque ligne entière est le lien**, pas seulement la valeur : `tel:`,
  `mailto:`, `https:`, une carte pour l'adresse. La cible fait donc 56 px sur
  toute la largeur, très au-delà des 44 px requis.
- La pastille est **un fond de marque, pas un fond d'accent** :
  `$vert-accent` ne porte pas de contenu clair (3,37:1). `--marque` avec
  `--texte-inv-marque` donne 12,51:1, et c'est un couple déjà mesuré.
- Le trait de séparation est **entre** les lignes, jamais après la dernière.
- Une valeur longue (e-mail, URL) porte `overflow-wrap: anywhere` : elle se
  coupe, elle n'élargit pas la page.

### 4quater.3 Le panneau QR — transposé du recto de la carte

Le même objet que sur la carte, pour que le scan et la page se répondent :

- Panneau `$vert-accent`, `rayon(md)` 14 px, padding `esp(4)`.
- À l'intérieur, un carré **blanc** portant le QR en `$vert-fonce`.
  **Sombre sur clair, sur les deux variantes** — voir 1bis.3.
- Légende dessous, `typo(caption)`, `var(--texte-3)`.
- **Le QR est en ligne dans la page, plus en surcouche.** La surcouche
  actuelle repose sur un motif `:target` avec `href="#"` : c'est le seul lien
  mort du produit, celui que compte le cliquet. En posant le QR dans le flux,
  le lien mort disparaît et le compteur descend de 1 à 0.

### 4quater.4 La carte, montrée comme un objet

Recto et verso, à la variante du porteur, dans la largeur d'aperçu.

- Permutation en `$d-carte` (600 ms), courbe `$courbe`.
- **Sans JavaScript, les deux faces s'affichent l'une sous l'autre.** Jamais
  une face cachée que seul un script peut révéler.
- L'épaisseur de pile — l'idée retenue de la référence — n'apparaît **que
  dans l'application** (aperçu, tableau de bord). Sur la page publique, on
  reste plat : c'est la page la plus légère du produit.

### 4quater.5 Ce qui ne change pas, et qu'il ne faut pas casser

- **Aucun JavaScript applicatif chargé** : `carte-publique.js` ne contient que
  l'ouverture des contacts natifs, et cela reste vrai.
- Zéro police distante, zéro image externe, SVG en ligne.
- La page s'affiche dans la langue **du visiteur**.
- Les mesures déjà acquises ne régressent pas : coordonnées 56 px, tuiles
  64 px, actions 52 px, nom sur l'échelle `h1`.
- Le contraste du nom sur la couverture **se mesure enfin** : le pire cas est
  une photo claire. Si le voile ne suffit pas, on le renforce — c'est le point
  laissé ouvert par l'audit, et il se règle ici.

### Acceptation du Lot 4 quater

- [ ] Les six largeurs × deux thèmes, plus **trois couvertures d'épreuve** :
      une sombre, une claire, une saturée. Le nom passe 4,5:1 sur les trois.
- [ ] Le lien mort `href="#"` a disparu : `design:check` rend 0.
- [ ] Chaque ligne de coordonnées est un lien de 56 px sur toute la largeur.
- [ ] Le QR est sombre sur clair, quelle que soit la variante.
- [ ] La page ne charge toujours aucun JavaScript applicatif — vérifié au
      panneau réseau.
- [ ] Les deux faces de la carte sont visibles sans JavaScript.
- [ ] Poids de la page mesuré avant / après, et commenté dans le commit.

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

### 5.c — La carte PVC → voir **Lot 5 bis**

La carte est dégelée. Elle ne se vérifie plus, elle se travaille — mais dans
un périmètre borné, listé au lot suivant.

---

## LOT 5 BIS — LA CARTE : DEUX VARIANTES, UNE GÉOMÉTRIE, TROIS TAILLES

**Ce qui est déjà juste, et qu'on ne rouvre pas.** Le travail fait sur la
carte est solide, et il faut le dire avant de lister ce qui change :

- Le format est **CR80** exact (`aspect-ratio: 1.586` = 85,6 × 54 mm), et la
  hauteur n'est jamais fixée en dur.
- **Tout est dimensionné en `cqw`**, unités de conteneur : la carte se
  redimensionne entièrement par la largeur de son parent, sans un seul point
  de rupture. C'est la meilleure décision technique du fichier, et elle règle
  à elle seule la question de « la taille ».
- Le nom a **trois paliers** (`name--short` 9cqw, normal 6,2cqw,
  `name--long` 4,8cqw) : un nom long ne casse pas la composition.
- Les **quatre faces existent déjà** — recto blanc, recto vert, verso blanc,
  verso vert — pilotées par `.light` / `.dark`.
- `App\Enums\VarianteCarte` **modélise déjà les deux variantes**, avec la
  blanche par défaut, et documente pourquoi : le vert plein consomme l'encre,
  marque le massicot, garde les traces de doigts, et **inverse le QR Code**.

Le nuancier à cinq teintes a d'ailleurs déjà été supprimé, pour une raison qui
tient et qu'il faut garder en tête à chaque décision de ce lot :

> Chaque carte imprimée est un support de communication pour la plateforme.
> Cinq teintes au choix produisaient cinq marques différentes : celui qui
> reçoit une carte ambre et une carte grenat ne voit pas deux clients d'un même
> service, il voit deux services.

### 5bis.1 Ce qui change — la liste complète, et elle est courte

| # | Changement | Portée | Raison |
|---:|---|---|---|
| 1 | **`border-radius: 3.7cqw`** au lieu de `0` | Écran **et** impression | Le rayon normatif CR80 est 3,18 mm. Des angles vifs sont ce que l'imprimeur ne livre pas — voir 1bis.4 |
| 2 | **Panneau clair autour du QR** sur la variante verte | Les deux faces qui portent un QR | Rend le QR sombre sur clair sur les **deux** variantes, conformément à ISO/IEC 18004 — voir 1bis.3. **Priorité 1 du lot** |
| 3 | **Le dégradé de la variante verte devient un aplat** | Impression, et écran par cohérence | `linear-gradient(135deg, #124a3a, #0B3B2E, #08281f)` sur du PVC produit du banding, consomme davantage d'encre, et un dégradé décoratif est dans les interdits. `$vert-fonce` plat |
| 4 | **Les couleurs de la carte passent par les tokens** | `carte-qrid.html`, `_card.scss`, `_carte-publique.scss` | `--forest`, `--leaf`, `--mint`, `--paper`, `--ink` sont les valeurs de la charte **recopiées**. Elles deviennent des références |
| 5 | **Trois largeurs nommées** au lieu d'un `min(420px, 92vw)` isolé | Toute l'application | Voir 5bis.3 |
| 6 | **L'écran de choix montre deux cartes, pas deux pastilles** | Étape 3 du parcours | Voir 5bis.4 |

**Ce qui ne change pas** : les proportions, la hiérarchie, la place du QR,
l'onde NFC, les trois paliers du nom, la matière retenue (la plus discrète des
trois essayées), le format, l'échelle en `cqw`, et le fait que la blanche est
la variante par défaut.

### 5bis.2 Le rayon, écrit une fois

Dans `_tokens.scss`, à la suite de la carte des rayons :

```scss
// LE RAYON DE LA CARTE PHYSIQUE — imposé par la norme, pas choisi.
//
// CR80 : 85,6 × 54 mm, rayon de coin 3,18 mm.
//   3,18 / 85,6 = 3,71 % de la largeur.
//
// En `cqw` et non en px : toute la carte est dimensionnée en unités de
// conteneur, donc le rayon suit l'échelle sans être recalculé à chaque
// taille d'affichage. Une valeur en px casserait à la vignette.
//
// C'est la SEULE exception à la carte $rayons, et elle est physique.
$rayon-carte-cr80: 3.7cqw;
```

L'ancien commentaire de `docs/DESIGN.md` — « la carte PVC utilise `none`, et
c'est une contrainte physique » — est **faux et se corrige au Lot 6**. Le
laisser reviendrait à documenter une erreur comme une règle.

### 5bis.3 Les trois tailles, nommées

Une seule géométrie, trois conteneurs. Comme tout est en `cqw`, il n'y a rien
d'autre à déclarer que la largeur du parent.

| Nom | Largeur | Où | Note |
|---|---|---|---|
| `--carte-apercu` | `min(420px, 92vw)` | Page publique, aperçu du parcours, `/design-system` | La valeur actuelle, conservée |
| `--carte-bloc` | `min(320px, 100%)` | Bloc « Ma carte » du tableau de bord | Doit tenir dans une colonne de tableau de bord à 320 px |
| `--carte-vignette` | `min(200px, 100%)` | Écran de choix de variante, listes d'administration | À 200 px, le nom au palier long reste lisible ; en dessous, il ne l'est plus |

**Impression** : 85,6 × 54 mm plus le fond perdu de l'imprimeur. Le PDF ne
passe pas par ces largeurs — `printable.blade.php` porte son propre bloc
`<style>`, et c'est légitime. Il reçoit en revanche les mêmes six changements,
à la main, avec le commentaire d'alignement.

**Écran de choix sous 480 px** : les deux vignettes s'empilent, une par
ligne — la règle « une seule colonne sous 480 px » s'applique. Deux vignettes
de 200 px côte à côte demanderaient 412 px, ce qui déborde à 320.

### 5bis.4 L'écran de choix — le savoir qui est dans le code doit remonter à l'écran

`VarianteCarte` porte déjà les libellés, les descriptions, et l'avertissement
d'impression. **Rien de tout cela n'est aujourd'hui montré au client.** C'est
le gain le plus facile de ce lot.

L'étape 3 du parcours affiche :

- **Deux aperçus de recto réels**, à `--carte-vignette`, pas deux pastilles de
  couleur. La formulation compte : « un nuancier invite à composer, deux
  aperçus invitent à choisir ».
- Sous chaque aperçu, le libellé (`VarianteCarte::libelle()`) et la
  description (`VarianteCarte::description()`).
- Sur la **blanche** uniquement, une mention discrète : *recommandée — le QR
  Code y est sombre sur clair, le sens que tous les lecteurs attendent.* Pas
  un avertissement alarmant sur la verte : une raison donnée sur la
  recommandée. C'est la même information, dite du bon côté.
- Deux `<input type="radio">` natifs, zone tactile ≥ 44 px couvrant tout
  l'aperçu, `aria-describedby` vers la description.
- **La blanche est pré-cochée** — `VarianteCarte::DEFAUT`. L'étape 3 ne demande
  aucune saisie : un client pressé la franchit d'un clic, et c'est un critère
  du chrono de 3 minutes.
- **Sans JavaScript**, la variante cochée est déjà appliquée par une variable
  CSS. Aucun aperçu ne dépend d'un script.

### 5bis.5 Les cinq variables locales de `carte-qrid.html`

Elles portent aujourd'hui les valeurs de la charte, recopiées à la main :

| Variable locale | Valeur | Token à référencer |
|---|---|---|
| `--forest` | `#0B3B2E` | `$vert-fonce` |
| `--leaf` | `#1E9E7A` | `$vert-accent` |
| `--mint` | `#E4F2EC` | `$vert-clair` |
| `--paper` | `#FFFFFF` | `$c-carte` |
| `--ink` | `#0A1F1A` | `$c-texte` |

Elles restent — un nom court est utile dans un fichier de composition — mais
elles ne portent plus de littéral. Trois valeurs présentes dans le fichier ne
sont dans aucune de ces cinq et doivent être tranchées : `#8FD9C2`, `#2B4A40`,
`#8FA39A`. Les mesurer, puis soit les rattacher à un token existant, soit les
déclarer dans `_tokens.scss` avec leur ratio en commentaire. Aucune troisième
issue.

### 5bis.6 La carte n'est pas un thème

Point de vigilance, parce que l'erreur est naturelle : **la carte ne bascule
pas avec le thème de l'interface.** Une carte blanche reste blanche en thème
sombre — c'est un objet physique, pas une surface d'interface. Ce qui change
autour d'elle, c'est le fond de la page.

Concrètement : la carte n'emploie **jamais** `var(--carte)`, `var(--page)` ni
`var(--texte)`. Elle emploie les tokens de marque et les tokens de thème
**clair** explicitement. C'est la seule zone du produit où c'est le cas, et
cela s'écrit en commentaire dans le fichier, sinon quelqu'un « corrigera » cet
écart dans six mois.

### Acceptation du Lot 5 bis

- [ ] `border-radius: 3.7cqw` sur les quatre faces, avec le calcul en
      commentaire.
- [ ] **Le QR est sombre sur clair sur les deux variantes** — vérifié en
      scannant les deux, avec deux lecteurs différents dont un ancien.
- [ ] Aucun dégradé ne subsiste sur la carte.
- [ ] `design:check` rend **0 valeur en dur** dans `carte-qrid.html`,
      `_card.scss` et les faces de `_carte-publique.scss`.
- [ ] Les trois largeurs nommées existent et sont employées aux trois
      endroits ; la carte reste lisible à `--carte-vignette` avec un nom au
      palier long.
- [ ] L'écran de choix montre deux cartes, la blanche pré-cochée, la
      recommandation affichée, et fonctionne **sans JavaScript**.
- [ ] La carte ne change pas d'apparence entre thème clair et thème sombre.
- [ ] Le PDF imprimable porte les six mêmes changements.
- [ ] Une épreuve d'impression est commandée **avant** tout lot de production :
      le rayon, l'aplat et le panneau QR se jugent sur du PVC, pas sur un
      écran.

---

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
13. **La coque mobile** : le dock, ses cinq entrées, la borne à 768 px, la
    règle des pastilles, et pourquoi « Plus » est une vraie route.
14. **La carte** : les deux variantes, la géométrie CR80, le rayon de
    3,7 cqw avec son calcul, les trois largeurs nommées, et la règle « la
    carte n'est pas un thème ».

**Deux affirmations du fichier actuel sont fausses et se corrigent
explicitement**, plutôt que d'être silencieusement remplacées :

- « La carte PVC utilise `rayon(none)`, et c'est une contrainte physique » —
  la norme CR80 impose au contraire un rayon de 3,18 mm. Écrire la correction,
  avec la mesure.
- « Pile système, aucun téléchargement » présenté comme un état de fait, alors
  que `$font` annonce une famille `'QrID'` absente. Écrire la décision prise
  au Lot 1, quelle qu'elle soit.

Une documentation qui se corrige devant le lecteur se fait croire. Une
documentation qui réécrit son passé ne se fait plus croire une seconde fois.

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
- **Un test sur le dock** : échec si un layout lui passe plus de **cinq**
  entrées, ou si un libellé court dépasse la longueur qui tient dans 57 px —
  dans les deux langues. C'est la contrainte la plus facile à casser en
  ajoutant « juste une entrée », et celle qui rend le dock inutilisable.
- **Un test sur la carte** : échec si une face porte `border-radius: 0`, un
  dégradé, ou un QR clair sur fond sombre. Les trois régressions sont
  invisibles à la relecture et coûteuses à l'impression.

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

À appliquer telle quelle. Un écran qui n'a pas ces 26 cases cochées n'est pas
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
16. **Le dock ne recouvre rien** : le contenu se dégage de `esp(11)` plus la
    zone sûre, et toute barre collante se cale au-dessus du dock.
17. **`env(safe-area-inset-bottom)` respecté** partout où quelque chose est
    fixé en bas.

**Couleur et contraste**
18. Aucune couleur en dur.
19. Tout texte ≥ 4,5:1, tout tracé ≥ 3:1, dans les deux thèmes.
20. Lisible au repos : aucune couleur définie seulement dans `:hover`.
21. Aucun lien souligné, focus visible renforcé.

**Contenu**
22. Aucun texte en dur ; `lang/fr` et `lang/en` complets — **libellés courts
    du dock compris, et l'anglais doit tenir dans 57 px**.
23. Vocabulaire compte/profil respecté.
24. Montants en FCFA entiers (« 3 500 FCFA ») ; téléphones via
    `formatted_phone`.

**Robustesse**
25. Rendu correct sans JavaScript.
26. Aucun `href="#"`, aucun bouton sans action, aucune liste non paginée.

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
| Valeurs en dur, toutes catégories | **1 366** (relevé `design:check`) | **0** hors liste blanche justifiée |
| Familles CSS concurrentes pour le champ | **14** | **1** |
| Systèmes de boutons | **4** | **1** |
| Racines CSS par famille (carte, tableau, badge, modale, alerte) | 3 / 3 / 2 / 2 / 2 | 1 chacune |
| Couples sous 4,5:1, thème clair | 5 distincts, ~109 occurrences | **0** |
| Couples sous 4,5:1, thème sombre | 8 distincts, ~145 occurrences | **0** |
| `@media (max-width)` | **18**, dont 15 hors feuilles gelées | **3** (les gelées seules) |
| `!important` (occurrences) | **44**, dont 27 dans le socle | **27** — plafond, jamais dépassé |
| Styles en ligne (hors e-mails et PDF) | **98** | **0** |
| Liens morts `href="#"` | **1** (surcouche QR) | **0** — réglé au Lot 4 quater |
| Soulignements | **9** | **0** |
| Classes d'état vide | **0** | 1, employée partout |
| Verts de marque distincts | **2** (`#0B3B2E`, `#0B5D3B`) | **1** |
| Sources de valeurs | 2 (`_tokens`, `_variables`) | **1** |
| Commandes de vérification | **3** ✅ | 3, **dans le CI** |
| Vérification navigateur | **aucune** | 22 pages × 2 thèmes × 6 largeurs |
| Tests | 663 | 663 + les tests de garde-fou |
| Taille CSS compilée | **364 551 o** | ≤ +5 % |
| Gestes pour changer d'écran sur téléphone | **2** (hamburger, puis lien) | **1** |
| Borne de repli de la navigation | **992 px** (Bootstrap) | **768 px** (`$ruptures`) |
| Variantes de carte | 2, déjà modélisées | 2, **et montrées comme deux cartes** |
| QR sombre sur clair | **1 variante sur 2** | **2 sur 2** |
| Rayon de la carte | `0` — hors norme CR80 | `3.7cqw` = 3,18 mm |
| Apparitions de la carte sur la landing | **0** | recto + verso, deux variantes |
| Systèmes de titres sur la landing | **4** | **1** |
| Chiffres non mesurés affichés | **1** (« +500 professionnels ») | **0** |
| Racines CSS pour le tableau | **3** | **1**, avec bascule en cartes |
| Espacement des formulaires | `mb-3` de Bootstrap | l'échelle, Partie 3 |
| Textes en dur dans un composant | ≥ 1 (« (facultatif) ») | **0** |

---

# PARTIE 8 BIS — POURQUOI C'EST LA DERNIÈRE FOIS

La demande est explicite : « ça doit être la dernière fois qu'on revient pour
le design ». Une promesse de ce genre ne se tient pas par la volonté, elle se
tient par la structure. Trois choses la rendent vraie, et une la rendrait
fausse.

**Ce qui la rend vraie**

1. **Le cliquet.** `config/design.php` plafonne six compteurs, et le plafond
   ne remonte jamais. Une valeur en dur ajoutée fait échouer le CI. La dette
   ne peut plus revenir par accumulation silencieuse — c'est ainsi qu'elle est
   revenue la première fois.
2. **La source unique.** Après le Lot 1, changer une couleur, un espacement ou
   un rayon dans tout le produit se fait à **un seul endroit**. Une refonte
   future n'est plus un chantier : c'est une modification de `_tokens.scss`.
   C'est très exactement ce qu'on achète en payant ces douze lots.
3. **Le test des familles.** Après le Lot 7, une seconde racine CSS pour un
   objet déjà décrit fait échouer le CI. Les 4 systèmes de boutons et les 5 de
   champs ne peuvent plus réapparaître.

**Ce qui la rendrait fausse**

Un seul écran écrit sans passer par la bibliothèque. Un seul. C'est pour cela
que le Lot 6 — `/design-system` refaite et `docs/DESIGN.md` réécrit — n'est
pas de la documentation de confort : c'est le lot qui décide si la refonte
tient. Sa recette finale est d'ailleurs la seule qui ne se mesure pas par une
commande : **quelqu'un qui n'a pas fait le chantier écrit une page neuve en
suivant la doc, et la page ressemble au reste.** Si elle ne ressemble pas, la
doc est incomplète, et on y retourne — avant de clore.

---

# PARTIE 9 — CE QU'ON DIT À CHAQUE FIN DE LOT

Trois lignes, pas plus :

1. **Fait** — ce qui a changé, avec le chiffre avant/après de `design:check`.
2. **Bloqué** — ce qui ne peut pas avancer, et ce qu'il faut pour débloquer.
3. **Reste** — le lot suivant, et ce qu'il faut valider avant.

Tout blocage qui dépasse deux heures se signale plutôt que de s'obstiner.
Toute idée hors lots va dans `V2.md`.
