> # ⚠ CE DOCUMENT EST PÉRIMÉ
>
> Il décrit un système qui n'existe plus, et le suivre **réinjecte
> l'ancien** dans tout ce qu'on écrit. Il est réécrit intégralement au
> **Lot 6**. D'ici là, la source de vérité est `resources/sass/_tokens.scss`.
>
> **Les quatre divergences, mesurées :**
>
> | Sujet | Ce document dit | `_tokens.scss` dit | Conséquence |
> |---|---|---|---|
> | Vert de marque | `#0B5D3B` | `#0B3B2E` | deux verts de marque dans le produit |
> | Espacement | `3 = 16px`, 7 valeurs | `3 = 12px`, `4 = 16px`, 13 valeurs | un `esp(3)` écrit d'après ce document vaut 12px, pas 16 |
> | Rayons | `lg = .75rem` (12px) | `lg = 22px` | presque le double |
> | Typographie | échelle `clamp()` | carte `mobile`/`bureau` en px | deux méthodes incompatibles |
>
> Rien d'autre ne cite ce fichier tant qu'il n'est pas réécrit.

---

## La police — décision du Lot 1

**Option B retenue : la pile système, assumée.**

`'QrID'` ouvrait la pile de `$font`, cinq `@font-face` l'attendaient en
commentaire dans `_base.scss`, et `public/fonts/` n'a jamais existé. Le repli
fonctionnait — mais la première famille déclarée était un mensonge.

**Pourquoi B** : le poids est un critère du produit. Le trafic public arrive
par scan de QR Code, souvent en 3G, et cinq graisses coûtent 150 à 250 Ko
avant le premier mot lisible. La pile système ne coûte rien et rend une
police native sur chaque plateforme.

**D'où vient le caractère, alors** : de l'échelle, des graisses (800 sur
`display` et `h1`) et de l'interlettrage (`-.03em` sur `display`, `-.025em`
sur `h1`). C'est cela qui donne le ton institutionnel, pas le dessin des
lettres.

**Réversible** : l'option A reste ouverte en V2. Elle ne demandera que de
déposer les `.woff2` dans `public/fonts/` et de remettre une famille en tête
de la pile — **jamais un CDN**.

---

## L'outillage de vérification

Trois commandes, ajoutées au Lot 0. Elles remplacent les relevés manuels,
qui ont rendu trois chiffres différents (1963, 651, 1598) avant qu'on
comprenne que l'écart venait de l'auditeur, pas du code audité.

### `php artisan design:check`

Le garde-fou. Il signale les valeurs en dur hors `_tokens.scss`, les
`!important`, les `@media (max-width)`, les styles en ligne, les liens
morts et les soulignements.

```
php artisan design:check                    # le tableau de conformité
php artisan design:check --detail           # chaque occurrence, fichier et ligne
php artisan design:check --categorie=couleur
php artisan design:check --json             # pour l'intégration continue
```

**Il compare à un PLAFOND, pas à zéro.** Comparer à zéro ferait échouer
le dépôt dès aujourd'hui, et la commande serait désactivée dans la
semaine. Les plafonds vivent dans `config/design.php` et forment un
**cliquet** : chaque lot les baisse, ils ne remontent jamais. La bonne
réponse à un dépassement est de corriger la valeur, pas le plafond.

État au Lot 0 :

| Mesure | Départ | Cible |
|---|---:|---|
| valeurs en dur | 1366 | 0 hors sources |
| `!important` | 44 | 27 après le Lot 2 |
| `@media (max-width)` | 18 | 3 (feuilles gelées) |
| styles en ligne | 98 | 0 hors e-mails et PDF |
| liens morts | 1 | 0 |
| soulignements | 9 | 0 |

### `php artisan design:audit`

Le relevé complet, qui produit `audit-design.json`. Il existait hors du
dépôt, ce qui rendait ses chiffres invérifiables — un chiffre dont on ne
peut pas refaire le calcul est une affirmation, pas une mesure.

Il partage son moteur avec `design:check` : les deux ne peuvent donc pas
diverger, ce qui était le vrai risque.

### `php artisan design:contraste`

Il lit `_tokens.scss` et mesure chaque couple texte/fond, dans les deux
thèmes. **20 couples, aucun sous 4,5:1.**

