# Chaîne d'envoi d'e-mails — Identité Pro

Document de référence. À relire le jour de l'achat du domaine professionnel.

---

## 1. Principe d'architecture

**Aucune adresse d'expédition n'est codée en dur dans le projet.**
L'identité de l'expéditeur est définie à un seul endroit : `config/mail.php`,
section `from`, alimentée par le `.env`.

Conséquence directe : **la bascule développement → production ne modifie que le
`.env`**. Aucun fichier PHP, aucune vue Blade, aucun Mailable n'est à toucher.

Règles respectées dans le code :

- `env()` n'est jamais appelé hors de `config/`.
- Tous les liens des e-mails sont générés par `route()` / `url()`, donc dérivés
  de `APP_URL`. Ils restent valides après mise en production.
- Tous les Mailables héritent de `App\Mail\BaseMailable`, qui porte la politique
  commune (file d'attente, 3 tentatives, backoff 10/30/60 s, timeout 30 s,
  journalisation des échecs).
- Tous les gabarits utilisent le layout partagé `resources/views/emails/layout.blade.php`
  (identité visuelle du produit) et disposent d'une **version texte brut**.

---

## 2. Configuration actuelle — développement (Gmail SMTP)

Les e-mails partent réellement, pour vérifier le rendu dans une vraie boîte.

### Obtenir un mot de passe d'application Google

