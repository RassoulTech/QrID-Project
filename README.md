# QrID

Carte de visite numérique pour les professionnels sénégalais. Chaque abonné
dispose d'un profil public (`/p/{slug}`) que ses contacts ouvrent après un scan
ou un clic : coordonnées, réseaux, bouton « enregistrer le contact ».

Application Laravel 12, rendue côté serveur (Blade + Bootstrap 5), sans SPA.
Toute la navigation fonctionne sans JavaScript.

> Ce fichier avait été écrasé par le README de [Mailpit](https://mailpit.axllent.org)
> lors de l'extraction de son archive à la racine du projet. Le binaire
> `mailpit.exe` reste ignoré par Git (voir `.gitignore`).

## Prérequis

| Outil    | Version              |
| -------- | -------------------- |
| PHP      | 8.2 ou plus          |
| Composer | 2.x                  |
| Node     | 20.19+ ou 22.12+ (Vite 8) |
| MySQL    | 8.x                  |

## Installation

```bash
composer install
cp .env.example .env      # puis renseigner DB_*, MAIL_* et ADMIN_*
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
```

`php artisan migrate --seed` crée les gabarits, les formules d'abonnement et le
compte administrateur (à partir des variables `ADMIN_*`). En environnement
`local` uniquement, `DemoSeeder` ajoute des profils de démonstration — ce sont
eux qui alimentent la maquette de la page d'accueil.

## Développement

```bash
composer dev     # serveur + worker de file + Vite, dans un seul terminal
```

Sous Windows, `dev.bat` ouvre les trois processus dans des fenêtres séparées.

- Application : <http://127.0.0.1:8000>
- État système (administrateur) : <http://127.0.0.1:8000/admin/etat-systeme>
- Référence visuelle des composants : `/design-system` (locale uniquement)

Les e-mails partent réellement via Gmail SMTP — voir [docs/EMAIL.md](docs/EMAIL.md)
pour la configuration et la bascule vers un relais de production.

## Tests

```bash
composer test          # suite complète
composer auth-check    # matrice d'authentification, CSRF, rendu des routes
vendor/bin/pint        # formatage du code
```

Les tests tournent sur SQLite en mémoire (voir `phpunit.xml`) : aucune base
MySQL n'est nécessaire.

Trois garde-fous méritent d'être connus avant de toucher au code :

- `EveryRouteRendersTest` parcourt la table de routage réelle : toute route GET
  ajoutée est testée automatiquement, il n'y a rien à maintenir.
- `CsrfExpiryTest` vérifie qu'un jeton expiré ramène l'utilisateur sur son
  formulaire, saisies conservées. Il substitue `Tests\Support\EnforcedCsrfToken`
  au contrôle CSRF, qui se neutralise de lui-même sous PHPUnit.
- `VocabularyTest` interdit certaines formulations dans l'interface (la
  distinction COMPTE / PROFIL doit rester lisible partout).

## Architecture

```
app/
  Concerns/     traits partagés (formatage des numéros sénégalais)
  Exceptions/   ExpiredPageRedirector — où renvoyer un jeton CSRF expiré
  Http/         contrôleurs, middlewares, FormRequests
  Mail/         BaseMailable + e-mails du produit (tous mis en file)
  Services/     RegistrationService (double opt-in), ProfileWizardService
  Support/      HomeRedirect — écran d'accueil selon le rôle
config/
  landing.php       contenu éditorial de l'accueil (aucun texte en dur en vue)
  registration.php  double opt-in : TTL, renvois, limites, compte admin
docs/
  DESIGN.md     système visuel
  EMAIL.md      envoi, file d'attente, bascule production
deployment/
  supervisor/   configuration du worker de file en production
```

Deux notions à ne jamais confondre, dans le code comme dans l'interface :

- le **COMPTE** (`users`) — les identifiants d'accès, gérés sous `/compte` ;
- le **PROFIL** (`profiles`) — la carte de visite publique, créée par le
  parcours en trois étapes sous `/profil/creation`.

## Inscription : double opt-in

Aucun compte n'est créé avant que l'adresse ne soit prouvée. Le formulaire
dépose une `pending_registrations` et envoie un lien ; c'est le clic sur ce lien
qui crée l'utilisateur, déjà vérifié, et ouvre l'essai gratuit.

La réponse affichée est identique quel que soit l'état de l'adresse (inconnue,
déjà inscrite, demande en cours) : rien ne permet d'énumérer les comptes. Le
temps de réponse est constant pour la même raison.

## Licence

Projet privé. Aucune licence publique n'est accordée.
