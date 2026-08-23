// =============================================================================
// LE SCRIPT DE LA CARTE PUBLIQUE — et lui seul
// =============================================================================
//
// POURQUOI UN SECOND POINT D'ENTRÉE PLUTÔT QUE app.js
// -----------------------------------------------------------------------------
// app.js embarque Bootstrap, la révélation au défilement, les compteurs de
// caractères, le répéteur de réseaux sociaux, les filtres d'administration —
// une centaine de kilo-octets dont cette page n'a besoin d'aucun.
//
// Or c'est LA page du produit qui doit être la plus légère : elle s'ouvre
// après un scan, sur le téléphone d'un inconnu, souvent en 3G, et elle a
// quelques secondes pour convaincre. Lui faire télécharger le tableau de bord
// serait absurde.
//
// Ce fichier ne contient donc qu'une chose, et c'est la seule de la page qui
// ne puisse pas se faire sans script.
//
// LE DÉFAUT QU'IL CORRIGE
// -----------------------------------------------------------------------------
// Le module d'enregistrement de contact vivait dans app.js. Le gabarit de la
// page publique ne charge que la feuille de style — « Pas de JavaScript ici :
// rien sur cette page n'en a besoin », disait le commentaire, et c'était vrai
// quand il a été écrit.
//
// Résultat : le lien « Enregistrer » n'a JAMAIS été réécrit en intention
// Android. Il est resté la fiche vCard brute, c'est-à-dire un téléchargement.
// Le code était juste, il ne s'exécutait pas — et rien ne pouvait le montrer
// côté serveur.
// =============================================================================

import enregistrerContact from './modules/enregistrer-contact';

const demarrer = () => enregistrerContact();

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', demarrer);
} else {
    demarrer();
}
