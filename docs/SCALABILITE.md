# Montée en charge

Tous les chiffres de ce document ont été **mesurés**, sur MySQL local, avec
1 000 profils synthétiques. Ils donnent des ordres de grandeur, pas des garanties :
Aiven aura d'autres I/O.

---

## Ce qui a été mesuré

### Requêtes par page

Aucun N+1, à aucune échelle. Les quatorze clés étrangères sont indexées.
`profiles.slug` et `payments.provider_ref` sont uniques.

| Page | Requêtes | Temps |
|---|---|---|
| carte publique | 5 | 125 ms |
| tableau de bord | 16 | 127 ms |
| admin — clients | 6 | 58 ms |
| statistiques client | 5 | 44 ms |

*(à 1 million d'événements)*

### Le point de rupture, avant correction

`admin/statistiques` agrégeait `profile_events` en entier, trois fois :

| Événements | Temps de page |
|---|---|
| 2 000 | 45 ms |
| 100 000 | **450 ms** |
| 1 000 000 | **32 301 ms** |

Fois dix sur le volume, **fois soixante-douze sur le temps**. La dégradation cesse
d'être linéaire : la table temporaire déborde sur le disque.

`EXPLAIN` nommait la cause du pire des trois — 28,6 s à elle seule : la table
pilote était `profiles`, donc MySQL agrégeait tout l'historique de chaque profil,
construisait une table temporaire, la triait, puis n'en gardait que dix lignes.

**Corrigé** par `profile_stats_daily` : les pages lisent des agrégats journaliers,
et la table source seulement pour la journée en cours — bornée par définition.

### Volume

**169 octets par événement**, index compris.

| Clients actifs | Événements / an | Poids / an |
|---|---|---|
| 100 | 36 000 | 6 Mo |
| 1 000 | 360 000 | 61 Mo |
| 10 000 | 3 600 000 | 608 Mo |

*(hypothèse : 30 événements par carte et par mois)*

---

## Les seuils, et ce qui casse à chacun

| Seuil | Ce qui casse | Levier |
|---|---|---|
| **100 clients** | rien | — |
| **1 000 clients** | rien de bloquant. La file d'e-mails devient le point sensible : chaque envoi tient la requête HTTP | worker |
| **10 000 clients** | connexions simultanées à la base ; stockage des images | plan Aiven, stockage objet |
| **5 M événements** | `profile_events` dépasse 800 Mo | purge automatique — déjà en place |

---

## Les leviers, dans l'ordre

### 1. Le worker — *déclaré, pas encore branché*

`qrid-worker` figure dans `render.yaml`, plan Starter (~7 $/mois).
`QUEUE_CONNECTION` reste sur **`sync`** jusqu'à ce que le worker soit vu vivant :
déclarer un service ne le fait pas exister, et sa création peut échouer si aucun
moyen de paiement n'est enregistré.

**La marche à suivre, dans cet ordre** :

1. appliquer le Blueprint ; le worker est créé et démarre ;
2. `php artisan app:health` — « Attente la plus longue » doit rester basse ;
3. **alors seulement**, passer `QUEUE_CONNECTION` à `database`.

Files, dans l'ordre de priorité : **`mail`** (quelqu'un attend devant son
écran), **`default`**, **`low`** (personne n'attend). `queue:work` les vide dans
cet ordre : une seule confirmation d'inscription passe avant cent
récapitulatifs.

`--tries=3 --backoff=10,60,300` : un SMTP indisponible l'est rarement plus de
cinq minutes. Au-delà, la tâche part dans `failed_jobs`, où elle reste
relançable. `--max-time=3600` fait redémarrer le processus toutes les heures —
le remède le plus simple aux fuites mémoire d'un PHP de longue durée.

**L'ordre de déploiement n'est pas négociable** : le worker doit tourner avant
que `QUEUE_CONNECTION` passe à `database`. L'inverse met les e-mails dans la
table `jobs` sans que personne ne les en sorte, et « plus personne ne peut créer
de compte » sans aucune erreur nulle part. C'est déjà arrivé.

Le filet : `app:health` surveille **l'âge de la plus vieille tâche**, pas leur
nombre. Zéro tâche peut signifier « tout va bien » comme « rien n'arrive
jamais » ; dix minutes d'attente ne peuvent signifier qu'une chose.

Un worker suffit jusqu'à quelques centaines d'envois par heure.

### 2. Augmenter le plan de base — *quand les connexions saturent*

Aiven plafonne les connexions simultanées selon le plan. **`app:health` lit ce
plafond directement de MySQL** — plus besoin d'aller le chercher dans une console
au moment où le problème survient, et plus de valeur recopiée à la main qui
devient fausse au premier changement de plan.

Chaque processus PHP-FPM ouvre une connexion. La commande affiche le pic atteint
depuis le dernier redémarrage du serveur et alerte au-delà de 80 % du plafond —
c'est-à-dire avant que la prochaine pointe de trafic ne rende des erreurs.

**Quand** : dès qu'apparaissent des `SQLSTATE[HY000] [1040] Too many connections`.

### 3. Passer à Redis — *quand le cache en base devient un goulot*

`CACHE_STORE=database` en production. Chaque lecture de cache est une requête SQL :
cela fonctionne, mais le cache concurrence alors la charge qu'il devait soulager.

**Quand** : quand les requêtes sur la table `cache` dépassent 10 % du total.

**Comment** : `CACHE_STORE=redis` et `QUEUE_CONNECTION=redis`. Aucun code à
changer — c'est tout l'intérêt d'être passé par les façades.

**Coût** : ~10 $/mois pour un Key Value Render.

### 4. Externaliser le stockage — *préparé, en attente d'identifiants*

Les cinq variables R2 sont déclarées dans `render.yaml` en `sync: false`. Il
reste à les saisir dans l'interface de Render, **puis** à passer
`FILESYSTEM_DISK` de `local` à `s3`. Dans cet ordre : un disque `s3` sans
identifiants échoue à la première écriture, au milieu d'une création de profil.

Les images sont déjà préparées pour ce basculement :

| | Avant | Maintenant |
|---|---|---|
| Format | JPEG | **WebP** si GD sait l'écrire, JPEG sinon |
| Tailles | une (840px) | **deux** — 840px pour la carte, 240px pour les listes |

Mesuré sur une bannière 1600x900 : **34 Ko en JPEG d'origine, 2 Ko en WebP**.
La vignette est écrite au dépôt, jamais à la demande — la produire au premier
affichage ferait payer la première visite de chaque liste, et sur un disque
éphémère cette « première visite » revient à chaque déploiement.

**Coût** : environ 1 $/mois.

### 5. Un CDN devant les pages publiques — *quand une carte devient virale*

Les pages `/p/{slug}` sont les plus consultées et leur contenu ne change que
lorsque le porteur le modifie.

**Quand** : au-delà de quelques milliers de vues par jour.

**Coût** : gratuit chez Cloudflare.

---

## Métriques à surveiller au quotidien

`php artisan app:health` répond en une sortie :

| Mesure | Seuil |
|---|---|
| Latence base | 200 ms |
| `profile_events` | 5 000 000 lignes |
| Dernière agrégation | 2 jours de retard |
| Connexions | 80 % du plafond MySQL |
| File en attente | 500 tâches |
| **Attente la plus longue** | **10 min** — le signal d'un worker arrêté |
| Tâches échouées | 0 |
| Stockage public | écriture et lecture |
| Dernier envoi réussi | — |

La commande rend un code de sortie non nul dès qu'un seuil est franchi : une
surveillance externe peut s'y brancher sans lire la sortie.

**Les requêtes lentes se journalisent seules** au-delà de 300 ms
(`SEUIL_REQUETE_LENTE_MS`), avec leur durée et la route qui les a déclenchées.
C'est ce qui manquait : la requête à 28,6 s existait depuis des mois et rien ne
l'avait signalée, parce que rien ne regardait.

---

## Ce qui reste ouvert

- **Webhook de paiement** : il n'y en a toujours pas — le paiement passe par
  WhatsApp, et il n'existe aucune source de vérité automatique à interroger.
  `app:reconcilier-paiements` comble le vide : tous les matins, il signale les
  paiements `pending` de plus de deux jours. Il ne décide **rien** — marquer
  `success` sans preuve donnerait un abonnement à qui n'a pas payé, marquer
  `failed` effacerait la trace de qui a payé. Un humain tranche.

- **Test de restauration** : jamais effectué. Voir `ENVIRONNEMENTS.md`.

- **Fenêtre de restauration Aiven** : à relever dans leur console.

La **limite de connexions** n'est plus une inconnue : `app:health` la lit
directement de MySQL (`max_connections`, `Max_used_connections`) et alerte
au-delà de 80 % du plafond. Aucune valeur n'est recopiée à la main — une valeur
recopiée devient fausse au premier changement de plan.