Il aplatit les fonds semi-transparents avant de mesurer, et c'est le
point : un badge pose un fond qui est sa propre teinte à 16 %, lequel
éclaircit la surface. Le texte perd alors du contraste contre son PROPRE
fond. Mesurer contre la surface nue donne un chiffre faux dans le sens
rassurant — celui qu'on ne corrige jamais. Treize couleurs échouaient
ainsi sans que rien ne le signale.

Ce qu'il ne peut PAS faire : dire ce qu'un visiteur voit. Une couleur
héritée dépend de la cascade, un texte sur photo dépend de la photo.

### `node tests/Browser/contraste.mjs`

Le relevé sur le **DOM rendu** : 10 pages publiques × 6 largeurs × 2
thèmes. Il mesure aussi les cibles tactiles sous 44px et les débordements
horizontaux.

Deux erreurs déjà commises, que ce script ne refait pas :

- **Le thème vient du serveur**, par le cookie de préférence — pas d'une
  classe posée en JavaScript. Un premier relevé basculait le thème dans
  une iframe : la feuille sombre ne s'appliquait pas, et le script
  annonçait 7 défauts sur la page d'accueil quand il y en avait un.
- **Le texte sur une image est exclu et compté à part.** `.pubc__nom` est
  du blanc sur la photo de couverture, un `<img>` positionné en absolu :
  un parcours du DOM ne voit que du blanc sur blanc et rend 1:1. Faux
  positif. Le contraste réel dépend de la photo et repose sur le voile.

### Le budget CSS

`config/design.php` porte la taille de référence du bundle compilé —
**364 551 octets** au Lot 0. Un lot qui l'augmente de plus de 5 %
s'explique dans son commit. Le trafic public arrive par scan de QR Code,
souvent en 3G : chaque kilo-octet se paie en secondes devant un écran
blanc.

---

# Système de design — Identité Pro

Référence unique pour toutes les pages du produit.
Page de démonstration en local : **http://127.0.0.1:8000/design-system**

Stack : Bootstrap 5.3 surchargé en SCSS. Aucun autre framework CSS.
Aucun JavaScript personnalisé hors de l'exception documentée (bascule
d'affichage des mots de passe, `resources/js/app.js`).

---

## 1. Palette

Toutes les couleurs proviennent de `resources/sass/_variables.scss`.
**Aucune couleur en dur ailleurs dans le projet.**

### Marque

| Token | Hex | Usage |
|---|---|---|
| `$brand-600` (primaire) | `#0B5D3B` | Boutons principaux, liens, en-tête d'e-mail, états actifs |
| `$brand-700` | `#094E32` | Survol des éléments primaires |
| `$brand-100` | `#E4F2EA` | Fonds pâles, menu actif, encarts (`.bg-brand-50`) |
| `$accent-500` | `#D4A017` | Accent or : badge premium, mise en exergue ponctuelle |

**Pourquoi ce vert** : au Sénégal, le vert porte la confiance et la réussite
(drapeau national, univers bancaire). Cette valeur désaturée et sombre évoque
le sérieux institutionnel plutôt que le « vert startup », et passe le contraste
AA en blanc sur aplat.

### États

| Token | Hex | Usage |
|---|---|---|
| `$success` | `#157347` | Confirmation, paiement abouti, profil publié |
| `$info` | `#0E7490` | Information neutre |
| `$warning` | `#B45309` | Abonnement bientôt expiré, action requise |
| `$danger` | `#B42318` | Erreur, paiement refusé, suppression |

### Gris

`$gray-50 #F8FAFC` (fond de page) · `$gray-100 #F1F5F9` · `$gray-200 #E6EAF0`
(bordures) · `$gray-300 #D0D7E2` (bordures de champ) · `$gray-400 #94A3B8`
(placeholder) · `$gray-500 #64748B` (texte secondaire) · `$gray-800 #1E293B`
(texte courant) · `$gray-900 #0F172A` (en-tête admin).

---

## 2. Typographie

**Pile système, aucun téléchargement.** Le trafic public arrive par scan de QR
Code, souvent en 3G : une police distante retarde le premier rendu. Le caractère
vient de l'échelle, des graisses et de l'interlettrage (`-0.015em` sur les titres).

