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

**État actuel : les sauvegardes automatiques d'Aiven sont le seul filet.** Aucune
sauvegarde applicative n'est en place, et aucune restauration n'a jamais été
testée.

C'est le trou le plus sérieux de ce document, et il faut le dire plutôt que de
décrire une procédure qui n'existe pas.

### Ce qu'il faut mettre en place

1. **Vérifier ce qu'Aiven conserve réellement.** Le plan détermine la fenêtre de
   restauration — souvent 2 jours sur les petits plans, 14 sur les suivants. Cette
   valeur est à relever dans la console et à noter ici.

2. **Une sauvegarde hors Aiven.** Un `mysqldump` hebdomadaire déposé sur un
   stockage tiers. Une sauvegarde qui vit chez le même hébergeur que la base ne
   protège pas d'un compte suspendu ou d'une erreur de facturation.

3. **Un test de restauration, écrit et daté.** Une sauvegarde jamais restaurée est
   une hypothèse, pas une garantie. La procédure : restaurer le dernier dump dans
   une base locale vide, lancer `php artisan app:health`, vérifier que les
   comptes, profils et paiements sont là. À refaire tous les trimestres, et à
   dater ici.

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
