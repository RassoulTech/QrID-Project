# Environnements

Deux bases de données, totalement indépendantes. Ce document existe pour qu'aucune
manipulation ne les confonde.

---

## Les deux bases ne se parlent jamais

| | Local | Production |
|---|---|---|
| Hébergeur | MySQL sur la machine de développement | Aiven |
| Base | `startup-tech` | définie par `DB_DATABASE` sur Render |
| Données | fabriquées, jetables | **clients réels** |
| `APP_ENV` | `local` | `production` |

**Les données des clients réels ne sont QUE dans la base de production.** Rien ne
les copie en local, aucun script ne les synchronise, et il ne faut pas en créer un :
une copie de données clients sur un poste de travail est une fuite qui attend son
ordinateur volé.

À l'inverse, la base locale ne contient rien qui mérite d'être sauvegardé. On peut
la détruire et la recréer à volonté — c'est même le mode de travail normal.

La séparation tient à une seule chose : **les variables d'environnement**. Le
fichier `.env` local n'est pas versionné, celui de production n'existe pas — ses
valeurs sont saisies dans l'interface de Render. `git grep DB_PASSWORD` ne doit
jamais rien rendre d'autre qu'un exemple vide.

---

## Le garde-fou sur les commandes destructrices

`migrate:fresh` supprime toutes les tables puis les recrée. `db:wipe` fait la même
chose sans les recréer. En local, ce sont des gestes ordinaires. En production,
c'est la perte de tous les comptes, profils et paiements.

Laravel demande une confirmation hors environnement local — **mais `--force` la
lève**, et `--force` figure dans presque toutes les lignes de commande de
déploiement, y compris notre `docker/entrypoint.sh` où il sert légitimement à
`migrate`.

Une faute de frappe entre `migrate` et `migrate:fresh` dans un fichier qui porte
déjà `--force` suffirait donc à vider la production sans qu'aucune question soit
posée.

`App\Providers\GardeEnvironnement` **refuse** ces deux commandes dès que
`APP_ENV` n'est ni `local` ni `testing`. Il n'y a pas d'option pour passer outre :
le seul moyen est de changer `APP_ENV`, ce qui ne se fait pas par accident.

```
$ APP_ENV=production php artisan migrate:fresh --force
REFUS : cette commande détruit toutes les tables et ne peut s'exécuter
que dans l'environnement local. Environnement courant : production.
```

Les seeders de démonstration sont soumis à la même règle : ils ne peuplent que
`local`.

---

## Sauvegarde de la production

### Deux filets, chez deux hébergeurs

**Aiven** sauvegarde automatiquement. Mais ces copies vivent chez Aiven, et
partagent donc le sort du compte : une facture impayée, une suspension, une
erreur de leur côté, et la base **et** ses sauvegardes disparaissent ensemble.
Une sauvegarde qui vit au même endroit que la donnée n'est pas une sauvegarde,
c'est une copie.

**`app:sauvegarder`** produit un `mysqldump` et le dépose sur le disque
applicatif — donc, en production, sur le stockage objet. Deux hébergeurs, deux
comptes, deux factures.

```
php artisan app:sauvegarder            # dump + rotation sur 8 fichiers
php artisan app:sauvegarder --garder=4
```

Planifiée le **dimanche à 04:00**, après l'agrégation et la purge : sauvegarder
avant reviendrait à conserver chaque semaine des millions d'événements bruts
qu'on s'apprête à supprimer.

Le dump utilise `--single-transaction` (aucun verrou sur les tables) et
`--quick` (ligne à ligne, pas de table entière en mémoire). Le mot de passe
passe par `MYSQL_PWD`, jamais en argument : un mot de passe en ligne de commande
est visible de tout le système. Un dump de moins d'un kilo-octet est **refusé** :
un dump vide qui écrase la rotation ne se découvrirait que le jour de la
restauration.

### Reste à relever

**La fenêtre de restauration d'Aiven** — souvent 2 jours sur les petits plans,
14 sur les suivants. À lire dans leur console et à noter ici.

### Le test de restauration

Une sauvegarde jamais restaurée est une hypothèse, pas une garantie.

1. Créer une base locale vide.
2. `mysql -u root base_test < storage/app/private/sauvegardes/qrid-....sql`
3. Pointer `.env` dessus, lancer `php artisan app:health`.
4. Vérifier que comptes, profils et paiements sont là.

À refaire **tous les trimestres**, et à dater ci-dessous.

| Date du test | Dump utilisé | Résultat |
|---|---|---|
| — | — | *jamais effectué* |

---

## Vérifier où l'on est

```
php artisan app:health
```

La première ligne annonce l'environnement. En cas de doute avant une commande
destructrice, c'est la vérification à faire — elle prend une seconde et évite la
seule erreur qui ne se rattrape pas.
