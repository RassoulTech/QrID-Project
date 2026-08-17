# Plan de finalisation — lancement au 20 août 2026

> Reçu le 11 août 2026. Neuf jours.
> Le déploiement (GitHub, Docker, Render, Aiven) est traité et **sort de ce
> plan** — voir [DEPLOIEMENT.md](DEPLOIEMENT.md).
> Périmètre gelé : aucune fonctionnalité nouvelle hors de ce document.
> Toute idée qui surgit est notée dans [../V2.md](../V2.md).

---

## ÉTAT DES LIEUX — au 17 août

Mesuré dans le dépôt, pas estimé.

| Bloc | Avancement | Verdict |
|---|---|---|
| 1 — Notifications | **✅ livré le 13 août** | reste la vérification en boîte réelle |
| 2 — Discord | **✅ livré le 13 août** | reste le webhook à créer, puis un cron |
| 3 — Durcissement | ~40 % | ⛔ **INTENABLE en l'état** — voir le risque n° 1 |
| 4 — Finitions | ~85 % | **le code est fait** ; ne restent que les points qui demandent un œil et un téléphone |

**Trois jours restants.** Ce qui manque au bloc 4 ne s'écrit plus, cela se
constate : un QR scanné, un rendu regardé à 375 px, onze e-mails reçus. Le seul
obstacle qui demande encore du code est le risque n° 1, et il attend une
décision, pas des heures de travail.

---

## ⛔ RISQUE N° 1 — Il n'existe aucune passerelle de paiement

**C'est le point à trancher avant tout le reste.**

`app/Services/Payment/` ne contient que `FakeGateway`, et celle-ci porte une
garde explicite :

```php
if (! app()->environment(['local', 'testing'])) {
    throw new RuntimeException(
        'FakeGateway est interdite hors développement : elle validerait '
        .'des paiements sans encaissement.'
    );
}
```

Render tourne en `APP_ENV=production`. **Aujourd'hui, en ligne, un clic sur
« Payer » lève une exception.** La garde est correcte — elle empêche
d'accorder des abonnements sans encaissement — mais elle signifie que le
produit ne peut pas prendre d'argent.

Le bloc 3 demande :

> Parcours testé en FakeGateway, puis avec les clés réelles sur un paiement
> de 2 500 FCFA effectué depuis un vrai téléphone

Ce n'est pas une tâche de développement. Cela suppose :

1. un contrat marchand avec un agrégateur (PayDunya, CinetPay, InTouch) ou
   directement Wave Business ;
2. la validation de votre dossier par cet agrégateur — **délai hors de notre
   contrôle, souvent plusieurs jours à plusieurs semaines** ;
3. des clés de test, puis des clés de production.

`PaymentGateway` est déjà une interface propre : le jour où les clés
existent, l'implémentation représente environ une journée. **Mais je ne peux
pas la commencer sans documentation d'API ni identifiants.**

### Les trois issues possibles

| Issue | Conséquence |
|---|---|
| **A.** Ouvrir sans paiement | l'essai gratuit fonctionne ; aucun encaissement possible |
| **B.** Décaler le lancement | on attend la validation de l'agrégateur |
| **C.** Paiement hors ligne | le client paie par Wave manuellement, l'administration active à la main |

**L'issue C mérite d'être regardée.** L'espace admin sait déjà prolonger un
abonnement avec motif et journalisation. Un lancement au 20 août avec
encaissement manuel est réaliste ; un lancement avec paiement automatique ne
dépend pas de nous.

### ✅ 17 août — l'écran de paiement ne tombe plus, il explique

Constaté dans les journaux de production : **deux `POST /abonnement/paiement`,
deux erreurs 500**, le 12 puis le 17 août. Le client recevait :

> *Une erreur est survenue. Le problème vient de nous, pas de vous. Notre
> équipe en a été informée.*

Trois mensonges en trois lignes. Rien n'était en panne, personne n'était
informé, et c'était le seul écran du produit où le client sortait son argent.

