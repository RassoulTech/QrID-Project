<?php

namespace App\Support;

/**
 * LES DESTINATIONS DU DOCK — une seule liste, lue à deux endroits.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * LE DÉFAUT QUE CETTE CLASSE SUPPRIME
 * ═══════════════════════════════════════════════════════════════════════════
 * Sur téléphone, le produit affiche DEUX navigations : le dock en bas, et le
 * panneau « Plus » qu'il ouvre. Chacune lisait sa propre liste.
 *
 * Résultat, constaté à l'écran : le dock proposait Tableau de bord, Profil,
 * Carte et Statistiques — et « Plus » rouvrait exactement les mêmes, plus une
 * cinquième. Quatre entrées sur cinq faisaient double emploi, et l'on
 * demandait au pouce de choisir entre deux chemins vers la même page.
 *
 * Un menu « Plus » ne peut contenir que ce qui ne tient PAS ailleurs. Sinon
 * il n'est pas un menu, c'est une copie.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * POURQUOI CETTE LISTE EST ICI ET NON DANS UN GABARIT
 * ═══════════════════════════════════════════════════════════════════════════
 * Deux vues doivent s'accorder sur son contenu : la coque, qui la donne au
 * dock, et le panneau, qui doit savoir ce que le dock affiche déjà pour ne
 * pas le répéter.
 *
 * Écrite dans l'une des deux, elle finirait par diverger de l'autre — et la
 * divergence est précisément le défaut qu'on vient de corriger.
 */
final class Navigation
{
    /**
     * Les routes que le dock de l'espace CLIENT porte déjà.
     *
     * Quatre au maximum : la cinquième place du dock revient à « Plus ».
     *
     * @return list<string>
     */
    public static function routesDuDockClient(): array
    {
        return ['dashboard', 'profil.index', 'carte.qr', 'statistiques'];
    }

    /**
     * Les routes que le dock de l'ADMINISTRATION porte déjà.
     *
     * @return list<string>
     */
    public static function routesDuDockAdmin(): array
    {
        return ['admin.overview', 'admin.clients.index', 'admin.payments.index', 'admin.cards.index'];
    }

    /**
     * Cette destination est-elle déjà dans le dock ?
     *
     * Le panneau s'en sert pour masquer, SUR TÉLÉPHONE UNIQUEMENT, ce que le
     * pouce atteint déjà d'un geste. Sur écran large il n'y a pas de dock :
     * la colonne reste la navigation complète, et rien n'est masqué.
     *
     * @param  list<string>  $routesDuDock
     */
    public static function estDansLeDock(string $route, array $routesDuDock): bool
    {
        return in_array($route, $routesDuDock, true);
    }
}
