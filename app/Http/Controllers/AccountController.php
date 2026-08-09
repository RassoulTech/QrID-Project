<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccountUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

/**
 * LE COMPTE — table users : identifiants d'accès (nom, e-mail, téléphone,
 * mot de passe) et suppression du compte.
 *
 * À NE PAS CONFONDRE avec le PROFIL professionnel (table profiles), géré par
 * App\Http\Controllers\Profile\ProfileWizardController.
 */
class AccountController extends Controller
{
    /** Formulaire des informations du compte. */
    public function edit(Request $request): View
    {
        return view('account.edit', [
            'user' => $request->user(),
        ]);
    }

    /** Mise à jour des informations du compte. */
    public function update(AccountUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        // Un changement d'adresse invalide la vérification précédente.
        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('compte.edit')->with('status', 'account-updated');
    }

    /** Suppression définitive du compte. */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