1. Aller sur https://myaccount.google.com/security
2. Activer la **validation en deux étapes** (obligatoire, sinon l'option suivante
   n'apparaît pas).
3. Aller sur https://myaccount.google.com/apppasswords
4. Créer un mot de passe d'application (nom libre, ex. « Identité Pro local »).
5. Google affiche **16 caractères groupés par 4** (`abcd efgh ijkl mnop`).

**Pièges de saisie :**

- Coller **sans les espaces** : `abcdefghijklmnop`.
- Ce n'est **pas** le mot de passe du compte Google.
- Ne pas l'entourer de guillemets dans le `.env` s'il ne contient pas d'espace.
- Il n'est affiché **qu'une seule fois** : le régénérer s'il est perdu.

### Bloc `.env`

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=dionemhd1@gmail.com
MAIL_PASSWORD=abcdefghijklmnop
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=dionemhd1@gmail.com
MAIL_FROM_NAME="Identité Pro"
```

> `MAIL_FROM_ADDRESS` **doit être identique** à `MAIL_USERNAME`.
> Gmail réécrit toute autre adresse d'expédition. Le nom affiché
> (`MAIL_FROM_NAME`) reste celui du produit : le destinataire voit
> « Identité Pro », pas un nom personnel.

### Deux erreurs fréquentes

**`535-5.7.8 Username and Password not accepted`**
→ Le mot de passe d'application est faux, contient des espaces, ou la validation
en deux étapes n'est pas activée. Régénérer le mot de passe et le coller sans espaces.

**`Connection could not be established` / timeout sur le port 587**
→ Le port est bloqué par le FAI ou l'antivirus. Basculer en SSL :

```env
MAIL_PORT=465
MAIL_ENCRYPTION=ssl
```

Vérifier la connectivité : `Test-NetConnection smtp.gmail.com -Port 587` (PowerShell).

---

## 3. Alerte de volume (aucun blocage)

**Aucun filtrage de destinataire n'existe dans ce projet.** Tout e-mail part
réellement, quel que soit le domaine visé (y compris les adresses jetables).

`App\Listeners\GuardOutgoingMail` se contente de **compter** les envois par
heure en local et de journaliser un avertissement au-delà du seuil, à titre
informatif — repère utile car Gmail plafonne autour de 500 envois/jour.
Il retourne toujours `true` : l'envoi n'est jamais annulé.

```env
MAIL_HOURLY_ALERT=100
```

Historique : une ancienne version de ce listener filtrait les destinataires par
liste blanche (`MAIL_ALLOWED_DOMAINS`) et bloquait silencieusement les envois
vers les domaines non listés. Ce mécanisme a été **entièrement supprimé** ;
si vous rencontrez cette variable quelque part, elle est obsolète.

---

## 4. PROCÉDURE DE BASCULE VERS LE DOMAINE PROFESSIONNEL

### Étape 1 — Choisir le service transactionnel

**Brevo** (recommandé pour le lancement) : 300 e-mails/jour gratuits à vie,
interface en français, mise en route ~15 min.
**Postmark** : meilleure délivrabilité du marché, payant dès le départ (~15 $/mois).
**Amazon SES** : ~0,10 $/1000 e-mails, imbattable au volume, configuration technique.

Trajectoire conseillée : **Brevo au lancement**, migration vers **SES** quand le
volume dépasse quelques milliers d'envois mensuels (la migration ne coûte que
des variables `.env`).

### Étape 2 — Configurer le DNS du domaine

**Ces trois enregistrements sont bloquants. Sans eux, les e-mails de confirmation
partent en spam : l'utilisateur ne clique jamais le lien, le compte n'est jamais
créé (flux double opt-in), et aucune trace de cette inscription perdue n'existe
côté serveur.**

| Type | Nom | Valeur (exemple Brevo) | Rôle |
|---|---|---|---|
| TXT | `identitepro.sn` | `v=spf1 include:spf.brevo.com -all` | Autorise le fournisseur à envoyer au nom du domaine |
| TXT | `mail._domainkey.identitepro.sn` | `v=DKIM1; k=rsa; p=MIGfMA0...` | Signature cryptographique de chaque message |
| TXT | `_dmarc.identitepro.sn` | `v=DMARC1; p=quarantine; rua=mailto:admin@identitepro.sn` | Politique en cas d'échec SPF/DKIM |

Le sélecteur DKIM et la valeur `p=` sont fournis par le service choisi.
Commencer DMARC en `p=quarantine`, passer à `p=reject` une fois SPF et DKIM
stables (compter quelques jours de rapports `rua`).

**Vérification :**

```bash
nslookup -type=TXT identitepro.sn
nslookup -type=TXT mail._domainkey.identitepro.sn
nslookup -type=TXT _dmarc.identitepro.sn
```

Ou un outil en ligne type MXToolbox (« SPF Record Lookup », « DKIM Lookup »,
« DMARC Lookup »). La propagation DNS peut prendre jusqu'à 48 h.

### Étape 3 — Pourquoi ne pas rester sur Gmail en production

- Gmail publie une politique **DMARC en `reject`** sur `gmail.com`. Envoyer depuis
  une adresse `@gmail.com` via un SMTP tiers est donc **rejeté systématiquement**.
- Il est impossible de signer en DKIM un domaine qu'on ne possède pas.
- Le quota (~500/jour) est incompatible avec un produit commercial.
- Un expéditeur `@gmail.com` inspire moins confiance qu'un `@identitepro.sn`
  pour un service payant.

### Étape 4 — Modifier le `.env` (et rien d'autre)

```env
APP_ENV=production
APP_URL=https://identitepro.sn

MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=xxxxx
MAIL_PASSWORD=xxxxx
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@identitepro.sn
MAIL_FROM_NAME="Identité Pro"
```

Puis :

```bash
php artisan config:clear
php artisan config:cache
php artisan queue:restart
```

### Étape 5 — Vérifier que la bascule est réussie

```bash
php artisan config:show mail          # default → smtp, from → no-reply@identitepro.sn
php artisan mail:test votre@adresse.com
php artisan queue:health
```

Puis, dans l'e-mail reçu (via « Afficher l'original » dans Gmail) :

- `SPF: PASS`
- `DKIM: PASS`
- `DMARC: PASS`

Enfin, un parcours d'inscription complet de bout en bout, et une vérification du
rendu sur mobile (application Gmail Android).

---

## 5. E-mails du produit

| E-mail | Classe | Statut |
|---|---|---|
| Confirmation d'inscription | `ConfirmRegistrationMail` | livré |
| Tentative sur compte existant | `RegistrationAttemptMail` | livré |
| Réinitialisation de mot de passe | `ResetPasswordMail` | livré |
| Confirmation de paiement (+ QR Code) | à venir | étape 3 |
| Relances d'abonnement J-7 / J-3 / J-1 / J | à venir | étape 3 |
| Notifications admin | `AlertOnJobFailed`, `AlertOnQueueBusy` | livré |

Tout nouvel e-mail **doit** étendre `App\Mail\BaseMailable`, utiliser
`emails.layout`, fournir une version texte, et être envoyé via
`Mail::to(...)->queue(...)` — **jamais** `->send()` dans une requête HTTP.

---

## 6. File d'attente

`QUEUE_CONNECTION=database`, tables `jobs` et `failed_jobs` migrées.

```bash
# Local
php artisan queue:work database --queue=mail,default

# Diagnostic (jobs en attente, âge du plus ancien, échecs)
php artisan queue:health

# Production : voir deployment/supervisor/identitepro-worker.conf
```

**Après toute modification d'un Mailable : `php artisan queue:restart`.**
Le worker garde le code en mémoire.

---

## 7. Journalisation

Chaque envoi est enregistré dans la table `mail_logs` (destinataire, sujet,
type, mailer, statut, horodatage) et dans `storage/logs/mail-*.log` (90 jours).

Répondre à un client affirmant n'avoir rien reçu :

```bash
php artisan tinker --execute="dump(App\Models\MailLog::where('recipient','client@x.com')->latest()->first()?->toArray());"
```

---

## 8. Diagnostic rapide en cas de problème

```bash
php artisan config:clear
php artisan config:show mail     # le driver est-il bien « smtp » ?
php artisan mail:test moi@gmail.com   # transport OK ?
php artisan queue:health         # worker actif ? jobs bloqués ?
php artisan queue:failed         # exceptions de transport
```

Interprétation : driver `log` → rien n'est envoyé, corriger `MAIL_MAILER`.
Jobs en attente avec âge élevé → worker arrêté. Jobs dans `failed_jobs` →
lire l'exception. `mail:test` échoue mais la file est saine → problème de
transport (identifiants, port).
