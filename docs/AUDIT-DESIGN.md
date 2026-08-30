# Audit de design — état avant unification

Relevé du 30 août 2026, sur la branche `main`.

Tout ce qui suit est **mesuré** : les valeurs en dur par analyse des fichiers,
les contrastes par lecture du DOM rendu dans un navigateur, sur 22 pages, dans
les deux thèmes. Aucun chiffre n'est estimé.

---

## Ce que la mesure a corrigé dans la mesure elle-même

Trois relevés successifs ont été nécessaires, et l'écart entre eux est
instructif.

| Version | Résultat | Défaut |
|---|---:|---|
| 1 | 1 963 valeurs en dur | comptait les commentaires et les tracés SVG |
| 2 | 651 | écartait les attributs de composants — de vraies occurrences |
| 3 | **1 598** | sépare le code du texte affiché |

Pour les contrastes, le premier balayage annonçait **7 défauts** sur la page
d'accueil en thème sombre. Vérification directe : il y en a **un**. La bascule
de thème par une classe JavaScript dans une iframe n'appliquait pas la feuille
sombre. Le relevé fiable passe par le **vrai mécanisme** — le thème rendu par
le serveur.

Un audit qui gonfle son chiffre ne sert à rien ; un audit qui le rabote en
jetant du vrai est pire, parce qu'il donne le sentiment d'avoir fini.

---

## 1. Couleurs en dur

**164 occurrences, 80 valeurs distinctes** dans les SCSS, hors `_variables.scss`.

| Valeur | Occurrences | Fichiers principaux |
|---|---:|---|
| `rgba(255,255,255,…)` | 22 | `_carte-publique-matiere`, `_carte-publique`, `_card` |
| `rgba(10,31,26,…)` | 13 | `_phone`, `_whatsapp`, `_card` |
| `#FFF` | 6 | `_carte-publique`, `_theme`, `_phone` |
| `#25D366` | 6 | `_contact`, `_dashboard`, `_whatsapp` |
| `#FF9A8F` | 6 | `_theme-dark`, `_theme` |
| `#000` | 5 | `_components`, `_auth` |
| `#0B3B2E` | 2 | `_components`, `_phone` — **la marque, recopiée** |

Le dernier cas est le plus parlant : la couleur de marque est déjà une variable,
et elle est malgré tout réécrite à la main à deux endroits.

**127 occurrences supplémentaires dans les vues Blade**, dont 40 distinctes.
Elles se répartissent en deux populations qu'il ne faut pas confondre :

- **Les e-mails** (`#0B5D3B`, `#1E293B`, `#94A3B8`, `#F1F5F9`…) — ce sont des
  valeurs **obligatoirement** en ligne. Aucun client de messagerie ne lit une
  feuille de style externe, et beaucoup ignorent les propriétés CSS
  personnalisées. Ces couleurs ne peuvent pas passer par les tokens.
  Elles doivent en revanche être **alignées** sur eux à la main, ce qui n'est
  pas le cas aujourd'hui : les e-mails utilisent `#0B5D3B`, l'application
  `#0B3B2E`. Deux verts de marque différents.
- **`design-system.blade.php`** (12 occurrences) — page locale, refaite à
  l'étape 2.

---

## 2. Tailles, espacements, rayons, ombres

| Catégorie | Occurrences | Valeurs distinctes | La plus fréquente |
|---|---:|---:|---|
| Tailles de police | 298 | **41** | `12.5px` (40 fois) |
| Espacements | 781 | **63** | `14px` (74 fois) |
| Rayons | 63 | 20 | `2px` (14 fois) |
| Ombres | 24 | 18 | — |
| Durées | 46 | 21 | `.2s` |

**41 tailles de police distinctes** pour un produit de cette taille, dont
`12.5px`, `11.5px`, `13.5px`, `14.5px`, `9.5px`, `8.5px` et `10.5px`. Personne
n'a choisi 12,5px contre 12px ou 13px : c'est le résultat d'ajustements locaux
jamais repris. Même chose pour les espacements — `7px` employé 21 fois, `9px`
24 fois, `11px` 20 fois.

C'est exactement ce qu'une échelle rend impossible.

---

## 3. Styles propres à une page

| Emplacement | Volume | Statut |
|---|---:|---|
| `profile/printable.blade.php` | **bloc `<style>` de 194 lignes** | légitime — le rendu PDF n'accède à aucune feuille externe |
| `design-system.blade.php` | 55 attributs `style=` | refait à l'étape 2 |
| Gabarits d'e-mail (14 fichiers) | ~90 attributs `style=` | légitime — contrainte des clients de messagerie |
| `admin/statistics.blade.php` | 8 attributs `style=` | **à reprendre** |

