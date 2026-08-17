# Logos des opérateurs de paiement

Déposez ici les logos **officiels**, récupérés auprès de chaque opérateur —
dossier de presse ou kit partenaire. C'est la seule source qui fasse foi.

    wave.svg
    orange_money.svg
    free_money.svg

Le premier fichier trouvé l'emporte, dans cet ordre : `.svg`, `.png`, `.webp`,
`.jpg`, `.jpeg`.

Le JPEG est **dernier** à dessein : il ignore la transparence et traîne toujours
un rectangle opaque derrière le logo. Préférez `.svg`, ou `.png` à défaut.

## Deux pièges qui font qu'un logo déposé ne s'affiche pas

**Le nom du fichier est celui de la méthode telle qu'elle est stockée en base :**
`orange_money`, avec un souligné. Un fichier nommé `orange money.jpeg`, avec une
espace, ne sera jamais trouvé — et rien ne le signalera.

**Le fichier doit être cadré sur le symbole, au carré.** Il est affiché dans une
boîte de 40 px de côté :

- une bannière avec de grandes marges donne un logo minuscule au centre ;
- un verrouillage « symbole + nom » rend le nom illisible — 4 px de haut — alors
  que le composant écrit déjà le nom de l'opérateur juste à côté.

C'est la raison pour laquelle `wave.png` et `orange_money.png` ne sont pas les
fichiers reçus tels quels : le premier était une bannière 1200 × 630 dont
l'icône n'occupait que le tiers central, le second un verrouillage complet dont
seules les deux flèches ont été gardées. Prévoyez 160 px de côté — la boîte en
fait 40, et un écran de téléphone compte trois pixels réels par point.

## Aucun code à modifier

Le composant `x-operator-mark` teste la présence du fichier à chaque affichage.
Déposer un logo suffit à l'activer ; le retirer rétablit la pastille.

`OperatorMarkTest` vérifie que chaque fichier réellement présent dans ce dossier
est bien servi sur l'écran de paiement. Un logo déposé qui n'apparaît pas fait
donc échouer la suite de tests au lieu de passer inaperçu.

## État au 17 août

| Opérateur | Marque affichée |
|---|---|
| Wave | `wave.png` — icône officielle |
| Orange Money | `orange_money.png` — symbole officiel aux deux flèches |
| Free Money | pastille, **logo manquant** |

## Ce qui s'affiche tant qu'un logo manque

Une pastille aux **couleurs officielles** de l'opérateur, avec ses initiales.

Ce choix n'est pas un pis-aller. Redessiner ces logos de mémoire produirait des
formes approximatives — un « W » à peu près juste, un cercle Orange à peu près
rond. Sur un écran de paiement, c'est le pire endroit pour un à-peu-près : le
client s'apprête à envoyer de l'argent, et un logo qui ne ressemble pas tout à
fait à celui qu'il connaît est exactement ce qui le fait renoncer.

Une pastille exacte vaut mieux qu'un logo faux.

## Rappel

Ces marques sont déposées. Les afficher pour indiquer un moyen de paiement
accepté est un usage normal ; les modifier, les recolorer ou les intégrer à
votre propre identité ne l'est pas.
