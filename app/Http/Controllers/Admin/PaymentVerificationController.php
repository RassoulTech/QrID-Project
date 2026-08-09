<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MotifRequest;
use App\Models\Payment;
use App\Services\Admin\PaymentVerificationService;
use Illuminate\Http\RedirectResponse;

/**
 * Vérification manuelle d'un paiement auprès de la passerelle.
 *
 * Le verdict remonte tel quel à l'écran, y compris quand rien n'a changé :
 * « toujours en attente chez l'opérateur » est une réponse utile, et
 * l'afficher évite qu'on reclique dix fois.
 */
class PaymentVerificationController extends Controller
{
    public function __construct(private PaymentVerificationService $service) {}

    public function store(MotifRequest $request, Payment $payment): RedirectResponse
    {
        $verdict = $this->service->verifier($payment, $request->motif());

        $message = 'Vérification effectuée : '.PaymentVerificationService::libelle($verdict).'.';

        // Un paiement toujours en attente n'est pas un échec de l'action :
        // l'action a réussi, c'est la réponse qui ne satisfait pas. On reste
        // donc sur un message d'information, jamais sur une erreur.
        return back()->with('status', $message);
    }
}
