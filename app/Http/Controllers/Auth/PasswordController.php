<?php

namespace App\Http\Controllers\Auth;

use App\Events\PasswordChanged;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Changement de mot de passe depuis les paramètres du compte.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        /*
         | ALERTE DE SÉCURITÉ — elle part MÊME ICI, où l'utilisateur vient de
         | saisir son mot de passe actuel et est donc légitime au-delà du doute.
         |
         | Ce n'est pas une redondance. Quelqu'un qui a dérobé une session
         | ouverte connaît lui aussi le mot de passe actuel s'il l'a intercepté,
         | et c'est précisément le scénario où le titulaire n'apprendra rien
         | autrement. Un e-mail de trop chez l'utilisateur légitime coûte deux
         | secondes ; celui qui manque coûte le compte.
         */
        event(new PasswordChanged($request->user(), $request->ip()));

        return back()->with('status', 'password-updated');
    }
}
