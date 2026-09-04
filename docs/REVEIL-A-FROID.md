# Empêcher le conteneur de s'endormir

Marche à suivre, 3 minutes. Aucun code à écrire, rien à payer.

---

## Le problème, en une phrase

Render arrête un service gratuit après **quinze minutes sans requête**. Le
réveil prend une cinquantaine de secondes — pendant lesquelles le premier
visiteur à scanner une carte attend devant un écran blanc, et conclut le plus
souvent que le lien est mort.

Aucune optimisation interne ne corrige cela : il faut du trafic venu de
l'extérieur.

---

## La solution

Un service de cron gratuit appelle une adresse du site toutes les dix minutes.
Le conteneur ne s'endort jamais.

L'adresse existe déjà :

    https://qrid-uutz.onrender.com/reveil

Elle ne touche ni la base, ni les sessions, ni les vues : **zéro requête SQL,
27 ms**, deux caractères en réponse. C'est un signe de vie, pas une page.

---

## Les trois étapes

### 1 · Créer un compte

Aller sur **https://cron-job.org** et créer un compte gratuit.
Le plan gratuit suffit largement — il autorise des appels à la minute, et il
en faut un toutes les dix.

### 2 · Créer la tâche

Bouton **« Create cronjob »**, puis :

| Champ | Valeur |
|---|---|
| Title | `QrID — réveil` |
| URL | `https://qrid-uutz.onrender.com/reveil` |
| Schedule | **Every 10 minutes** |

Laisser tout le reste par défaut. La méthode est `GET`, aucun en-tête ni
corps n'est nécessaire.

### 3 · Vérifier

Cliquer sur **« TEST RUN »** avant d'enregistrer. La réponse attendue est :

    Statut : 200
    Corps  : ok

Si c'est le cas, enregistrer. C'est terminé.

---

## Ce que cela change, et ce que cela ne change pas

**Ce que cela règle** — le réveil à froid. Un visiteur qui scanne une carte
obtient une réponse en une demi-seconde au lieu d'attendre cinquante.

**Ce que cela ne règle pas** — le plancher de ~270 ms par page. Il vient du
dixième de processeur du plan gratuit, et seul un changement de plan le
franchit.

**Le quota d'heures.** Maintenir le conteneur éveillé consomme des heures
d'instance. À surveiller sur le tableau de bord Render pendant le premier
mois. Si la marge devient courte, limiter la tâche à une plage horaire — par
exemple 7 h – 22 h, heure de Dakar — en réglant `Schedule` sur ces heures. Un
scan à 3 h du matin reste rare, et acceptera d'attendre son réveil.

---

## Si cela cesse de fonctionner

cron-job.org est un service gratuit : il peut tomber. Le symptôme sera le
retour du réveil lent, sans aucune erreur nulle part.

Deux vérifications, dans cet ordre :

1. **Sur cron-job.org**, l'historique de la tâche indique les derniers
   appels et leur statut.
2. **Sur Render**, les journaux du service montrent les requêtes reçues :
   une ligne `GET /reveil` doit apparaître toutes les dix minutes.

Le planificateur interne, lui, ne dépend pas de ce ping : il tourne dans le
conteneur (`supervisor`), et les tâches rattrapent ce qu'elles ont manqué
pendant un sommeil. Voir `App\Support\Planificateur`.
