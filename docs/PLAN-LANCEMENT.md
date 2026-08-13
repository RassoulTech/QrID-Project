# Plan de finalisation — lancement au 20 août 2026

> Reçu le 11 août 2026. Neuf jours.
> Le déploiement (GitHub, Docker, Render, Aiven) est traité et **sort de ce
> plan** — voir [DEPLOIEMENT.md](DEPLOIEMENT.md).
> Périmètre gelé : aucune fonctionnalité nouvelle hors de ce document.
> Toute idée qui surgit est notée dans [../V2.md](../V2.md).

---

## ÉTAT DES LIEUX — au 12 août

Mesuré dans le dépôt, pas estimé.

| Bloc | Avancement | Verdict |
|---|---|---|
| 1 — Notifications | **✅ livré le 13 août** | reste la vérification en boîte réelle |
| 2 — Discord | **✅ livré le 13 août** | reste le webhook à créer, puis un cron |
| 3 — Durcissement | ~40 % | ⛔ **INTENABLE en l'état** — voir le risque n° 1 |
| 4 — Finitions | ~60 % | tenable, mais dépend de vous pour trois points |

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
| **Pages légales** | ⛔ **gabarit vide** |
| Aperçu de partage WhatsApp | ⚠️ balises `og:` présentes, jamais vérifiées |

**Les pages légales sont un gabarit sans contenu.** Le fichier porte
lui-même la mention :

> *Contenu à compléter avec un juriste avant l'ouverture commerciale : ces
> mentions sont obligatoires pour un service payant.*

CGU, confidentialité et mentions légales sont **obligatoires** pour vendre.
Ce n'est pas du développement : il faut un texte. **À produire de votre
côté.**

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

---

## CE QUI DÉPEND DE VOUS, ET NON DE MOI

Par ordre d'urgence :

1. **Décision sur le paiement** (risque n° 1) — bloque le bloc 3
2. **Budget Render** pour un cron et/ou un worker (risque n° 2) — bloque les
   blocs 1 et 2
3. **Stockage objet** pour les photos (risque n° 3)
4. **Textes légaux** — CGU, confidentialité, mentions
5. **Scan du QR Code** sur deux téléphones et vérification à 375px
6. **Réception des 11 e-mails** dans une vraie boîte — suppose une messagerie
   de production qui fonctionne, donc un domaine vérifié chez Resend

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
