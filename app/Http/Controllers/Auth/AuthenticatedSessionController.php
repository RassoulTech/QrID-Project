<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\HomeRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Connexion.
     *
     * regenerate() change l'identifiant de session : c'est la parade contre la
     * fixation de session, et elle doit venir APRÈS l'authentification.
     *
     * intended() ramène l'utilisateur là où il voulait aller avant d'être
     * intercepté par le middleware ; à défaut, chacun rejoint son accueil.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(HomeRedirect::for($request->user()));
    }

    /**
     * Déconnexion.
     *
     * Les trois opérations sont nécessaires et dans cet ordre : oublier
     * l'utilisateur, détruire les données de session, puis renouveler le jeton
     * CSRF — sans quoi le formulaire de connexion qui suit repartirait avec un
     * jeton périmé et provoquerait un 419.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
