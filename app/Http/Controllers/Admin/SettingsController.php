<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Paramètres de la plateforme — trois onglets, un seul écran.
 *
 * SEUL L'ONGLET « PLANS TARIFAIRES » EST FONCTIONNEL. Les deux autres —
 * paramètres généraux, sécurité — n'ont aucun réglage derrière eux dans ce
 * produit : ni table de configuration, ni option modifiable à chaud. Les
 * afficher avec des champs qui n'écrivent nulle part serait exactement le
 * genre de faux écran que ce produit s'interdit. Ils apparaissent donc, comme
 * dans la maquette, avec un état vide qui dit ce qu'il en est.
 *
 * COMPTE DES REQUÊTES — 2 : la liste des formules et le plan sélectionné.
 */
class SettingsController extends Controller
{
    public const ONGLETS = [
        'plans' => 'admin.parametres.onglets.plans',
        'general' => 'admin.parametres.onglets.general',
        'securite' => 'admin.parametres.onglets.securite',
    ];

    public function index(Request $request): View
    {
        return $this->rendu(
            $request,
            // À l'ouverture, le premier plan est sélectionné : un éditeur vide
            // à droite laisserait croire à un écran cassé.
            Plan::query()->orderBy('price_fcfa')->first()
        );
    }

    /** Même écran, plan choisi imposé par l'URL — donc partageable. */
    public function plan(Request $request, Plan $plan): View
    {
        return $this->rendu($request, $plan);
    }

    private function rendu(Request $request, ?Plan $selection): View
    {
        $onglet = array_key_exists($request->query('onglet'), self::ONGLETS)
            ? $request->query('onglet')
            : 'plans';

        return view('admin.settings.index', [
            'onglet' => $onglet,
            'onglets' => self::ONGLETS,
            'plans' => Plan::query()->orderBy('price_fcfa')->get(),
            'selection' => $selection,
            'periodicites' => Plan::PERIODICITES,
        ]);
    }
}