| Rôle | Taille | Graisse |
|---|---|---|
| h1 | `clamp(1.75rem, 1.35rem + 1.8vw, 2.5rem)` | 700 |
| h2 | `clamp(1.5rem, 1.25rem + 1.1vw, 2rem)` | 700 |
| h3 | `clamp(1.25rem, 1.1rem + .7vw, 1.5rem)` | 700 |
| Corps | `1rem` / interligne `1.6` | 400 |
| Petit | `0.875rem` | 400 |

Largeur de lecture : `.prose` limite à `68ch`.
Pour activer une police de marque : déposer les `.woff2` dans `public/fonts`,
décommenter le `@font-face` de `_base.scss`. **Jamais de CDN.**

---

## 3. Espacement, rayons, ombres

**Espacement base 4 px.** `1: 4px` · `2: 8px` · `3: 16px` · `4: 24px` ·
`5: 32px` · `6: 48px` · `7: 64px`. On s'y tient : aucune valeur arbitraire.

**Rayons** : `sm .375rem` · `base .5rem` (boutons, champs) · `lg .75rem`
(cartes) · `xl 1rem`. Pilule réservée aux badges.

**Ombres** : légères uniquement. `$box-shadow-sm` par défaut sur les cartes,
`$box-shadow-lg` réservé aux éléments flottants. Aucun dégradé
(`$enable-gradients: false`).

---

## 4. Composants disponibles

### Formulaire

```blade
<x-input name="email" type="email" label="Adresse e-mail" :required="true"
         placeholder="vous@exemple.sn" autocomplete="username" />
<x-input name="ville" label="Ville" :optional="true" help="Affichée sur votre profil." />
<x-password name="password" autocomplete="new-password" />
<x-textarea name="bio" label="Présentation" :rows="4" :maxlength="500" />
<x-select name="plan" label="Formule" :options="['mensuel' => 'Mensuel']" placeholder="Choisir" />
<x-checkbox name="cgv" label="J'accepte les conditions générales" :required="true" />
<x-checkbox name="alertes" label="Recevoir les actualités" :switch="true" />
<x-radio name="paiement" label="Moyen de paiement" :options="['wave' => 'Wave']" />
```

Tous acceptent `errorBag` pour les formulaires à sacs multiples
(`errorBag="updatePassword"`).

### Action

```blade
<x-button>Enregistrer</x-button>
<x-button variant="outline-secondary" size="sm">Annuler</x-button>
<x-button variant="danger" :disabled="true">Supprimer</x-button>
<x-button :href="route('login')" :block="true">Se connecter</x-button>
```

Variantes : `primary` · `secondary` · `outline-primary` · `outline-secondary` ·
`danger` · `outline-danger` · `link`. Tailles : `sm` · `lg`.

### Structure et retour

```blade
<x-card title="Mon profil" subtitle="Vos informations">
    <x-slot name="actions"><x-badge status="active" /></x-slot>
    ...
    <x-slot name="footer"><x-button>Enregistrer</x-button></x-slot>
</x-card>

<x-alert type="success">Profil publié.</x-alert>
<x-alert type="danger" title="Paiement refusé" :dismissible="false">Vérifiez votre solde.</x-alert>

<x-badge status="active" />       {{-- active trial expired pending failed
                                      refunded published draft suspended --}}
<x-stat label="Vues" value="1 248" trend="up" trend-value="+12 %" hint="30 jours" />

<x-empty-state icon="profile" title="Aucun profil" message="...">
    <x-slot name="action"><x-button>Créer</x-button></x-slot>
</x-empty-state>

<x-breadcrumb :items="[['label' => 'Accueil', 'url' => '#'], ['label' => 'Profil']]" />
<x-pagination :paginator="$profils" />

<x-modal id="suppression" title="Confirmer">
    ...
    <x-slot name="footer">...</x-slot>
</x-modal>

<x-flash />   {{-- messages de session, déjà inclus dans tous les layouts --}}
```

---

## 5. Layouts

| Layout | Usage | Particularité |
|---|---|---|
| `<x-public-layout>` | Site vitrine | Navbar + CTA, footer complet (légal, WhatsApp) |
| `<x-auth-layout>` | Connexion, inscription, mot de passe | Centré, épuré, sans navigation |
| `<x-app-layout>` | Espace client connecté | Menu latéral (offcanvas mobile), bandeau abonnement, bloc support |
| `<x-admin-layout>` | Back-office | En-tête **sombre** volontairement distinct |
| `<x-public-profile-layout>` | Profil public (scan QR) | Le plus léger : ni navbar ni footer, pas de JS |