`PaymentGateway` porte désormais `estDisponible()`. L'écran interroge la
passerelle **avant** d'afficher un bouton : sans encaissement possible, le
choix du moyen disparaît et un panneau propose l'encaissement à la main par
WhatsApp — c'est-à-dire l'issue C, rendue praticable pour le client.

**Le défaut invisible derrière celui-là :** `start()` écrivait le `Payment` en
`pending` AVANT que `initiate()` ne lève. Chaque clic laissait un paiement
fantôme, et ce sont eux qui alimentent l'alerte « en attente depuis plus d'une
heure » du récapitulatif du soir. L'absence de contrat opérateur se serait
signalée, tous les soirs, comme une panne d'encaissement.

La garde de `FakeGateway` n'a pas bougé : elle refuse toujours d'encaisser hors
développement. 8 tests, dont un qui vérifie qu'aucun paiement fantôme n'est
écrit.

**Décision attendue de votre part.**

---

## ⛔ RISQUE N° 2 — Ni file d'attente ni planificateur en production

Le bloc 1 pose comme principe :

> Tout passe par la file d'attente, jamais en synchrone dans une requête.

Le bloc 2 exige une commande planifiée à 21h00, le bloc 3 un job toutes les
dix minutes.

Or la production tourne avec `QUEUE_CONNECTION=sync`, et **aucun processus
n'exécute `queue:work` ni `schedule:run`**. Un service web Render ne fait que
répondre aux requêtes HTTP.

Cinq tâches sont déclarées et ne s'exécutent donc jamais :

```php
Schedule::command('registrations:purge')->dailyAt('03:00');
Schedule::command('queue:monitor database:mail --max=50')->everyMinute();
Schedule::command('profiles:remind')->dailyAt('09:00');        // bloc 1
Schedule::command('subscriptions:notify')->dailyAt('09:15');   // bloc 1
Schedule::command('report:daily')->dailyAt('21:00');           // bloc 2
```

**Les trois dernières sont livrées et testées depuis le 13 août.** Elles
restent muettes tant qu'aucun processus ne lance `schedule:run`. Un Cron Job
Render exécutant cette seule commande chaque minute les réveille toutes les
cinq — c'est une ligne de configuration, aucun code à écrire.

C'est désormais **le seul obstacle** entre les blocs 1 et 2 et leur mise en
service : tout le code existe, il attend un déclencheur.

**Sans un service supplémentaire chez Render — payant — les blocs 1 et 2 ne
peuvent pas fonctionner comme spécifiés.** Ce n'est pas un problème de code :
le code marchera, personne ne le déclenchera.

Deux options :

- **un Cron Job Render** exécutant `schedule:run` chaque minute : couvre le
  récapitulatif Discord, la purge, et `VerifyPendingPayments` ;
- **un Background Worker** pour `queue:work` : nécessaire au « jamais en
  synchrone ».

**Décision attendue : budget mensuel acceptable ?**

---

## ⚠️ RISQUE N° 3 — Les photos disparaissent à chaque déploiement

Hors plan, mais bloquant pour une ouverture commerciale.

`FILESYSTEM_DISK=local` : les photos de profil sont écrites sur le disque du
conteneur, effacé à chaque mise en ligne. Un client téléverse sa photo, vous
corrigez un détail le lendemain, **sa photo n'existe plus**. Sans erreur,
sans trace.

À basculer sur un stockage objet avant le 20. Compter une demi-journée.

---

## BLOC 1 — Notifications ✅ LIVRÉ LE 13 AOÛT

### E-mails client — 11 sur 11

| E-mail | Déclencheur |
|---|---|
| Confirmation d'inscription | formulaire d'inscription |
| Adresse déjà utilisée | formulaire d'inscription |
| Réinitialisation de mot de passe | mot de passe oublié |
| **Bienvenue** — essai ouvert, lien de création | `UserRegistered` |
| **Rappel de publication** — 24 h puis 72 h | `profiles:remind` |
| **Carte en ligne** — le lien à partager | `ProfilePublished` |
| **Reçu de paiement** — QR + PDF joints | `PaymentSucceeded` |
| **Paiement non abouti** | `PaymentFailed` |
| **Échéance** — J-7, J-3, J-1, jour même | `subscriptions:notify` |
| **Abonnement expiré** | `subscriptions:notify` |
| **Mot de passe modifié** — sécurité | `PasswordChanged` |

