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

### 1. Ajouter un worker — *quand les e-mails ralentissent l'inscription*

Aujourd'hui `QUEUE_CONNECTION=sync` : tout part dans la requête HTTP. C'est un
choix documenté dans `App\Mail\BaseMailable`, et il est juste tant qu'aucun worker
ne tourne — un e-mail de confirmation qui reste en file signifie « personne ne peut
créer de compte ».

**Quand** : dès qu'un envoi SMTP dépasse régulièrement 1 s, ou avant d'ajouter le
moindre appel réseau au parcours d'inscription.

**Comment** : un service `worker` sur Render lançant
`php artisan queue:work --queue=mail,default,low`, puis basculer
`QUEUE_CONNECTION` sur `database`. **Dans cet ordre.** L'inverse arrête tous les
e-mails sans aucun message d'erreur.

**Coût** : un service Render supplémentaire, ~7 $/mois au plan Starter.

Un worker suffit jusqu'à quelques centaines d'envois par heure. Au-delà, en ajouter
un second et répartir les files.

### 2. Augmenter le plan de base — *quand les connexions saturent*

Aiven plafonne les connexions simultanées selon le plan. **Cette limite n'a pas été
relevée** — elle est à lire dans la console et à noter ici.

Chaque processus PHP-FPM ouvre une connexion. Avec le `pm.max_children` actuel,
le plafond côté application est connu ; c'est côté Aiven qu'il manque le chiffre.

**Quand** : dès qu'apparaissent des `SQLSTATE[HY000] [1040] Too many connections`.

### 3. Passer à Redis — *quand le cache en base devient un goulot*

`CACHE_STORE=database` en production. Chaque lecture de cache est une requête SQL :
cela fonctionne, mais le cache concurrence alors la charge qu'il devait soulager.

**Quand** : quand les requêtes sur la table `cache` dépassent 10 % du total.

**Comment** : `CACHE_STORE=redis` et `QUEUE_CONNECTION=redis`. Aucun code à
changer — c'est tout l'intérêt d'être passé par les façades.

**Coût** : ~10 $/mois pour un Key Value Render.

### 4. Externaliser le stockage — *avant toute mise en service réelle*

`FILESYSTEM_DISK=local` sur un disque **éphémère** : les images de couverture
disparaissent à chaque déploiement. Le contournement en place — les octets sont
aussi écrits en base, le disque n'est qu'un cache — fonctionne mais ne passe pas
l'échelle : ces octets voyagent dans chaque requête qui charge un profil.

**Quand** : avant les premiers clients payants, ou dès que la moyenne des images
dépasse 200 Ko.

**Comment** : `FILESYSTEM_DISK=s3` vers Cloudflare R2. **Coût** : ~1 $/mois.

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
| File en attente | 500 tâches |
| Tâches échouées | 0 |
| Stockage public | écriture et lecture |

La commande rend un code de sortie non nul dès qu'un seuil est franchi : une
surveillance externe peut s'y brancher sans lire la sortie.

**Les requêtes lentes se journalisent seules** au-delà de 300 ms
(`SEUIL_REQUETE_LENTE_MS`), avec leur durée et la route qui les a déclenchées.
C'est ce qui manquait : la requête à 28,6 s existait depuis des mois et rien ne
l'avait signalée, parce que rien ne regardait.

---

## Ce qui reste ouvert

- **Limite de connexions Aiven** : non relevée.
- **Webhook de paiement** : il n'y en a pas — le paiement passe par WhatsApp.
  L'idempotence n'a pas d'objet tant qu'aucune passerelle n'appelle en retour.
- **Worker** : non déployé. C'est une dépense, donc une décision.
- **Sauvegardes** : voir `ENVIRONNEMENTS.md`. Aucune restauration testée.