Chacun accepte `title` (et `description` pour le référencement).

```blade
<x-app-layout title="Tableau de bord">
    <x-slot name="header"><h1 class="h4 fw-bold mb-0">Tableau de bord</h1></x-slot>
    ...
</x-app-layout>
```

---

## 6. Règles pour toute nouvelle page

1. Choisir le layout adapté — ne jamais recréer un gabarit HTML complet.
2. Utiliser les composants existants. S'il en manque un, **l'ajouter à la
   bibliothèque** plutôt que d'écrire du markup local.
3. Mobile first : concevoir à 360 px, enrichir ensuite (`col-12 col-lg-…`).
4. Un `<h1>` unique par page, hiérarchie de titres sans saut de niveau.
5. Tout texte en français, dans `lang/fr` — aucune chaîne en dur dans les vues.
6. Toute liste est paginée (`<x-pagination>`), toute relation affichée est
   *eager loaded*.
7. Montants en FCFA entiers, format « 25 000 FCFA ». Téléphones affichés via
   l'accesseur `formatted_phone` (« +221 77 383 13 64 »).
8. Vérifier le rendu sur `/design-system` avant de considérer l'écran terminé.

---

## 5 bis. COMPTE ≠ PROFIL — distinction permanente

Deux notions distinctes, jamais confondues. C'est la règle de vocabulaire la
plus importante du produit : la confondre rend le parcours incompréhensible.

| | **COMPTE** | **PROFIL** |
|---|---|---|
| Table | `users` | `profiles` |
| Contenu | Nom, e-mail, téléphone, mot de passe | Prénom, nom affiché, fonction, entreprise, photo, coordonnées, réseaux, modèle, couleur |
| Quand | Créé **en premier**, avant connexion | Créé **après** connexion |
| Contrôleur | `AccountController` | `Profile\ProfileWizardController` |
| Routes | `register`, `login`, `compte.*` | `profil.creation.*`, `profil.apercu` |
| Vues | `auth/`, `account/` | `profile/` |

**Vocabulaire autorisé pour le COMPTE**
créer un compte · inscription · s'inscrire · connexion · se connecter · mon compte

**Vocabulaire autorisé pour le PROFIL**
créer mon profil · mon profil professionnel · modifier mon profil · publier mon profil

**Règles**

- Un compte peut exister **sans** profil — c'est l'état normal après confirmation
  de l'adresse e-mail.
- Un profil ne peut **jamais** exister sans compte.
- Le mot « profil » n'apparaît pour la première fois **qu'au dashboard**, sur le
  bouton « Créer mon profil ». Ni sur la landing, ni dans l'inscription, ni dans
  les e-mails de confirmation.
- Le formulaire d'inscription ne demande **aucune** information professionnelle
  (ni fonction, ni entreprise, ni photo) et n'écrit que dans `users` et
  `pending_registrations`.
- Tout appel à l'action de la landing mène à l'**inscription** : il emploie donc
  le vocabulaire du compte (« Créer un compte », « Commencer gratuitement »).

---

## 5 ter. JavaScript — règle permanente

**Le JavaScript améliore, il ne porte jamais.** Toute fonctionnalité doit
rester utilisable si le script ne se charge pas. Le serveur reste la source de
vérité : validation, autorisation, état, navigation.

### Autorisé

Animations au défilement · copie dans le presse-papiers · aperçu d'image avant
envoi · glisser-déposer de fichier · réordonnancement des liens sociaux ·
compteurs de caractères · aperçu en direct des couleurs et modèles ·
confirmations avant suppression · composants Bootstrap via `data-bs-*`.

### Interdit

Vue, React, Inertia, Livewire ou tout framework de rendu client · validation
uniquement côté client · rendu de contenu essentiel en JavaScript · navigation
du wizard en JavaScript (il reste multi-pages avec persistance en session) ·
appels API pour ce qu'un formulaire serveur sait faire.

### Mise en œuvre

- JavaScript natif, aucune librairie hors Bootstrap.
- `resources/js/modules/`, **un module par fonctionnalité**, compilé par Vite.
  Jamais de script inline.
- Chaque module **sort immédiatement** si son élément déclencheur est absent
  de la page.
- Chargement en `defer` (comportement par défaut des modules Vite).
- Toute action modifiant des données passe par un **formulaire POST avec
  `@csrf`** — jamais `fetch` sans jeton.
