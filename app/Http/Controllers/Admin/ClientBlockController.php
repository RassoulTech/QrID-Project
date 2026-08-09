<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MotifRequest;
use App\Models\User;
use App\Services\Admin\ClientBlockService;
use Illuminate\Http\RedirectResponse;

/**
 * Blocage et déblocage d'un compte client.
 *
 * DEUX GARDES avant toute chose :
 *
 *   · on ne bloque pas un administrateur depuis cet écran. Deux
 *     administrateurs qui se bloquent mutuellement ferment la porte de
 *     l'intérieur, et il faut alors une intervention en base ;
 *   · on ne se bloque pas soi-même. Cela paraît absurde à écrire, et c'est
 *     précisément le genre de clic qui arrive à 19 heures un vendredi.
 */
class ClientBlockController extends Controller
{
    public function __construct(private ClientBlockService $service) {}

    public function store(MotifRequest $request, User $user): RedirectResponse
    {
        if ($refus = $this->refus($request, $user)) {
            return $refus;
        }

        $this->service->bloquer($user, $request->motif());

        return back()->with('status', "Le compte de {$user->name} est bloqué. Ses sessions ont été fermées.");
    }

    public function destroy(MotifRequest $request, User $user): RedirectResponse
    {
        if ($refus = $this->refus($request, $user)) {
            return $refus;
        }

        $this->service->debloquer($user, $request->motif());

        return back()->with('status', "Le compte de {$user->name} est de nouveau actif.");
    }

    private function refus(MotifRequest $request, User $user): ?RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['motif' => 'Vous ne pouvez pas bloquer votre propre compte.']);
        }

        if ($user->isAdmin()) {
            return back()->withErrors(['motif' => 'Un compte administrateur ne se bloque pas depuis cet écran.']);
        }

        return null;
    }
}
