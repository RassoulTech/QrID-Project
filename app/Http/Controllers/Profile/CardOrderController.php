<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\AdresseLivraisonRequest;
use App\Models\CardOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * « MA CARTE PHYSIQUE » — côté client.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * L'ADRESSE SE CORRIGE TANT QUE RIEN N'EST PARTI
 * ═══════════════════════════════════════════════════════════════════════
 * Une adresse mal saisie est la première cause de colis perdu, et le client
 * s'en aperçoit souvent le lendemain. Tant que la commande attend, il corrige
 * lui-même. Dès qu'elle part chez l'imprimeur, le bordereau est figé : laisser
 * modifier après coup ferait croire que le colis suivra, alors qu'il est déjà
 * adressé ailleurs.
 */
class CardOrderController extends Controller
{
    public function edit(Request $request): View|RedirectResponse
    {
        $commande = $this->commande($request);

        if (! $commande) {
            return redirect()->route('dashboard')->with(
                'info',
                __('profile.flash.carte_physique_apres_paiement')
            );
        }

        return view('carte-physique.adresse', [
            'commande' => $commande,
            'delai' => config('cartes.delai_jours'),
        ]);
    }

    public function update(AdresseLivraisonRequest $request): RedirectResponse
    {
        $commande = $this->commande($request);

        if (! $commande) {
            return redirect()->route('dashboard');
        }

        /*
         | LA GARDE EST SERVEUR, PAS SEULEMENT À L'ÉCRAN.
         |
         | Le formulaire n'est pas affiché quand la commande est partie — mais
         | un onglet resté ouvert, un bouton précédent, ou une requête forgée
         | contourneraient l'écran. On revérifie ici : c'est le seul endroit
         | qui fasse foi.
         */
        if (! $commande->adresseModifiable()) {
            return back()->with(
                'warning',
                'Votre carte est déjà en production : l’adresse ne peut plus être modifiée. Écrivez-nous si elle est erronée.'
            );
        }

        $commande->update($request->livraison());

        return redirect()->route('dashboard')->with(
            'success',
            'Adresse de livraison enregistrée. Votre carte part à la prochaine production.'
        );
    }

    /** La commande en cours du client, s'il en a une. */
    private function commande(Request $request): ?CardOrder
    {
        return CardOrder::where('user_id', $request->user()->id)
            ->whereNot('status', CardOrder::STATUS_CANCELLED)
            ->latest('id')
            ->first();
    }
}
