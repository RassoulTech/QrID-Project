# Automatisation Make — mise en place

Deux scénarios, deux sens opposés. Ils sont indépendants : l'un peut
fonctionner sans l'autre.

---

## Avant tout : produire les secrets

```
php artisan automation:token
```

La commande imprime deux valeurs et n'enregistre rien — écrire dans `.env`
depuis une commande, c'est risquer d'écraser une valeur de production sur une
faute de frappe. Copiez-les vous-même, dans `.env` **et** dans Render.

---

## Scénario 1 — Make déclenche vos tâches

**Le problème qu'il résout.** Cinq tâches sont programmées : rappels de
publication, relances d'échéance, récapitulatif Discord, purge, surveillance
de file. Aucune ne s'exécute. Un service web ne fait que répondre aux
requêtes ; il ne regarde jamais l'heure.

### Le scénario, en trois modules

| Module | Réglage |
|---|---|
| **Schedule** | Toutes les minutes |
| **HTTP → Make a request** | voir ci-dessous |
| **Router** *(facultatif)* | alerte si la réponse n'est pas 200 |

### Le module HTTP

```
Méthode  : POST
URL      : https://qrid-uutz.onrender.com/automation/schedule
Headers  : X-Automation-Token = <AUTOMATION_SCHEDULE_TOKEN>
Body     : aucun
```

### Ce que vous verrez

La plupart des minutes, la réponse est :

```json
{ "ok": true, "duree_ms": 42, "message": "Aucune tâche due à cette minute." }
```

**C'est le fonctionnement normal.** `schedule:run` compare l'heure à ce que
déclare `routes/console.php` et ne lance que ce qui est dû. À 9h00, la réponse
mentionnera `profiles:remind` ; à 21h00, `report:daily`.

Sans cette phrase explicite, chaque minute creuse ressemblerait à une panne
dans le journal de Make. C'est pour cela qu'elle existe.

### Si vous obtenez 404

Le jeton est absent, faux, ou `AUTOMATION_SCHEDULE_TOKEN` n'est pas défini sur
Render. La route rend délibérément 404 plutôt que 401 : un 401 confirmerait
l'existence de l'adresse à qui la cherche.

### Pourquoi le jeton passe en en-tête

Une URL complète se retrouve dans les journaux du serveur, dans l'historique
du navigateur et dans les en-têtes `Referer` envoyés à des tiers. Un en-tête,
non. Le paramètre `?token=` reste accepté pour les outils qui l'exigent, mais
l'en-tête est préférable.

---

## Scénario 2 — Vos prospects arrivent dans Make

**Quand.** À chaque compte **confirmé** — c'est-à-dire après le clic sur le
lien de confirmation, ou après une connexion Google.

Jamais à la demande d'inscription : une adresse tapée dans un formulaire n'est
pas un client. Elle est parfois erronée, parfois celle de quelqu'un d'autre.
Le groupe WhatsApp est réservé aux clients.

### Le scénario

1. **Webhooks → Custom webhook** — Make vous donne une URL.
2. Collez-la dans `MAKE_WEBHOOK_URL`, dans `.env` et dans Render.
3. Confirmez un compte de test : Make capture la structure automatiquement.

### Ce que vous recevez

```json
{
  "evenement": "inscription",
  "source": "QrID",
  "environnement": "production",
  "horodatage": "2026-08-17T10:32:00+00:00",
  "donnees": {
    "id": 42,
    "nom": "Awa Ndiaye",
    "email": "awa@exemple.sn",
    "telephone": "+221770000000",
    "origine": "formulaire",
    "inscrit_le": "2026-08-17T10:31:58+00:00",
    "groupe_whatsapp": "https://chat.whatsapp.com/..."
  }
}
```

`origine` vaut `formulaire` ou `google`. Elle permet de ne pas envoyer deux
fois un message de bienvenue à quelqu'un qui en a déjà reçu un.

### Ce que vous ne recevrez jamais

Ni mot de passe, même haché. Ni identifiant Google. Ni adresse IP. Un service
d'automatisation n'en a aucun usage, et chacun serait une donnée de plus à
protéger chez un tiers. Un test le vérifie.

### Vérifier la signature — à faire

L'URL d'un webhook Make est **publique**. Sans vérification, quiconque la
découvre peut y injecter de faux prospects, qui seraient invités au groupe.

Chaque appel porte l'en-tête `X-QrID-Signature` : un HMAC-SHA256 du corps
**brut**, avec `MAKE_WEBHOOK_SECRET` comme clé.

Dans Make, ajoutez un module **Tools → Set variable** puis un **Filter** :

```
sha256(bodyRaw; MAKE_WEBHOOK_SECRET)  =  headers["x-qrid-signature"]
```

Si les deux diffèrent, arrêtez le scénario.

> La signature porte sur la chaîne **exactement** transmise. C'est un piège
> classique : signer un objet puis le ré-encoder produit deux chaînes
> différentes — accents échappés ou non — et la vérification échoue toujours.
> Utilisez le corps brut, jamais le JSON reconstruit par Make.

---

## Le groupe WhatsApp

`SUPPORT_WHATSAPP_GROUP` reçoit le lien d'invitation.

Il apparaît dans l'e-mail de bienvenue et dans l'espace client. **Jamais sur
une page publique** — quiconque l'obtient peut rejoindre le groupe. Un test le
vérifie sur l'accueil, la connexion et l'inscription.

S'il fuite : WhatsApp → Infos du groupe → Inviter via un lien → **Révoquer**.
L'ancien lien devient inopérant.

---

## Ordre de mise en route

1. `php artisan automation:token`
2. Les deux secrets dans Render, puis **Manual Deploy**
3. Scénario 1, laissé tourner cinq minutes — vérifiez les 200 dans l'historique
4. Attendez 21h00 : le récapitulatif doit arriver sur Discord **sans que vous
   lanciez quoi que ce soit**
5. Scénario 2, puis un compte de test pour capturer la structure
6. La signature en dernier, une fois que le reste fonctionne

L'étape 4 est celle qui compte : elle prouve que la chaîne entière tient sans
intervention humaine.