### Alertes équipe — 6 sur 6

Compte confirmé · Carte créée · Carte mise en ligne · Paiement encaissé ·
Paiement en échec · Traitement en échec.

**Une seule classe, `AdminAlertMail`, pour les six.** Elles ont rigoureusement
la même forme — titre, couples libellé/valeur, lien vers l'administration — et
ne diffèrent que par leur contenu, c'est-à-dire par un paramètre. Six classes
auraient produit six gabarits presque identiques dont on en corrigerait cinq
le jour d'un changement.

Ce qui les distingue vraiment — **l'urgence** — est porté par l'enum
`MotifAlerte` et décide du sujet (`[Action]` / `[Info]`) comme du bandeau.
Les deux motifs d'échec **ne sont pas désactivables** : un interrupteur sur
« paiement en échec » serait actionné un jour de bruit et jamais remis.

### Trois décisions structurantes prises pendant l'écriture

**1. `Courrier` — l'e-mail d'information ne peut plus casser une page.**

Deux familles, deux comportements opposés :

- le courrier qui **porte le parcours** (lien de confirmation, lien de
  réinitialisation) échoue **bruyamment** : sans lui l'utilisateur est bloqué,
  et un « c'est envoyé » qui ment coûte des jours ;
- le courrier qui **informe** (reçu, bienvenue, échéance) accompagne une action
  **déjà réussie**. Son échec est avalé pour l'utilisateur, consigné pour
  l'exploitant.

Test à l'appui : *un paiement est encaissé, l'abonnement ouvert et la carte
publiée même quand aucun e-mail ne peut partir.* Sans cette séparation, une
panne SMTP pendant un retour d'opérateur aurait annulé l'encaissement —
client débité, sans abonnement, et rien dans les données pour dire pourquoi.

**2. `RegistrationConfirmed` n'a pas été créé.** `UserRegistered` est émis
exactement au même instant. Deux événements pour un seul fait se seraient
désynchronisés au premier refactoring.

**3. Défaut sérieux trouvé et corrigé — `mail_logs.sent_at` était NOT NULL.**

Un e-mail qui n'est pas parti n'a pas de date d'envoi. Toute tentative
d'enregistrer un échec heurtait la contrainte, était rejetée, puis avalée par
le try/catch qui protège la journalisation.

**Aucune panne d'envoi n'a donc jamais été enregistrée.** L'écran « État
système » affichait une liste sans le moindre échec — donc rassurante —
pendant que les e-mails ne partaient pas. On a cherché la cause d'une panne
dans un journal que le défaut lui-même empêchait de se remplir.

### Ce qui reste, et qui ne dépend pas de moi

- [ ] **Réception des 11 e-mails dans une vraie boîte** — bloqué par la
      messagerie de production, non par le code
- [ ] **Les deux commandes planifiées ne s'exécutent pas en production** —
      risque n° 2, toujours ouvert. Elles sont écrites, testées, et
      déclenchables à la main :
      `php artisan profiles:remind --dry-run`
      `php artisan subscriptions:notify --dry-run`

### Couverture