- Aucune donnée sensible exposée côté client.

### Dégradation — à documenter pour chaque module

Chaque fichier de `resources/js/modules/` porte en commentaire ce qui se passe
sans JavaScript. Si la réponse est « ça ne marche pas », la fonctionnalité est
mal conçue et doit être repensée avant d'être écrite.

**Règle structurante pour l'apparition au défilement** : c'est le module qui
ajoute `.is-hidden` au démarrage. Le CSS laisse les éléments **visibles par
défaut** — si le script échoue, le contenu reste lisible. Ne jamais masquer en
CSS ce que seul le JavaScript peut révéler.

---

## 5 quater. Lisibilité au repos — règle permanente

**Aucun élément d'interface ne doit dépendre d'une interaction pour être
lisible. Le survol enrichit, il ne révèle jamais.**

Concrètement, pour chaque composant :

- La **couleur du texte est identique dans les cinq états** — repos, survol,
  focus, actif, visité. Au survol, seul le **fond** change.
- Ne jamais définir une couleur uniquement dans `:hover`.
- Vérifier le rendu **au repos** avant de considérer un composant terminé.

### Le piège de spécificité à connaître

Une pseudo-classe compte comme une classe. `a:link` a une spécificité de
**(0,1,1)**, supérieure à `.btn-dark` **(0,1,0)**. Une règle globale du type
`a:link { color: inherit }` écrase donc la couleur d'un bouton **au repos**,
alors que `.btn-dark:hover` (0,2,0) reprend le dessus au survol : le texte
n'apparaît qu'au survol.

**Ne jamais poser `color` sur `a:link` ou `a:visited`.** La décoration
(`text-decoration`) est sûre, la couleur ne l'est pas.

### Contraste

Tout couple texte/fond atteint **4,5:1 minimum** (WCAG AA). Les éléments
purement graphiques — icônes, bordures, traits décoratifs — relèvent du seuil
de 3:1.

Deux couples ont échoué à l'audit et ont été corrigés : blanc sur `$vert-accent`
(3,37:1 → fond passé en `$vert-fonce`, 12,51:1) et le bandeau des métiers à
`opacity:.45` (1,86:1 → opacité retirée, l'estompage vient du masque latéral,
5,03:1).

### Vérification

La page `/design-system` affiche chaque variante de bouton dans ses **cinq
états côte à côte**. Un texte invisible au repos y saute aux yeux
immédiatement.

---

## 6 bis. Liens — règle permanente

**Aucun lien n'est souligné, nulle part dans l'application.**

La distinction visuelle d'un lien passe par la **couleur**, jamais par le trait.
Au survol, la teinte change (assombrissement, ou opacité sur fond sombre).

La règle est posée globalement dans `_base.scss` et couvre tous les états —
repos, visité, survol, actif, focus — ainsi que les composants Bootstrap qui
soulignent par défaut (`.btn-link`, `.nav-link`, `.dropdown-item`,
`.navbar-brand`, `.page-link`, `.alert-link`). Toute page construite ensuite
en hérite automatiquement : **ne jamais ajouter `text-decoration` dans une vue.**

**Exception unique — le focus clavier.** Les liens n'étant plus soulignés, le
contour de focus devient le seul repère pour la navigation au clavier. Il est
donc *renforcé* (2 px vert, décalage 3 px, coins arrondis), jamais supprimé, et
passe en blanc sur les fonds sombres. Ne pas y toucher.

---

## 7. Interdits

- **Classe Bootstrap brute là où un composant existe** — jamais
  `<button class="btn btn-primary">`, toujours `<x-button>`.
- **Style en ligne** (`style="..."`) — sauf dans les gabarits d'e-mail, où le
  CSS en ligne est imposé par les clients de messagerie.
- **`text-decoration: underline`** sous quelque forme que ce soit — voir 6 bis.
- **URL en dur** dans un `href` — toujours `route()` ou `url()`.
- **`href="#"` ou bouton sans action** — un lien mort est un bug, pas un
  espace réservé.
- **Confondre COMPTE et PROFIL** dans un libellé, une route, un contrôleur ou
  une vue — voir 5 bis.
- **Couleur en dur** (`#0B5D3B`, `rgb(...)`) hors de `_variables.scss`.
- **JavaScript qui porte une fonctionnalité** — voir 5 ter. Le script
  améliore ; sans lui, tout doit rester utilisable.
