<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * QUI REÇOIT LES ALERTES DE L'ÉQUIPE.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI CETTE CLASSE EXISTE
 * ═══════════════════════════════════════════════════════════════════════
 * La liste des destinataires était résolue à DEUX endroits — AdminNotifier et
 * ContactController — avec deux ordres de priorité différents. Deux
 * implémentations d'une même règle finissent toujours par diverger, et ici la
 * divergence se serait constatée sur un message qui n'arrive pas.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LES ADRESSES DE DÉMONSTRATION SONT ÉCARTÉES, ET C'EST UN CORRECTIF
 * ═══════════════════════════════════════════════════════════════════════
 * AdminDemoSeeder crée deux administrateurs sur un domaine fictif, pour que
 * le journal d'audit de l'espace admin distingue plusieurs auteurs. Ces
 * comptes sont utiles à l'écran et NUISIBLES au courrier :
 *
 *   · leur domaine n'existe pas, donc chaque envoi rebondit ;
 *   · les rebonds abîment la réputation de l'expéditeur — celle-là même dont
 *     dépendent les liens de réinitialisation de mot de passe ;
 *   · et surtout, chez la plupart des fournisseurs, UN SEUL destinataire
 *     invalide fait rejeter TOUT LE MESSAGE. Deux adresses fictives
 *     empêchaient donc l'unique adresse réelle de recevoir quoi que ce soit.
 *
 * C'était la cause exacte des messages de contact qui ne partaient pas.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * L'ORDRE DE PRIORITÉ
 * ═══════════════════════════════════════════════════════════════════════
 * 1. la liste explicite, si elle est renseignée — elle a le dernier mot, et
 *    permet de viser une boîte partagée plutôt que des boîtes personnelles ;
 * 2. à défaut, les comptes de rôle « admin » présents en base, filtrés.
 *
 * Le repli sur la base n'est pas une commodité : une liste figée dans une
 * variable d'environnement se périme au premier changement d'équipe, et le
 * jour où quelqu'un part, ses alertes partent avec lui sans que personne ne
 * s'en aperçoive.
 */
final class DestinatairesEquipe
{
    /**
     * Les adresses à qui écrire. Peut être vide — l'appelant doit le prévoir.
     *
     * @return array<int, string>
     */
    public static function alertes(): array
    {
        $explicites = array_filter((array) config('notifications.admin_recipients', []));

        if ($explicites !== []) {
            return self::routables(array_values($explicites));
        }

        return self::routables(
            User::admins()->whereNotNull('email')->pluck('email')->all()
        );
    }

    /**
     * Les adresses du formulaire de contact.
     *
     * L'adresse de support l'emporte quand elle existe : les messages de
     * clients n'ont pas à atterrir dans des boîtes personnelles, ne serait-ce
     * que pour qu'un départ de l'équipe ne les fasse pas disparaître.
     *
     * @return array<int, string>
     */
    public static function contact(): array
    {
        $support = trim((string) config('landing.support.email'));

        if ($support !== '') {
            return self::routables([$support]);
        }

        return self::alertes();
    }

    /**
     * Écarte ce qui ne peut pas être livré.
     *
     * Le filtrage est TRACÉ : une adresse écartée en silence donnerait, le
     * jour d'un incident, un « pourquoi n'ai-je rien reçu » sans réponse.
     *
     * @param  array<int, string>  $adresses
     * @return array<int, string>
     */
    private static function routables(array $adresses): array
    {
        $exclus = array_map('mb_strtolower', (array) config('notifications.excluded_domains', []));

        $gardees = [];
        $ecartees = [];

        foreach ($adresses as $adresse) {
            $adresse = mb_strtolower(trim((string) $adresse));

            if ($adresse === '' || ! filter_var($adresse, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $domaine = mb_strtolower((string) mb_substr(strrchr($adresse, '@') ?: '', 1));

            if (in_array($domaine, $exclus, true)) {
                $ecartees[] = $adresse;

                continue;
            }

            $gardees[] = $adresse;
        }

        if ($ecartees !== []) {
            Log::channel('mail')->info('Destinataires de démonstration écartés', [
                'adresses' => $ecartees,
            ]);
        }

        return array_values(array_unique($gardees));
    }
}
