<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExtendSubscriptionRequest;
use App\Models\User;
use App\Services\Admin\SubscriptionExtensionService;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

/**
 * Prolongation manuelle d'un abonnement — geste commercial tracé.
 */
class SubscriptionExtensionController extends Controller
{
    public function __construct(private SubscriptionExtensionService $service) {}

    public function store(ExtendSubscriptionRequest $request, User $user): RedirectResponse
    {
        try {
            $abonnement = $this->service->prolonger($user, $request->jours(), $request->motif());
        } catch (RuntimeException $e) {
            // Le refus revient dans le champ du formulaire, pas dans une page
            // d'erreur : l'administrateur doit voir sa saisie et la raison au
            // même endroit.
            return back()->withErrors(['motif' => $e->getMessage()])->withInput();
        }

        return back()->with('status', sprintf(
            'Abonnement prolongé de %d jour%s. Nouvelle échéance : %s.',
            $request->jours(),
            $request->jours() > 1 ? 's' : '',
            $abonnement->ends_at?->format('d/m/Y') ?? 'sans terme'
        ));
    }
}