37 tests dédiés. Les plus importants ne vérifient pas la rédaction mais la
**non-répétition** (deux exécutions d'une commande n'envoient qu'un message)
et la **containment** (une messagerie morte ne coûte ni un encaissement, ni
une inscription).

---

## BLOC 2 — Récapitulatif quotidien Discord ✅ LIVRÉ LE 13 AOÛT

### Ce qui est en place

| Élément | État |
|---|---|
| `config/notifications.php` + `DISCORD_WEBHOOK_URL` | ✅ |
| `App\Services\DiscordNotifier` — embed, 3 tentatives | ✅ |
| `App\Services\RapportQuotidien` — chiffres du jour | ✅ |
| Commande `report:daily` + `--dry-run` | ✅ |
| Planifiée à 21h00, fuseau **explicite** Africa/Dakar | ✅ |
| Comparaison avec la veille sur chaque chiffre | ✅ |
| Alertes en tête, couleur de l'embed qui change | ✅ |
| Message court les jours vides | ✅ |

### La règle qui gouverne le bloc

**Le message part tous les soirs, même quand il n'y a rien à dire.**

Un récapitulatif qui se tait les jours creux rend l'absence de message
ambiguë : on ne distingue plus « rien ne s'est passé » de « l'automatisation
est cassée ». Et c'est toujours la seconde qu'on découvre trop tard — on
s'habitue au silence, puis on constate un mois plus tard que le planificateur
ne tourne plus. Même raisonnement que le retrait du voyant rouge permanent de
GitHub Actions.

Le message d'une journée vide est donc **court** — pas de tableau de zéros —
mais il existe, et sa seule fonction est de prouver que la chaîne fonctionne.

### Trois écarts assumés par rapport au plan

**1. L'envoi n'est pas en file, il est synchrone.** Aucun worker n'exécute
`queue:work` : un message mis en file resterait dans la table `jobs` sans
jamais en sortir — exactement la panne qui a coûté plusieurs jours sur les
e-mails. Trois tentatives espacées d'une seconde remplacent la file. Le délai
est payé par une commande planifiée que personne n'attend.

**2. Le seuil d'alerte sur les paiements est d'UNE heure, pas de 24.** Ce
message part une fois par jour : à 24 h, une journée entière s'écoulerait
avant le premier signalement.

**3. `RapportQuotidien` est un service distinct d'`AdminStatsService`.**
Celui-ci raisonne en périodes de 7 à 365 jours et compare à la période
précédente de même durée ; le récapitulatif compare **un jour à la veille**,
un intervalle que l'autre ne sait pas produire. Ce qui est partagé, ce sont
les **définitions** — « un essai en cours », « un paiement réussi » — pour
qu'un même mot ne donne pas deux chiffres différents selon l'écran.

Un quatrième motif d'alerte a été ajouté au passage : **les e-mails qui ne
sont pas partis**. Il existe à cause de la panne de cette semaine, restée
invisible trois jours ; une ligne ici l'aurait montrée le premier soir.

### Ce qui reste, et qui ne dépend pas de moi

- [ ] **Créer le webhook** — Discord → Paramètres du salon → Intégrations →
      Webhooks, puis `DISCORD_WEBHOOK_URL` dans Render
- [ ] **Le cron** — risque n° 2, toujours ouvert. La commande est testée et
      déclenchable à la main : `php artisan report:daily --dry-run`

### Couverture

20 tests. Le premier vérifie qu'une base **entièrement vide** produit quand
même un message ; les autres couvrent la comparaison à la veille, les alertes,
le refus de Discord, la panne réseau, et le fait que l'URL du webhook
n'atteigne jamais un journal.

---

## BLOC 3 — Durcissement (3 jours)

### Sécurité — largement acquis

| Point | État |
|---|---|
| Middlewares et policies sur chaque route | ✅ vérifié par `AdminAccessTest` (27 routes) |
| `@csrf` sur tous les formulaires | ✅ vérifié par `BladeHygieneTest` |
| Aucun `env()` hors `config/` | ⚠️ **1 occurrence** — `MailTest.php:24` |
| Uploads contrôlés côté serveur | ✅ `image`, `mimes`, `max:2048` |
| Pages d'erreur personnalisées | ✅ 403, 404, 419, 429, 500, 503 |
| Rate limiting | ⚠️ **partiel** — voir ci-dessous |

**Rate limiting — 4 routes couvertes :** connexion, inscription, renvoi
d'e-mail, paiement. La demande est satisfaite. À revérifier : la
réinitialisation de mot de passe et la vérification manuelle de paiement
côté admin (déjà en `throttle:30,1`).

### Paiement — le point critique

**Parcours FakeGateway validé de bout en bout le 17 août**, sur le compte réel
`dionemhd1@gmail.com` monté en local : formulaire → écran de simulation →
retour opérateur → paiement `success` 2 500 FCFA par Wave → abonnement
`active` (Mensuel, échéance au 16/09/2026) → **carte en ligne**. La moitié du
bloc qui ne dépend pas d'un agrégateur est donc démontrée.

- [ ] ⛔ **Implémentation d'une passerelle réelle** — voir risque n° 1
- [ ] Webhook idempotent, signature vérifiée, traitement en job
- [ ] Job `VerifyPendingPayments` toutes les 10 minutes
- [ ] Alerte admin si un paiement reste en attente plus d'une heure

Acquis : `CheckoutService::succeed()` est **déjà idempotent** (un retour
rejoué ne double rien), le `Payment` naît en `pending` avant tout départ vers
l'opérateur, et la vérification manuelle existe côté admin avec motif et
journalisation.

**Manquant : tout ce qui touche à un opérateur réel.**

### Performance — largement acquis

Comptes de requêtes mesurés le 5 août :

```
Vue d'ensemble   26      Abonnements       8
Statistiques      7      Modèles           4
Clients           7      Paramètres        4
Fiche client      9      Journal          14
Profils           7      Paiements         7
```

Aucun N+1 : ces nombres sont fixes, ils ne croissent ni avec le nombre de
comptes ni avec celui des paiements. Un N+1 a été trouvé et corrigé sur la
liste des clients (23 → 7).

- [ ] Vérifier les index sur les colonnes filtrées et triées
- [ ] Mesurer le poids des pages publiques
- [ ] Les 26 requêtes de la vue d'ensemble sont réductibles à ~9 (agrégats
      conditionnels au lieu de 3 requêtes par carte) — **non urgent**

