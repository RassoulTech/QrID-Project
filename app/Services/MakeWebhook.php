<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * ENVOI VERS MAKE — capture des prospects et automatisations.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * IL NE PEUT PAS CASSER UNE INSCRIPTION
 * ═══════════════════════════════════════════════════════════════════════
 * Cet appel se produit PENDANT la confirmation d'un compte. Si Make est
 * indisponible, si le réseau tombe, si le scénario a été supprimé, le client
 * doit malgré tout obtenir son compte.
 *
 * Toute panne est donc avalée ET consignée : la valeur de retour dit ce qui
 * s'est passé, le journal garde la trace. C'est le même partage que pour les
 * e-mails d'information — silencieux pour l'utilisateur, bruyant pour
 * l'exploitant. Un prospect manqué coûte un contact ; une inscription
 * refusée coûte le client.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LA SIGNATURE PROTÈGE MAKE, PAS NOUS
 * ═══════════════════════════════════════════════════════════════════════
 * Un webhook Make est une URL publique. Sans vérification, quiconque la
 * découvre peut y injecter de faux prospects — qui seraient alors invités au
 * groupe WhatsApp réservé aux clients.
 *
 * On signe donc la charge utile en HMAC-SHA256 et on transmet l'empreinte en
 * en-tête. Le scénario Make recalcule la même empreinte avec le secret
 * partagé et rejette ce qui ne correspond pas. Sans secret configuré, aucun
 * en-tête n'est envoyé : le scénario ne doit alors pas prétendre vérifier.
 */
class MakeWebhook
{
    public static function estConfigure(): bool
    {
        return filled(config('automation.make.webhook'));
    }

    /**
     * Envoie un événement. Rend true si Make l'a accepté.
     *
     * @param  string  $evenement  nom court, sur lequel le scénario aiguille
     * @param  array<string, mixed>  $donnees
     */
    public function envoyer(string $evenement, array $donnees): bool
    {
        $webhook = (string) config('automation.make.webhook');

        if ($webhook === '') {
            return false;   // fonction absente, pas en panne : rien à signaler
        }

        $charge = [
            'evenement' => $evenement,
            'source' => config('app.name'),
            'environnement' => app()->environment(),
            'horodatage' => now()->toIso8601String(),
            'donnees' => $donnees,
        ];

        /*
         | LE CORPS EST ENCODÉ UNE SEULE FOIS, ET C'EST INDISPENSABLE.
         |
         | Première version : je signais un JSON produit ici, puis laissais
         | asJson() ré-encoder le tableau pour l'envoi. Les deux encodages
         | diffèrent — accents échappés ou non, barres obliques protégées ou
         | non — donc la signature n'aurait JAMAIS correspondu au corps reçu.
         |
         | Le scénario Make aurait rejeté chaque appel, et l'échec se serait
         | manifesté comme un silence : aucun prospect capturé, aucune erreur
         | visible de notre côté.
         |
         | On signe donc EXACTEMENT la chaîne qu'on transmet.
         */
        $corps = (string) json_encode($charge, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        try {
            $reponse = Http::timeout(config('automation.make.timeout', 10))
                // Deux tentatives seulement, et un délai court : cet appel se
                // produit dans la requête de confirmation d'inscription. Faire
                // attendre le client dix secondes pour un envoi qui ne le
                // concerne pas serait le pire des arbitrages.
                ->retry(2, 500, throw: false)
                ->withHeaders($this->entetes($corps))
                ->withBody($corps, 'application/json')
                ->post($webhook);
        } catch (Throwable $e) {
            Log::warning('Make injoignable', [
                'evenement' => $evenement,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        if ($reponse->successful()) {
            return true;
        }

        Log::warning('Make a refusé l\'envoi', [
            'evenement' => $evenement,
            'statut' => $reponse->status(),
            'reponse' => mb_substr($reponse->body(), 0, 300),
        ]);

        return false;
    }

    /**
     * Les en-têtes, signature comprise.
     *
     * L'empreinte porte sur la CHAÎNE EXACTE transmise, reçue en paramètre —
     * jamais sur un tableau ré-encodé ici. C'est la seule façon que le
     * scénario Make retrouve la même valeur.
     *
     * @return array<string, string>
     */
    private function entetes(string $corps): array
    {
        $secret = (string) config('automation.make.secret');

        if ($secret === '') {
            return [];
        }

        return [
            'X-QrID-Signature' => hash_hmac('sha256', $corps, $secret),
        ];
    }
}