- **Police ou image chargée depuis un CDN.**
- **Le mot « Laravel »** dans l'interface : le nom du produit vient de
  `config('app.name')`.
- **Dégradés criards, ombres lourdes, icônes décoratives** sans fonction.

---

## 8. Accessibilité et performance

- Contraste **AA** vérifié sur tous les couples texte/fond de la palette.
- Zones tactiles **≥ 44 px** (`$min-touch-target`) sur boutons, champs et liens
  de pagination.
- Chaque champ a un `<label>` associé ; les erreurs sont reliées par
  `aria-describedby` et signalées par `aria-invalid`.
- Focus visible jamais supprimé (`:focus-visible`, contour vert 2 px).
- Lien d'évitement « Aller au contenu » dans chaque layout.
- `prefers-reduced-motion` respecté.
- **Poids surveillé** : zéro police distante, zéro image externe, SVG en ligne.
  Le profil public ne charge même pas le JavaScript.

Mesurer le CSS compilé :

```bash
npm run build
ls -lh public/build/assets/
```

---

## 9. Commandes

```bash
npm run dev      # compilation à chaud pendant le développement
npm run build    # compilation optimisée (production)
php artisan view:clear
```

---

## 5 quinquies. Parcours de création — règles permanentes

**Le chrono est un critère d'acceptation, pas un souhait.**
De l'arrivée sur le tableau de bord à l'écran d'aperçu : moins de 3 minutes,
rechargements de page compris. Toute demande d'ajout de champ se juge contre
ce chrono, jamais contre l'exhaustivité des données.

**Quatre champs obligatoires, et c'est tout.**

| Étape | Obligatoire | Optionnel |
|---|---|---|
| 1 — Qui êtes-vous | prénom, nom, fonction | entreprise, photo |
| 2 — Comment vous joindre | téléphone | WhatsApp, e-mail public, site, adresse, réseaux |
| 3 — Votre style | *(rien : tout est pré-sélectionné)* | modèle, couleur |

L'étape 3 ne demande aucune saisie : ses deux champs arrivent pré-cochés.
Elle existe pour rassurer sur le rendu, pas pour faire travailler. Un
utilisateur pressé la franchit d'un clic.

**La navigation est serveur, toujours.**
Chaque « Continuer » est un POST suivi d'une redirection. Un rafraîchissement
ne resoumet jamais. Le retour arrière du navigateur ne casse rien. Aucune
étape n'est atteignable si la précédente n'est pas franchie, et le middleware
renvoie sur la *première étape manquante*, jamais sur l'accueil.

**Rien ne se perd.**
Les données vivent en session jusqu'à l'écriture finale, en une seule
transaction. L'utilisateur qui ferme son navigateur retrouve son avancement.
Aucune ligne incomplète n'atteint jamais la table `profiles`.

**Le parcours de création est aussi le parcours d'édition.**
`profile.edit` recharge le profil en session et renvoie sur l'étape 1. Trois
écrans de plus pour les mêmes champs auraient doublé la surface de bugs sans
rien apporter. Le slug ne change jamais lors d'une modification : un lien déjà
partagé reste valable.

**Le JavaScript ne porte rien.**

| Fonction | Sans JavaScript |
|---|---|
| Aperçu de la photo | Le champ fichier fonctionne, la photo part au « Continuer » |
| Ajout d'un réseau | Le bouton reste un submit, le serveur renvoie une ligne de plus |
| Retrait d'un réseau | Vider les deux champs suffit : une ligne incomplète est ignorée |
| Aperçu de la couleur | La teinte cochée est déjà appliquée par une variable CSS |
| Menu latéral mobile | Offcanvas Bootstrap natif, piloté par `data-bs-*` |

**Contraste des champs de saisie.**
`$bordure-forte` (14 % de noir, 1,33:1) convient à un trait décoratif mais pas
à la délimitation d'un composant de saisie, que WCAG 1.4.11 exige à 3:1. Les
champs, la zone de dépôt et les cartes de modèle utilisent `$bordure-champ`
(48 %, 3,16:1 sur blanc). Un marque-place n'est jamais atténué : il se lit.
Une entrée de menu indisponible garde sa couleur pleine — ce qui la distingue,
c'est l'absence de réaction au survol, pas un texte délavé.