### Stabilité

Le test instable : **7 exécutions consécutives au vert le 12 août.** Il n'est
pas reproduit. Constaté deux fois auparavant, toujours à 288/1011 — deux
assertions de moins, donc un test qui s'interrompt en cours.

**Je ne le déclare pas corrigé.** La demande est de dix exécutions
consécutives : à refaire en fin de parcours, quand le code aura cessé de
bouger.

**Au 17 août : suite entière au vert** — 566 tests, 2106 assertions, 278
secondes. **Six exécutions consécutives au vert**, la série ayant été
interrompue par l'arrêt du processus et non par un échec. Six ne valent pas
dix : l'exigence reste ouverte, à reprendre quand le code aura cessé de bouger.
Le compte de 1011 cité
ci-dessus ne correspond à rien de mesurable aujourd'hui et aucun fichier de
test n'a jamais été supprimé du dépôt : c'était vraisemblablement un total
d'assertions, pas de tests.

---

## BLOC 4 — Finitions et recette (3 jours)

### Interface — largement acquis

| Point | État |
|---|---|
| Cohérence landing / client / admin | ✅ coque unifiée le 5 août |
| Thème sombre sur chaque page | ✅ jeton `--sur-accent` ajouté après un défaut à 1,33:1 |
| Rendu à 375px | ⚠️ **règles écrites, jamais constatées à l'œil** |
| Aucun lien mort | ✅ vérifié par test |
| États vides explicites | ✅ `x-empty-state` partout |
| Messages en français | ✅ vérifié par `VocabularyTest` |
| **Pages légales** | ✅ **rédigées, livrées le 16 août** — 17 tests |
| Aperçu de partage WhatsApp | ✅ **image générée, livré le 16 août** |
| Marques des opérateurs | ✅ **Wave et Orange Money le 17 août** ; Free Money en pastille |
| **Écran de paiement refondu** | ✅ **17 août** — la mention « structure provisoire » tombe |
| **« Enregistrer le contact »** | ✅ **livré le 17 août** — 15 tests |
| **Carte non active : page utile au scan** | ✅ **livré le 17 août** — 8 tests |
| Cohérence de `/exemple` | ⚠️ **page en Bootstrap brut**, voir ci-dessous |

### Scanner sa propre carte avant de l'activer

Le premier geste d'un client qui vient de créer sa carte est de **scanner son
propre QR Code pour voir si ça marche**. Il tombait sur une page d'erreur nue.
Rien ne lui disait que son QR Code était juste, que rien n'était cassé, et
qu'il ne restait qu'une étape.