Hors e-mails, PDF et page de référence, il reste **8 styles en ligne** dans une
seule vue applicative. C'est peu, et c'est une bonne nouvelle : la dette est
dans les SCSS, pas dans les vues.

---

## 4. Composants dupliqués

44 composants Blade existent déjà. Le problème n'est pas leur nombre, c'est le
nombre de **familles CSS concurrentes** qui décrivent la même chose :

| Famille | Racines distinctes | Détail |
|---|---:|---|
| **Champ** | **14** | `.f__control`, `.f__label`, `.f__error`, `.f__hint`, `.f__count`, `.f__eye`, `.f__alert`, `.auth-*`, `.adm-champ`… |
| Carte | 3 | `.card`, `.carte`, `.adm-*` |
| Tableau | 3 | `.table`, `.liste`, `.adm-table` |
| Bouton | 2 | `.btn`, `.adm-btn` |
| Badge | 2 | `.badge`, `.tagline` |
| Modale | 2 | `.offcanvas`, `.adm-modale` |
| Alerte | 2 | `.alert`, `.mail-spam` |
| État vide | 0 | aucune classe dédiée — chaque page réinvente |

Le champ de formulaire est le cas critique : **trois systèmes parallèles**
(public, authentification, administration) pour un même objet. Une correction
de hauteur tactile doit aujourd'hui être faite trois fois.

---

## 5. Contrastes inférieurs à 4,5:1

Mesurés dans le navigateur, sur le DOM rendu, **22 pages**, les deux thèmes.
Le texte posé sur une **image** est exclu et compté à part : son contraste
dépend de la photo et ne se vérifie pas statiquement.

### Thème clair

| Ratio | Couple | Sélecteur | Occurrences | Pages |
|---:|---|---|---:|---:|
| **1,46** | `#FFC107` sur `#F2F3F1` | `.badge.text-bg-light` | 23 | 3 |
| **3,03** | `#1E9E7A` sur `#F2F3F1` | `.badge.text-bg-light` | 25 | 5 |
| **3,37** | `#FFFFFF` sur `#1E9E7A` | `.brand__mark` | 18 | **18** |
| **4,04** | `#D63384` sur `#F2F3F1` | `<code>` | 1 | 1 |
| **4,07** | `#DC3545` sur `#F2F3F1` | `.badge.text-bg-light` | 42 | 4 |

### Thème sombre

Les mêmes, plus :

| Ratio | Couple | Sélecteur | Occurrences |
|---:|---|---|---:|
| **2,06** | `#9BB0A8` sur `#F2F3F1` | `.badge.text-bg-light` | 47 |
| **2,52** | `#B42318` sur `#16211D` | `.adm-stat__var.is-baisse` | 3 |
| **2,95** | `#5C6B66` sur `#16211D` | `.board-empty__text` | 2 |

### Les deux causes, et elles sont structurelles

**1. Le badge n'est pas thémé.** `.badge.text-bg-light` conserve son fond clair
`#F2F3F1` **y compris en thème sombre**. C'est ce qui produit les pires ratios :
un fond clair figé sous un texte pensé pour le sombre.

**2. Les couleurs d'état viennent de Bootstrap, pas de la charte.** `#FFC107`,
`#DC3545`, `#D63384` n'ont jamais été confrontées à la surface sur laquelle
elles atterrissent. « En attente » en jaune vif sur gris clair donne **1,46:1** :
un texte que personne ne peut lire, affiché 23 fois sur trois écrans dont la
liste des paiements.

### Faux positifs écartés

`.pubc__nom`, `.pubc__role`, `.pubc__entreprise` ressortent à 1:1 — **à tort**.
Le nom est en blanc sur la **photo de couverture**, un `<img>` positionné en
absolu que l'analyse du DOM ne voit pas, protégé par un dégradé sombre et une
`text-shadow`. Le contraste réel dépend de la photo ; il ne se vérifie pas
statiquement, et la garantie repose sur le voile.

**À faire à l'étape 3** : mesurer le pire cas sur une couverture claire, et
renforcer le voile si nécessaire.

---

## Synthèse

| Mesure | Valeur |
|---|---:|
| Valeurs en dur, toutes catégories | **1 598** |
| Dont hors e-mails / PDF / page de référence | ~1 400 |
| Fichiers SCSS | 19, **8 108 lignes** |
| Familles CSS concurrentes pour le champ | **14** |
| Couples sous 4,5:1, thème clair | 5 distincts, ~109 occurrences |
| Couples sous 4,5:1, thème sombre | 8 distincts, ~145 occurrences |
| Défaut présent sur le plus de pages | `.brand__mark` — **18 pages** |

Le défaut le plus répandu — le médaillon « QI » — se corrige à **un seul
endroit**. C'est la démonstration de ce que l'unification apporte.
