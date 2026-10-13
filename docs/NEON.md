# Passer la base chez Neon (PostgreSQL)

Marche à suivre, 10 minutes. Rien à payer.

---

## Pourquoi

Aiven gratuit **s'éteint** après une période sans activité et attend qu'un
humain le rallume dans sa console. Tant que personne ne le fait, le conteneur
refuse de démarrer : c'est la panne du 12 octobre 2026.

Neon gratuit **s'endort** après cinq minutes sans requête, et **se réveille
seul** à la connexion suivante. Après une longue absence, le premier visiteur
attend le réveil de Render, puis tout fonctionne. Aucun geste manuel.

Le code tourne indifféremment sur MySQL et sur PostgreSQL. Les écarts entre
les deux moteurs, et la façon dont chacun est neutralisé, sont décrits et
testés dans `tests/Feature/PortabiliteDesBasesTest.php`.

---

## Les étapes

### 1 · Créer le projet Neon

Sur **https://console.neon.tech**, bouton **« New project »** :

| Champ | Valeur |
|---|---|
| Name | `qrid` |
| Postgres version | **17** |
| Region | **AWS Europe Central 1 (Frankfurt)** — la même que Render |

### 2 · Copier l'adresse de connexion

Sur le tableau de bord du projet, bouton **« Connect »** :

- **désactiver « Connection pooling »** — on veut l'adresse DIRECTE ;
- copier la chaîne entière, qui commence par `postgresql://`.

### 3 · La donner à Render — c'est la bascule

Le code compatible PostgreSQL est déjà en production, et il tourne encore
sur Aiven : rien ne bascule tant que cette étape n'est pas faite.

Service `qrid` → **Environment**, les trois variables **dans le même
enregistrement** :

| Variable | Valeur |
|---|---|
| `DB_URL` | la chaîne copiée à l'étape 2 |
| `DB_CONNECTION` | `pgsql` |
| `DB_SSLMODE` | `require` |

Les anciennes variables `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`,
`DB_PASSWORD` et `DB_SSL_CA_CONTENT` peuvent rester : `DB_URL` l'emporte.

### 4 · Laisser Render redémarrer

L'enregistrement des variables relance le service. Au démarrage, le
conteneur crée les tables dans Neon, puis les données de référence :
formules, modèles de carte, compte administrateur, comptes vitrine.

Si le démarrage échoue, Render garde la version précédente en ligne — sur
Aiven. Les journaux du service disent pourquoi.

### 5 · Vérifier

- se connecter avec le compte administrateur ;
- ouvrir **Administration → État système** : la ligne « Connexions » doit
  afficher un plafond lu chez Neon.

---

## Les heures de calcul

L'offre gratuite donne **100 heures de calcul par mois**. Neon ne les compte
que lorsque la base est éveillée, et l'endort après **cinq minutes sans
aucune requête**.

L'application est réglée pour la laisser dormir quand personne ne s'en sert :

- le planificateur et son battement interrogent la base **toutes les trente
  minutes** (`Planificateur::CADENCE_MINUTES`) ;
- le worker de file **dort** tant que `QUEUE_CONNECTION` vaut `sync` — il
  interrogeait la base toutes les dix secondes ;
- la surveillance de la file ne tourne que si la file existe.

Un conteneur éveillé en permanence, sans un seul visiteur, tient donc la base
éveillée cinq minutes sur trente : environ **30 heures par mois**. Le reste
suit les visites réelles.

**Le ping de `REVEIL-A-FROID.md` est donc compatible avec l'offre gratuite** :
il supprime l'attente de 50 secondes du premier visiteur sans épuiser Neon.

À surveiller sur la console Neon (onglet *Usage*) pendant le premier mois.

**Le jour où `QUEUE_CONNECTION` passe à `database`**, le worker interroge de
nouveau la base toutes les dix secondes : elle ne dort plus tant que le
conteneur est éveillé. C'est le moment de passer Neon sur l'offre payante.

---

## Revenir à MySQL

Remettre `DB_CONNECTION=mysql`, retirer `DB_URL`, et renseigner `DB_HOST` et
ses voisines. Aucune ligne de code à changer : le code est resté compatible.

---

## Les sauvegardes

Neon garde **six heures** d'historique sur l'offre gratuite : c'est un filet
contre une fausse manœuvre, pas une sauvegarde. `php artisan app:sauvegarder`
produit un dump avec `pg_dump`, présent dans l'image. Tant que
`FILESYSTEM_DISK` vaut `local`, ce dump vit sur le disque éphémère du
conteneur — le problème reste ouvert, voir `app/Console/Commands/Sauvegarder.php`.
