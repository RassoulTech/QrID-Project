# Audit de la logique applicative — 2 septembre 2026

Phase 1 de la mission « finalisation complète de la logique ».
Aucun code n'a été modifié pour produire ce document : tout ce qui suit est
mesuré dans le dépôt, pas supposé.

---

## Ce que l'audit contredit

La mission est écrite pour une application-maquette : fausses données, TODO,
compteurs simulés, fonctionnalités seulement visuelles. **Ce n'est pas cet
état-là.** Mesures :

| Recherche | Résultat |
|---|---|
| `TODO` / `FIXME` / `HACK` dans `app/`, `resources/`, `routes/`, `config/` | **0** |
| Tests | **678 passants** |
| Routes sous `throttle:` | 11 groupes, dont toute l'authentification |
| Téléphone international | règle `TelephoneInternational` + `IndicatifsPays::normaliser` |
| Table d'agrégation des statistiques | `profile_stats_daily` existe |
| Migrations d'index dédiées | 2 (`add_missing_read_indexes`, `add_scale_indexes`) |
| Point d'entrée unique des e-mails | `App\Support\Courrier` |
| Traçabilité des envois | table `mail_logs` + écran « État système » |

Une large part des 26 sections de la mission décrit un travail déjà fait.
L'écart réel est court — et il est dominé par de l'**infrastructure non
provisionnée**, pas par du code absent.

---

## P0 — le code est écrit, l'infrastructure ne l'exécute pas

Ces trois points ne se corrigent pas dans l'éditeur. Le code les attend.

### 1. Aucun planificateur ne tourne en production

`routes/console.php` déclare l'agrégation des statistiques (02h30), la purge
des inscriptions expirées (03h00), les relances (09h00) et la surveillance de
la file. **Rien ne les exécute** : un service web Render ne répond qu'aux
requêtes HTTP.

Conséquences en chaîne :
- `profile_stats_daily` reste **vide en production** ;
- `StatisticsController` lit donc les événements **bruts** à chaque affichage ;
- les inscriptions abandonnées s'accumulent sans jamais être purgées ;
- les relances écrites et testées ne partent jamais.

*Correctif : un service `cron` Render, `php artisan schedule:run`, toutes les minutes.*

### 2. `QUEUE_CONNECTION=sync`, et aucun worker

Chaque e-mail part **dans la requête HTTP**. Une inscription attend le SMTP.
`Courrier` le documente et désigne l'unique ligne à changer le jour venu.

Le `render.yaml` déclare déjà le worker et impose le bon ordre : créer le
worker, le vérifier, **puis seulement** basculer la variable. L'inverse met
les e-mails en file sans que personne ne les en sorte — panne déjà vécue sur
ce produit.

*Correctif : worker Render (plan `starter`), puis bascule.*

### 3. `FILESYSTEM_DISK=local` sur un disque éphémère

Les photos disparaissent à chaque déploiement. Le contournement en place
écrit aussi les octets en base — cela tient, mais ces octets voyagent dans
**chaque requête** qui charge un profil. C'est le premier mur à l'échelle.

*Correctif : les quatre variables Cloudflare R2, puis `FILESYSTEM_DISK=s3`.*

---

## P1 — écarts de code réels

### 4. Aucune passerelle de paiement en production
`FakeGateway` est la seule implémentation et **s'interdit elle-même** hors
développement. Le parcours dégrade proprement (`paiementDisponible` passé à la
vue, le contrôleur refuse) — mais le produit ne peut pas encaisser en ligne.
Le point de branchement est prêt, à un seul endroit : `AppServiceProvider`.

### 5. Deux chemins de lecture pour le même chiffre
`StatistiquesLecture` lit les agrégats. `StatisticsController` fait ses
propres `selectRaw` sur `profile_events`. Deux sources pour une même valeur,
dont une qui ne passe pas l'échelle. À unifier derrière le service.

### 6. Taxonomie d'événements trop étroite
Trois types seulement : `view`, `scan`, `save`. Manquent notamment le clic sur
un lien de contact, le partage, et le clic WhatsApp que la mission demande.

### 7. WhatsApp est dispersé, sans architecture
Quatre implémentations indépendantes : le trait `FormatsSenegalPhone`, le
composant `whatsapp-fab`, un lien écrit en dur dans la section contact de la
landing, une URL de support dans `config/registration.php`.
Aucun registre de gabarits, aucune variable dynamique, aucun message
contextuel, aucun suivi. **C'est le plus gros chantier de code de la mission.**

### 8. Un formulaire échappe à la règle de téléphone partagée
`ContactRequest` valide `['nullable','string','max:30']` là où les trois
autres formulaires utilisent `TelephoneInternational`.

---

## Cloisonnement — vérifié, et sain

Une policy pour seize modèles : le chiffre inquiète, la vérification rassure.
Le produit ne cloisonne pas par policy mais **par portée de requête** — les
données sont lues depuis l'utilisateur authentifié, jamais cherchées par
identifiant. On ne peut pas oublier une policy sur un modèle qu'on ne récupère
jamais par son id.

Surface IDOR réelle hors `/admin` : **deux méthodes**, `PaymentController::simulate`
et `::callback`. Les deux portent un `abort_unless($payment->user_id === $request->user()->id, 403)`.
Tout le reste des méthodes recevant un modèle est `private`.

Tout `/admin` est sous `['auth','verified','admin']`.

**Conclusion : pas de trou de cloisonnement trouvé.** À consolider par des
tests A→B explicites, pas par une réécriture.

---

## Ordre de travail proposé

1. **Provisionnement Render** — cron, worker, R2. Débloque à lui seul les statistiques réelles, les e-mails non bloquants et les photos persistantes.
2. **Unifier la lecture des statistiques** (#5) — prérequis de tout tableau de bord honnête.
3. **Architecture WhatsApp** (#7) — service, gabarits, variables, contextes, suivi.
4. **Élargir la taxonomie d'événements** (#6) — alimente #3 et les statistiques.
5. **Tests de cloisonnement A→B** — figer ce qui est déjà correct.
6. **Téléphone dans `ContactRequest`** (#8).
7. **Passerelle de paiement** (#4) — dépend d'un choix opérateur.
