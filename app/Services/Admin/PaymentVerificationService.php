<?php

namespace App\Services\Admin;

use App\Models\AdminAction;
use App\Models\Payment;
use App\Services\Payment\CheckoutService;
use App\Services\Payment\PaymentGateway;
use App\Support\AdminActionType;

/**
 * Vérification manuelle d'un paiement resté en attente.
 *
 * LE CAS RÉEL : le client a payé, l'opérateur a débité, mais le retour vers
 * l'application s'est perdu — réseau coupé, onglet fermé trop tôt. Le
 * paiement reste « en attente » et le client n'a rien reçu de ce qu'il a
 * payé. L'administration interroge alors la passerelle.
 *
 * IDEMPOTENCE. C'est l'exigence centrale, et elle tient à deux endroits :
 *
 *   · un paiement déjà réussi ressort tel quel, sans second appel réseau et
 *     sans seconde prolongation d'abonnement ;
 *   · l'ouverture de l'abonnement passe par CheckoutService::succeed(), qui
 *     porte déjà sa propre garde. On ne réécrit pas cette logique ici : deux
 *     chemins d'encaissement finiraient par diverger, et c'est exactement là
 *     qu'un client se retrouve avec deux abonnements pour un paiement.
 *
 * Le résultat est un verdict, pas un booléen : « déjà encaissé » et « toujours
 * en attente chez l'opérateur » sont deux issues distinctes, et l'écran doit
 * pouvoir les dire différemment.
 */
class PaymentVerificationService
{
    public const DEJA_ENCAISSE = 'already';

    public const ENCAISSE = 'confirmed';

    public const TOUJOURS_EN_ATTENTE = 'pending';

    public const ECHOUE = 'failed';

    public function __construct(
        private CheckoutService $checkout,
        private PaymentGateway $gateway,
    ) {}

    public function verifier(Payment $paiement, string $motif): string
    {
        if ($paiement->isSuccessful()) {
            $this->journaliser($paiement, $motif, self::DEJA_ENCAISSE);

            return self::DEJA_ENCAISSE;
        }

        /*
         | On interroge la passerelle avec le retour qu'elle nous a déjà donné,
         | conservé dans `payload`. C'est la seule information dont on dispose
         | hors du navigateur du client. Une passerelle réelle exposerait ici
         | un appel de statut par référence — l'interface PaymentGateway est
         | le point d'extension prévu pour cela.
         */
        $confirme = $this->gateway->confirms($paiement, $paiement->payload ?? []);

        if (! $confirme) {
            $this->journaliser($paiement, $motif, self::TOUJOURS_EN_ATTENTE);

            return self::TOUJOURS_EN_ATTENTE;
        }

        $encaisse = $this->checkout->succeed($paiement, $paiement->payload ?? []);

        $verdict = $encaisse ? self::ENCAISSE : self::ECHOUE;

        $this->journaliser($paiement, $motif, $verdict);

        return $verdict;
    }

    /**
     * TOUJOURS journalisé, y compris quand rien ne change.
     *
     * Une vérification qui ne trouve rien est une information : elle prouve
     * que la réclamation a été instruite, et à quelle date.
     */
    private function journaliser(Payment $paiement, string $motif, string $verdict): void
    {
        AdminAction::log(
            AdminActionType::VERIFICATION_PAIEMENT,
            $paiement,
            "{$motif} — résultat : ".self::libelle($verdict)
        );
    }

    public static function libelle(string $verdict): string
    {
        return match ($verdict) {
            self::DEJA_ENCAISSE => 'paiement déjà encaissé, aucun changement',
            self::ENCAISSE => 'paiement confirmé et abonnement ouvert',
            self::TOUJOURS_EN_ATTENTE => 'toujours en attente chez l\'opérateur',
            default => 'refusé par l\'opérateur',
        };
    }
}