`/p/{slug}` sert désormais une page qui nomme l'obstacle et porte le bouton qui
le lève. Trois cas, dans cet ordre — c'est la logique métier :

| Cas | Message | Bouton |
|---|---|---|
| Suspendue par l'administration | vos informations sont intactes | contacter le support |
| Sans abonnement actif | ni le lien ni le QR ne changeront | Activer ma carte |
| Brouillon, abonnement actif | **votre QR Code est juste** | Mettre ma carte en ligne |

**Une carte suspendue n'envoie jamais payer.** La suspension vient de
l'administration : l'argent n'y changerait rien, et ce serait la faute la plus
coûteuse que cette page pourrait commettre.

**LE STATUT RESTE 404, ET C'EST VOULU.** Un 200 distinguerait une carte
inactive d'un slug inexistant : on pourrait énumérer les comptes en essayant
des adresses. Le corps devient utile, le code de réponse ne dit rien de plus
qu'avant. Trois tests existent uniquement pour empêcher qu'on « corrige » ce
404.

**La page ne suppose aucune session.** Le scan se fait au téléphone, souvent
sur un navigateur où l'on n'est pas connecté : une page réservée au
propriétaire authentifié n'aurait servi que dans le cas le plus rare. Le nom
n'est affiché **qu'au propriétaire** — vérifié par un test qui compte zéro
occurrence du nom pour tout autre visiteur.

### « Enregistrer le contact » — le bouton qui était une image de bouton

Ce bouton **n'existait pas**. [README.md](../README.md) le promettait en
troisième ligne, et la maquette de téléphone de la page d'accueil le dessinait
— `phone__save`, un `<div>` au texte figé. La vraie page publique offrait
Appeler, WhatsApp, E-mail et les réseaux, et rien pour garder le contact.

C'est le geste qui **termine** le parcours : on scanne, on regarde, on garde.
Sans lui, le visiteur devrait recopier un numéro à la main — ce que personne ne
fait. La carte est vue, puis oubliée, et le scan n'aura servi à rien.

`VCardService` produit la fiche, servie par `/p/{slug}/contact.vcf`.

**Version 3.0, pas 4.0.** La 4.0 est plus propre, mais plusieurs lecteurs de
contacts Android d'avant 2020 ignorent un `VERSION:4.0` : le téléphone ouvre le
fichier et n'enregistre personne. Sur un geste dont l'échec est **silencieux**,
la compatibilité passe avant l'élégance.

**Trois pièges, tous muets :**

1. **La virgule.** Dans un vCard, elle sépare deux valeurs. Non échappée,
   « Cabinet Sall, Diop & Associés » s'enregistre amputé après « Sall » — sans
   qu'aucune erreur soit levée. C'est l'objet du test le plus important du
   fichier.
2. **L'encodage.** « Aïssatou », « Thiès » : sans jeu de caractères annoncé,
   la moitié des fiches sénégalaises ressortent abîmées.
3. **L'en-tête `attachment`.** Sans elle, Android affiche le fichier comme du
   texte brut : l'utilisateur voit `BEGIN:VCARD` et referme.

La photo est **embarquée** et non liée : un contact enregistré doit survivre à
la carte dont il vient. Son absence coûte le portrait, jamais la fiche — le cas
est réel, `FILESYSTEM_DISK=local` les efface à chaque déploiement (risque n° 3).

**La fiche applique les mêmes gardes que la page publique.** Un profil
dépublié ne doit pas laisser fuir ses coordonnées par une seconde adresse : ce
serait contourner l'abonnement en changeant d'URL.

### `/exemple` n'est pas au niveau du reste

La page ciblée par « Voir un exemple » est écrite en **Bootstrap brut** —
`btn btn-primary`, `d-grid`, `alert alert-warning` — alors que la vraie carte
utilise le système de design du produit. Elle montre des **initiales dans un
rond**, pas la carte. La coque a été unifiée le 5 août ; cette page a été
oubliée.

