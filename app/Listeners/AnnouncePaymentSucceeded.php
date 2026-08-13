<?php

namespace App\Listeners;

use App\Enums\MotifAlerte;
use App\Events\PaymentSucceeded;
use App\Mail\PaymentSucceededMail;
use App\Services\AdminNotifier;
use App\Services\PrintableCardService;
use App\Services\QrCodeService;
use App\Support\Courrier;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Reçu au client, alerte à l'équipe.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LES PIÈCES JOINTES NE PEUVENT PAS EMPÊCHER LE REÇU DE PARTIR
 * ═══════════════════════════════════════════════════════════════════════
 * Le QR Code se lit sur le disque et le PDF passe par DomPDF : deux opérations
 * qui peuvent échouer — disque plein, extension GD absente, gabarit cassé.
 *
 * Elles sont donc produites CHACUNE dans son propre try/catch, en amont du
 * message. Un PDF qui ne se génère pas coûte une pièce jointe ; il ne doit
 * jamais coûter le reçu lui-même. Quelqu'un qui vient de payer 2 500 FCFA a
 * droit à sa preuve, avec ou sans fichier d'impression.
 *
 * Le coût de ces deux générations est assumé DANS la requête HTTP, faute de
 * worker. C'est le retour de l'opérateur de paiement : une seconde de plus y
 * est acceptable, ce que ne serait pas une seconde de plus au chargement du
 * tableau de bord.
 */
class AnnouncePaymentSucceeded
{
    public function __construct(
        private AdminNotifier $equipe,
        private QrCodeService $qr,
        private PrintableCardService $carte,
    ) {}

    public function handle(PaymentSucceeded $event): void
    {
        $payment = $event->payment;
        $user = $payment->user;

        if (! $user) {
            return;
        }

        $profile = $user->profile;
        $abonnement = $payment->subscription;

        $montant = number_format((int) $payment->amount_fcfa, 0, ',', ' ');
        $reference = $payment->provider_ref ?: 'PAY-'.$payment->id;
        $formule = $abonnement?->plan?->name
            ?? ($payment->payload['plan_slug'] ?? 'Abonnement');

        Courrier::informer($user->email, new PaymentSucceededMail(
            name: $user->name,
            reference: $reference,
            montant: $montant,
            moyen: $payment->method_label,
            formule: $formule,
            date: $payment->created_at?->translatedFormat('j F Y à H:i') ?? '',
            echeance: $abonnement?->ends_at?->translatedFormat('j F Y'),
            publicUrl: $profile ? route('profile.public', $profile->slug) : null,
            dashboardUrl: route('dashboard'),
            qrPng: $profile ? $this->essayer(fn () => $this->qr->png($profile), 'QR Code') : null,
            pdf: $profile ? $this->essayer(fn () => $this->carte->render($profile), 'PDF') : null,
            slug: $profile->slug ?? 'carte',
            recipient: $user->email,
        ));

        $this->equipe->alerter(
            MotifAlerte::PaiementReussi,
            [
                'Client' => $user->name,
                'Adresse' => $user->email,
                'Montant' => $montant.' FCFA',
                'Moyen' => $payment->method_label,
                'Formule' => $formule,
                'Référence' => $reference,
            ],
            route('admin.payments.index'),
        );
    }

    /**
     * Produit une pièce jointe, ou null. N'interrompt jamais l'envoi.
     *
     * @param  callable(): string  $produire
     */
    private function essayer(callable $produire, string $quoi): ?string
    {
        try {
            return $produire();
        } catch (Throwable $e) {
            Log::channel('mail')->warning('Pièce jointe non produite', [
                'piece' => $quoi,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
