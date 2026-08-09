<?php

namespace App\Services\Payment;

use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Passerelle de développement — AUCUN appel réseau, aucune somme encaissée.
 *
 * Elle permet de dérouler le parcours complet (choix du moyen, redirection,
 * retour, activation de l'abonnement, publication du profil) sans contrat
 * opérateur et sans connexion. C'est ce qui rend le parcours testable de bout
 * en bout, y compris l'échec et l'annulation.
 *
 * GARDE-FOU : elle refuse de s'exécuter hors des environnements local et de
 * test. En production, une passerelle qui valide tout sans encaisser serait
 * une faille comptable — mieux vaut une exception bruyante qu'un abonnement
 * offert en silence.
 */
class FakeGateway implements PaymentGateway
{
    public function initiate(Payment $payment): string
    {
        $this->refuserHorsDeveloppement();

        // Référence d'opérateur simulée : le vrai flux en fournit toujours une,
        // et c'est elle qui permet le rapprochement comptable.
        $payment->forceFill([
            'provider' => $this->name(),
            'provider_ref' => 'FAKE-'.Str::upper(Str::random(12)),
        ])->save();

        Log::info('Paiement simulé ouvert.', [
            'payment_id' => $payment->id,
            'method' => $payment->method,
            'amount_fcfa' => $payment->amount_fcfa,
        ]);

        // On renvoie vers NOTRE page de simulation : elle offre les trois
        // issues réelles (succès, échec, annulation) au lieu d'en supposer une.
        return route('abonnement.simulation', ['payment' => $payment->id]);
    }

    /**
     * Le retour vaut encaissement si — et seulement si — la référence
     * d'opérateur correspond à celle enregistrée à l'ouverture.
     *
     * Ce contrôle n'est pas décoratif : sans lui, n'importe qui pourrait
     * appeler l'URL de retour et s'offrir un abonnement.
     */
    public function confirms(Payment $payment, array $callback): bool
    {
        $this->refuserHorsDeveloppement();

        if (($callback['statut'] ?? null) !== 'succes') {
            return false;
        }

        $reference = (string) ($callback['reference'] ?? '');

        return $reference !== ''
            && $payment->provider_ref !== null
            && hash_equals($payment->provider_ref, $reference);
    }

    public function name(): string
    {
        return 'fake';
    }

    private function refuserHorsDeveloppement(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException(
                'FakeGateway est interdite hors développement : elle validerait des paiements sans encaissement.'
            );
        }
    }
}
