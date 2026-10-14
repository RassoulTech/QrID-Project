<?php

namespace App\Support;

/**
 * LES DESTINATIONS DES COQUES — une seule liste, lue partout où l'on navigue.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * POURQUOI LA LISTE EST ICI ET NON DANS UN GABARIT
 * ═══════════════════════════════════════════════════════════════════════════
 * L'espace client affiche la même navigation sous deux formes : des onglets
 * en haut sur ordinateur, une barre d'onglets ancrée en bas sur téléphone.
 * Écrite dans chaque gabarit, la liste finirait par diverger — une page
 * présente en haut et absente en bas, inatteignable au pouce sans que rien
 * ne le signale. Les deux surfaces lisent donc CETTE liste, et les tests
 * aussi.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * SIX DESTINATIONS EN HAUT, CINQ EN BAS — et aucune perdue
 * ═══════════════════════════════════════════════════════════════════════════
 * À 320px, cinq entrées laissent 64px chacune : de quoi porter une icône et
 * un libellé court lisible. Six n'en laissent que 53 — le libellé se tronque.
 *
 * L'abonnement est donc la seule destination absente de la barre du bas, et
 * il reste à UN geste : dans le menu du compte (en haut à droite, sans
 * JavaScript), dans la tuile d'abonnement du tableau de bord et dans le
 * bandeau d'essai. L'onglet « Compte » du bas s'allume aussi sur ses écrans :
 * l'abonnement fait partie du compte, et l'on sait toujours où l'on est.
 */
final class Navigation
{
    /**
     * Les onglets de l'espace CLIENT, dans l'ordre du haut (ordinateur).
     *
     * Chaque entrée :
     *   route   — la destination ;
     *   actif   — les motifs routeIs() qui allument l'onglet ;
     *   icone   — identifiant x-client-icon ;
     *   libelle — clé de traduction, onglet du haut ;
     *   court   — clé de traduction, barre du bas (huit caractères au plus) ;
     *   bas     — rang dans la barre du bas, ou null si l'entrée n'y est pas ;
     *   actif_bas — motifs supplémentaires qui allument l'entrée du BAS.
     *
     * @return list<array{route:string, actif:list<string>, icone:string, libelle:string, court:string, bas:?int, actif_bas?:list<string>}>
     */
    public static function ongletsClient(): array
    {
        return [
            [
                'route' => 'dashboard',
                'actif' => ['dashboard'],
                'icone' => 'grille',
                'libelle' => 'navigation.client.onglet.tableau_de_bord',
                'court' => 'navigation.client.court.tableau_de_bord',
                'bas' => 1,
            ],
            [
                'route' => 'statistiques',
                'actif' => ['statistiques'],
                'icone' => 'courbe',
                'libelle' => 'navigation.client.onglet.statistiques',
                'court' => 'navigation.client.court.statistiques',
                'bas' => 4,
            ],
            [
                // « Mon profil » reste allumé pendant TOUT le parcours de
                // création : sans cela, la navigation perdrait son repère au
                // milieu des trois étapes.
                'route' => 'profil.index',
                'actif' => ['profil.index', 'profile.create.*', 'profile.edit', 'profile.preview'],
                'icone' => 'personne',
                'libelle' => 'navigation.client.onglet.mon_profil',
                'court' => 'navigation.client.court.mon_profil',
                'bas' => 2,
            ],
            [
                'route' => 'carte.qr',
                'actif' => ['carte.qr', 'carte.physique'],
                'icone' => 'qr',
                'libelle' => 'navigation.client.onglet.mon_qr',
                'court' => 'navigation.client.court.mon_qr',
                'bas' => 3,
            ],
            [
                // Mène à l'écran des formules, qui sert la première
                // souscription comme le renouvellement.
                'route' => 'abonnement.paiement',
                'actif' => ['abonnement.*'],
                'icone' => 'carte-bancaire',
                'libelle' => 'navigation.client.onglet.abonnement',
                'court' => 'navigation.client.court.mon_abonnement',
                'bas' => null,
            ],
            [
                'route' => 'compte.edit',
                'actif' => ['compte.*'],
                'icone' => 'compte',
                'libelle' => 'navigation.client.onglet.compte',
                'court' => 'navigation.client.court.compte',
                'bas' => 5,
                'actif_bas' => ['abonnement.*'],
            ],
        ];
    }

