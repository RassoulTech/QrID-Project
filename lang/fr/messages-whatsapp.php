<?php

/*
|------------------------------------------------------------------------------
| LES MESSAGES WHATSAPP
|------------------------------------------------------------------------------
| Un message par CONTEXTE, jamais un message générique réutilisé partout.
| « Bonjour » sur un bouton de partage oblige à tout écrire au moment précis
| où l'on veut aller vite ; un texte déjà rédigé se corrige d'un mot.
|
| ILS SONT MODIFIABLES PAR L'EXPÉDITEUR. WhatsApp pré-remplit la zone de
| saisie sans envoyer : la personne relit, ajuste, puis envoie. C'est pourquoi
| ces textes sont des propositions et non des formules figées — ils doivent
| sonner comme une phrase qu'on aurait pu écrire soi-même.
|
| CE QU'ILS NE CONTIENNENT JAMAIS : une donnée qui n'est pas déjà publique.
| Le texte voyage dans une URL, que le navigateur retient. Voir la règle
| écrite en tête de App\Support\Whatsapp.
|
| LES VARIABLES sont remplacées par Laravel : « :nom » devient le nom, « :url »
| l'adresse. Une variable absente resterait affichée telle quelle dans le
| message — d'où le test qui vérifie que chaque gabarit reçoit les siennes.
*/

return [

    /*
     | PARTAGE — je diffuse quelque chose qui m'appartient.
     |
     | Pas de destinataire : WhatsApp ouvre le sélecteur de contacts. Le
     | message est donc écrit pour être lu par n'importe qui, et ne suppose
     | aucune relation préalable.
     */
    'partage' => [
        'carte' => "Bonjour, voici ma carte de visite numérique :\n:nom\n:url",
        'qr' => "Bonjour, voici mon QR Code professionnel — scannez-le ou ouvrez le lien :\n:nom\n:url",
    ],

    /*
     | INVITATION — j'invite quelqu'un à créer la sienne.
     |
     | Le ton est celui d'une recommandation entre confrères, pas d'une
     | publicité : c'est une personne qui parle, pas le produit.
     */
    'invitation' => [
        'confrere' => "Bonjour, j'utilise QrID pour ma carte de visite numérique et je trouve ça pratique. Si ça t'intéresse : :url",
    ],

    /*
     | CONTACT — j'écris au titulaire d'une carte que je viens de consulter.
     |
     | Le visiteur ne s'est pas identifié, et rien ne justifie de deviner qui
     | il est. Le message dit d'où il vient, ce qui suffit au titulaire pour
     | situer l'échange.
     */
    'contact' => [
        'titulaire' => 'Bonjour :nom, je vous contacte après avoir consulté votre carte de visite numérique.',
    ],

];
