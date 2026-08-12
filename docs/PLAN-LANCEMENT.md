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
| 1 — Notifications | ~25 % | **tenable, sous une condition d'infrastructure** |
| 2 — Discord | 0 % | **tenable, sous la même condition** |
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

Deux tâches sont déjà déclarées et ne s'exécutent donc jamais :

```php
Schedule::command('registrations:purge')->dailyAt('03:00');
Schedule::command('queue:monitor database:mail --max=50')->everyMinute();
```

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

## BLOC 1 — Notifications temps réel (2 jours)

### Ce qui existe déjà

| Élément | État |
|---|---|
| `BaseMailable` — identité visuelle commune | ✅ |
| Table `mail_logs` + `LogSentMail` | ✅ |
| `GuardOutgoingMail` — garde-fou d'envoi | ✅ |
| Événements Laravel + listeners (mécanique) | ✅ `UserRegistered` → `StartFreeTrial` |
| `ConfirmRegistrationMail` | ✅ |
| `AlreadyRegisteredMail` | ✅ |
| `ResetPasswordMail` | ✅ |

La fondation est saine : classe de base, journalisation, déclenchement par
événements. **Il reste le volume.**

### Ce qui manque

**E-mails client — 3 sur 9 faits**

- [ ] Compte confirmé : bienvenue + lien vers la création de profil
- [ ] Profil créé non activé : rappel à 24 h puis 72 h
- [ ] Paiement réussi : lien public + QR Code en pièce jointe + PDF
- [ ] Paiement échoué : explication + lien pour réessayer
- [ ] Carte publiée : récapitulatif + lien à partager
- [ ] Abonnement : J-7, J-3, J-1, jour d'expiration
- [ ] Abonnement expiré : profil indisponible + lien de réactivation
- [ ] Mot de passe modifié : alerte de sécurité

**E-mails administrateur — 0 sur 6**

- [ ] Compte confirmé par un nouveau client
- [ ] Profil créé
- [ ] Carte activée
- [ ] Paiement réussi, avec montant et moyen
- [ ] Paiement échoué ou en attente depuis plus d'une heure
- [ ] Job en échec définitif

**Événements à créer** — seul `UserRegistered` existe. Il faut au minimum :
`RegistrationConfirmed`, `ProfileCreated`, `ProfilePublished`,
`PaymentSucceeded`, `PaymentFailed`, `SubscriptionExpiring`,
`SubscriptionExpired`, `PasswordChanged`.

### Estimation honnête

15 e-mails, 8 événements, leurs listeners, leurs gabarits HTML et texte, plus
la vérification de chacun dans une vraie boîte. **2 jours est serré mais
faisable si l'on ne redessine rien.** Le facteur limitant sera la
vérification manuelle des 15 envois, pas l'écriture.

---

## BLOC 2 — Récapitulatif quotidien Discord (1 jour)

### Ce qui existe

Rien. Aucun `DiscordNotifier`, aucune commande `report:daily`, aucune
configuration de webhook.

### Ce qui est déjà acquis ailleurs

`AdminStatsService` calcule déjà en SQL agrégé : comptes, profils,
abonnements actifs, chiffre d'affaires, essais, paiements en attente,
répartition par moyen. **Le récapitulatif réutilise ces requêtes** au lieu
d'en écrire de nouvelles.

### À faire

- [ ] `config/notifications.php` + `DISCORD_WEBHOOK_URL` en variable
- [ ] `App\Services\DiscordNotifier` — message au format embed
- [ ] Commande `report:daily`, planifiée à 21h00 heure de Dakar
- [ ] Envoi en file, réessai, échec journalisé sans rien bloquer
- [ ] Comparaison avec la veille sur chaque chiffre
- [ ] Alerte en tête si paiement bloqué ou job en échec
- [ ] Message court même si la journée est vide

> Le dernier point est le plus important du bloc : *l'absence de message ne
> doit jamais être ambiguë entre « rien ne s'est passé » et
> « l'automatisation est cassée ».* C'est exactement le raisonnement qui a
> fait retirer le voyant rouge permanent de GitHub Actions.

**1 jour : réaliste**, à condition que le risque n° 2 soit tranché — sinon la
commande existera sans jamais s'exécuter.

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
6. **Réception des 15 e-mails** dans une vraie boîte

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