Elle fonctionne, et le bouton d'enregistrement y a été ajouté. Mais **c'est la
page qu'un prospect voit en premier**, et elle donne à voir un produit plus
pauvre que celui qui existe. À reprendre — ou à ne pas montrer : une
démonstration gagne à porter sur un vrai profil, à son adresse `/p/{slug}`.

### Aperçu de partage — ce qui manquait vraiment

Le plan disait « balises `og:` présentes, jamais vérifiées ». La vérification a
montré pire que prévu : **il n'y avait aucun `og:image`.**

Sans lui, WhatsApp rend un aperçu minuscule — une ligne de titre grise et rien
d'autre. Avec, il rend une grande vignette qu'on remarque dans une
conversation. L'écart n'est pas cosmétique : c'est la différence entre un lien
qu'on ouvre et un lien qu'on fait défiler, sur le geste central du produit.

`SharePreviewService` peint une image 1200 × 630 par profil : nom, fonction,
entreprise, photo quand elle existe, aux couleurs de la marque. Elle existe
**toujours**, y compris pour les cartes sans photo — c'est la raison pour
laquelle on ne se contente pas de la photo du profil, qui est carrée,
facultative, et ne porte ni le nom ni la marque.

**Trois défauts silencieux trouvés en chemin**, chacun rendant la balise
invisible sans lever la moindre erreur :

1. `PublicProfileLayout` est un composant **de classe** : tout paramètre
   absent de son constructeur est rangé dans `$attributes` et ignoré ;
2. `Storage::disk()->url()` lève dès qu'on remplace le disque — l'exception
   était avalée par le garde-fou de la page publique ;
3. les halos peints en pleine résolution laissaient des **anneaux
   concentriques** visibles.

### Pages légales — écrites le 16 août

CGU, confidentialité et mentions légales portaient jusque-là une trame à trous
et, **en clair sous les yeux des clients**, la mention « contenu à compléter ».
Les trois pages sont désormais rédigées : ce qui est vendu, à quel prix, par
quels opérateurs, quelles données sont conservées, et le droit applicable.

`LegalPagesTest` — 17 tests — interdit qu'elles redeviennent une trame : il
refuse tout aveu d'inachèvement dans le rendu et exige que chaque page porte
une date.

**Ce qui reste de votre côté : une relecture par un juriste.** Le texte est
complet et cohérent, mais je ne suis pas votre conseil ; les montants, la
raison sociale et les délais de rétractation engagent votre entreprise.

### Marques des opérateurs — 17 août

Les logos officiels de **Wave** et **Orange Money** sont déposés et servis sur
l'écran de paiement. Free Money garde sa pastille aux couleurs de sa charte
tant que son logo manque — c'est le repli prévu, pas un défaut.

Les deux fichiers reçus étaient au bon endroit et **tous les deux invisibles** :
`orange money.jpeg` portait une espace au lieu d'un souligné, et l'extension
`.jpeg` n'était pas reconnue. Aucune erreur, aucune trace, un écran de paiement
identique à la veille. Le format est maintenant accepté, et `OperatorMarkTest`
fait échouer la suite quand un fichier du dossier ne peut pas s'afficher.

Ils demandaient aussi un recadrage : le `wave.jpeg` reçu était une bannière
1200 × 630 dont l'icône n'occupait que le tiers central, et le fichier Orange
Money un verrouillage « symbole + nom » dont le nom, à 40 px, aurait fait quatre
pixels de haut — alors que le composant l'écrit déjà en toutes lettres à côté.
Seuls les symboles ont été gardés.

### Carte

- [ ] QR Code scanné depuis deux téléphones, à l'écran **et imprimé**
- [ ] PDF imprimable vérifié au bon format
- [ ] Retirer tout élément décoratif qui nuit à la lisibilité

**Je ne peux pas scanner.** Deux points restent d'ailleurs signalés depuis
juillet : le QR inversé sort de la norme ISO/IEC 18004 et peut échouer sur
certains lecteurs ; le code-barres Code 128 **ne peut physiquement pas être
scanné** à la taille d'une carte (0,062 mm par module contre 0,19 mm
nécessaires — il faudrait une carte de 86 mm de large).

