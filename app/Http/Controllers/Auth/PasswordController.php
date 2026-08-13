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
        /*
         | LE MOT DE PASSE ACTUEL N'EST EXIGÉ QUE S'IL EXISTE.
         |
         | Un compte créé par Google n'en a pas. Le réclamer quand même est une
         | impasse : la règle `current_password` ne peut jamais passer sur un
         | mot de passe nul, et l'écran refuserait indéfiniment sans expliquer
         | pourquoi.
         |
         | CE N'EST PAS UN ASSOUPLISSEMENT DE SÉCURITÉ. La question posée par
         | `current_password` est « êtes-vous bien le titulaire ? » ; sur un
         | compte sans mot de passe, la réponse a déjà été donnée par Google,
         | et la session en cours ne peut avoir été ouverte autrement.
         |
         | Le compte ne perd rien pour autant : Google continue de fonctionner,
         | et l'alerte de sécurité part dans les deux cas.
         */
        $regles = ['password' => ['required', Password::defaults(), 'confirmed']];

        if ($request->user()->hasPassword()) {
            $regles['current_password'] = ['required', 'current_password'];
        }

        $validated = $request->validateWithBag('updatePassword', $regles);

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
