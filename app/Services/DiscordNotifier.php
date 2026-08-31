<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * ENVOI VERS DISCORD.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * IL NE PEUT RIEN CASSER, ET C'EST TOUT SON CONTRAT
 * ═══════════════════════════════════════════════════════════════════════
 * Cette classe est appelée par une commande planifiée. Une exception qui en
 * sortirait ferait échouer la tâche, et sur un planificateur qui n'a pas
 * encore de supervision, un échec silencieux est indiscernable d'une absence
 * d'exécution.
 *
 * Toute panne est donc avalée ET consignée : la valeur de retour dit ce qui
 * s'est passé, et le journal garde la trace. C'est le même partage que pour
 * les e-mails d'information — silencieux pour l'appelant, bruyant pour
 * l'exploitant.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * SYNCHRONE, ET ASSUMÉ
 * ═══════════════════════════════════════════════════════════════════════
 * Le plan demandait un envoi en file. Aucun worker n'exécute queue:work :
 * un message mis en file resterait dans la table `jobs` sans jamais en
 * sortir — exactement la panne qui a coûté plusieurs jours sur les e-mails.
 *
 * Trois tentatives espacées d'une seconde remplacent la file. Discord répond
 * en quelques centaines de millisecondes ; ce délai est payé par une commande
 * planifiée que personne n'attend, pas par une requête HTTP d'utilisateur.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * L'URL DU WEBHOOK EST UN SECRET
 * ═══════════════════════════════════════════════════════════════════════
 * Quiconque la possède peut écrire dans le salon. Elle n'apparaît donc jamais
 * dans un journal, même en cas d'erreur — seul l'identifiant du salon, tronqué,
 * est consigné pour permettre de distinguer deux webhooks.
 */
class DiscordNotifier
{
    /**
     * Vert de la marque, converti en entier — Discord n'accepte pas
     * « #0B3B2E ».
     *
     * La valeur suit $vert-fonce de `_tokens.scss`. Elle est recopiée à la
     * main, et doit l'être : aucune feuille de style n'atteint la charge
     * utile d'une API. Un changement de charte se répercute donc ici.
     */
    public const COULEUR_OK = 0x0B3B2E;

    /** Ambre : la journée a produit quelque chose qui appelle une action. */
    public const COULEUR_ALERTE = 0xB45309;

    /** Discord tronque au-delà ; on préfère couper nous-mêmes, proprement. */
    private const MAX_CHAMPS = 25;

    public static function estConfigure(): bool
    {
        return filled(config('notifications.discord.webhook'));
    }

    /**
     * Envoie un embed. Rend true si Discord l'a accepté.
     *
     * @param  array<int, array{name:string, value:string, inline?:bool}>  $champs
     */
    public function envoyer(
        string $titre,
        string $description = '',
        array $champs = [],
        int $couleur = self::COULEUR_OK,
        ?string $pied = null,
    ): bool {
        $webhook = (string) config('notifications.discord.webhook');

        if ($webhook === '') {
            Log::warning('Récapitulatif Discord non envoyé : aucun webhook configuré.');

            return false;
        }

        $embed = array_filter([
            'title' => $titre,
            'description' => $description !== '' ? $description : null,
            'color' => $couleur,
            'fields' => array_slice($champs, 0, self::MAX_CHAMPS),
            'footer' => $pied ? ['text' => $pied] : null,
            'timestamp' => now()->toIso8601String(),
        ], fn ($v) => $v !== null && $v !== []);

        try {
            $reponse = Http::timeout(config('notifications.discord.timeout', 10))
                // Trois tentatives, une seconde d'écart. `throw: false` : c'est
                // la valeur de retour qui décide, jamais une exception.
                ->retry(3, 1000, throw: false)
                ->asJson()
                ->post($webhook, [
                    'username' => config('app.name'),
                    'embeds' => [$embed],
                ]);
        } catch (Throwable $e) {
            Log::error('Récapitulatif Discord : envoi impossible', [
                'salon' => $this->salonTronque($webhook),
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        if ($reponse->successful()) {
            return true;
        }

        /*
         | LE CORPS DE LA RÉPONSE EST CONSIGNÉ, PAS L'URL.
         |
         | Discord explique ses refus — webhook supprimé, charge utile
         | invalide, cadence dépassée — et cette explication est la seule chose
         | qui permette de corriger. L'URL, elle, est un secret : quiconque la
         | possède peut écrire dans le salon.
         */
        Log::error('Récapitulatif Discord refusé', [
            'salon' => $this->salonTronque($webhook),
            'statut' => $reponse->status(),
            'reponse' => mb_substr($reponse->body(), 0, 300),
        ]);

        return false;
    }

    /**
     * De quoi distinguer deux webhooks dans un journal, sans jamais permettre
     * de s'en servir.
     */
    private function salonTronque(string $webhook): string
    {
        if (! preg_match('#/webhooks/(\d+)/#', $webhook, $trouve)) {
            return 'inconnu';
        }

        return mb_substr($trouve[1], 0, 6).'…';
    }
}