### Recette finale

- [ ] Parcours client complet chronométré sur un vrai téléphone
- [ ] Parcours admin complet
- [ ] Chaque e-mail reçu et vérifié
- [ ] Récapitulatif Discord reçu et exact
- [ ] Abonnement expiré puis réactivé
- [ ] Suite de tests au vert

### ⛔ LA PRODUCTION N'A AUCUN PROFIL PUBLIÉ — rien à démontrer en ligne

Vérifié le 17 août sur `qrid-uutz.onrender.com` : `/exemple` affiche « Aucun
profil de démonstration ». La route prend le **premier profil publié**, et la
base n'en contient aucun.

Ce n'est pas un défaut. Les jeux de démonstration sont volontairement interdits
hors développement (`DatabaseSeeder`) — **jamais de faux clients en
production**, et c'est la bonne décision. Mais il en découle qu'**aucune
démonstration n'est possible en ligne aujourd'hui.**

Il faut créer et publier **un profil réel via l'interface**. C'est d'ailleurs la
répétition de la démonstration elle-même, et cela cochera du même coup le
parcours client chronométré ci-dessus.

Chaîne vérifiée en local le 17 août, sur un profil réellement publié :

| Étape | Résultat |
|---|---|
| `/p/{slug}` | 200 |
| Slug inexistant | 404, pas d'erreur 500 |
| QR généré à la publication | PNG, SVG, + variante de carte |
| Empreinte du QR | conforme à `APP_URL` — le garde-fou opère |
| Contenu encodé | l'URL publique complète (`CardTest`) |
| `og:image` 1200 × 630 | 200, 42 Ko réellement servis |
| `tel:` · WhatsApp · `mailto:` · réseaux | tous présents et bien formés |
| `contact.vcf` | 200, `text/vcard; charset=utf-8`, en pièce jointe |

**`APP_URL` est correct en production** — le serveur se sait bien sur
`qrid-uutz.onrender.com`. Le défaut du 13 août est réglé côté Render, et les QR
produits aujourd'hui encodent la bonne adresse.

---

## CE QUI DÉPEND DE VOUS, ET NON DE MOI

Par ordre d'urgence :

1. **Décision sur le paiement** (risque n° 1) — bloque le bloc 3
2. **Budget Render** pour un cron et/ou un worker (risque n° 2) — bloque les
   blocs 1 et 2
3. **Stockage objet** pour les photos (risque n° 3)
4. **Scan du QR Code** sur deux téléphones et vérification à 375px
5. **Réception des 11 e-mails** dans une vraie boîte — suppose une messagerie
   de production qui fonctionne, donc un domaine vérifié chez Resend
6. **Relecture juriste** des trois pages légales — elles sont écrites depuis le
   16 août ; ce sont vos montants et votre raison sociale qui y sont engagés
7. **Logo officiel de Free Money**, si vous l'obtenez — sans lui, sa pastille
   reste, et c'est acceptable. Le déposer dans
   `public/images/operateurs/free_money.svg` suffit, aucun code à toucher

---

## VERDICT

**Trois blocs sur quatre sont tenables en neuf jours.**

Le bloc 3 ne l'est pas tel qu'écrit : « un paiement de 2 500 FCFA depuis un
vrai téléphone » dépend d'un agrégateur qui doit valider votre dossier. Ce
délai n'est pas dans nos mains.

**Ma recommandation : lancer le 20 août avec l'essai gratuit et
l'encaissement manuel** (issue C du risque n° 1), et brancher la passerelle
dès que les clés arrivent. Le produit est utilisable, les cartes se créent et
se partagent, l'administration suit tout. Ce qui manque est un automatisme de
paiement, pas une fonctionnalité pour le client.

---

## RÈGLES DE TRAVAIL — rappel

- Un bloc à la fois, validation avant de passer au suivant
- Point quotidien : fait / bloqué / reste à faire
- Blocage de plus de deux heures : signaler plutôt que s'obstiner
- Aucune fonctionnalité nouvelle jusqu'au 20 août — les idées vont dans
  [V2.md](../V2.md)