    /**
     * Les entrées de la barre du bas, dans SON ordre.
     *
     * @return list<array<string, mixed>>
     */
    public static function ongletsClientDuBas(): array
    {
        $bas = array_values(array_filter(
            self::ongletsClient(),
            fn (array $o) => $o['bas'] !== null,
        ));

        usort($bas, fn (array $a, array $b) => $a['bas'] <=> $b['bas']);

        return $bas;
    }

    /**
     * Les routes que la barre du bas de l'espace CLIENT porte.
     *
     * @return list<string>
     */
    public static function routesDuDockClient(): array
    {
        return array_column(self::ongletsClientDuBas(), 'route');
    }

    /**
     * Les rubriques de l'ADMINISTRATION, par section.
     *
     * Chaque entrée : [identifiant, route, motif d'activation, vue attendue].
     * L'identifiant choisit l'icône et la clé `navigation.admin.*` ; il ne
     * change dans aucune langue. La colonne, le fil d'Ariane de la barre et
     * la barre du bas lisent cette liste — aucune des trois ne la recopie.
     *
     * @return array<string, list<array{0:string, 1:string, 2:string, 3:string}>>
     */
    public static function rubriquesAdmin(): array
    {
        return [
            'pilotage' => [
                ['vue-ensemble', 'admin.overview', 'admin.overview', 'admin.overview'],
                ['statistiques', 'admin.statistics', 'admin.statistics', 'admin.statistics'],
            ],
            'gestion' => [
                ['clients', 'admin.clients.index', 'admin.clients.*', 'admin.clients.index'],
                ['profils', 'admin.profiles.index', 'admin.profiles.*', 'admin.profiles.index'],
                ['paiements', 'admin.payments.index', 'admin.payments.*', 'admin.payments.index'],
                ['abonnements', 'admin.subscriptions', 'admin.subscriptions', 'admin.subscriptions'],
                ['cartes', 'admin.cards.index', 'admin.cards.*', 'admin.cards.index'],
            ],
            'configuration' => [
                ['modeles', 'admin.templates.index', 'admin.templates.*', 'admin.templates.index'],
                ['parametres', 'admin.settings', 'admin.settings*', 'admin.settings.index'],
                ['journal', 'admin.audit.index', 'admin.audit.*', 'admin.audit.index'],
            ],
            'systeme' => [
                ['etat-systeme', 'admin.system.health', 'admin.system.health', 'admin.system-health'],
            ],
        ];
    }

    /**
     * Les routes que la barre du bas de l'ADMINISTRATION porte déjà.
     *
     * L'administration compte onze rubriques : quatre tiennent dans la
     * barre, la cinquième place ouvre la colonne (« Plus »), qui garde les
     * autres. Le panneau ne répète pas ce que la barre porte.
     *
     * @return list<string>
     */
    public static function routesDuDockAdmin(): array
    {
        return ['admin.overview', 'admin.clients.index', 'admin.payments.index', 'admin.cards.index'];
    }

    /**
     * Cette destination est-elle déjà dans la barre du bas ?
     *
     * Le panneau de l'administration s'en sert pour masquer, SUR TÉLÉPHONE
     * UNIQUEMENT, ce que le pouce atteint déjà d'un geste.
     *
     * @param  list<string>  $routesDuDock
     */
    public static function estDansLeDock(string $route, array $routesDuDock): bool
    {
        return in_array($route, $routesDuDock, true);
    }
}
