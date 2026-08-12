<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Demande d'un lien de réinitialisation.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        /*
         | UNE PANNE D'ENVOI NE DOIT PAS DONNER UNE ERREUR 500.
         |
         | L'envoi se fait pendant cette requête — sans worker, c'est la seule
         | manière d'obtenir un e-mail qui parte réellement. Mais si le
         | fournisseur refuse, l'exception remontait jusqu'à la page « Une
         | erreur est survenue », qui n'apprend rien et fait croire que le
         | formulaire lui-même est cassé.
         |
         | On ne masque pas l'échec pour autant : dire « lien envoyé » quand
         | rien n'est parti serait le mensonge qui nous a coûté plusieurs
         | jours. Le message nomme le problème, et l'erreur exacte est
         | enregistrée dans mail_logs — visible sur l'écran « État système ».
         */
        try {
            $status = Password::sendResetLink($request->only('email'));
        } catch (\Throwable $e) {
            Log::error('Envoi du lien de réinitialisation impossible', [
                'email' => $request->input('email'),
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => "L'envoi de l'e-mail a échoué. Ce n'est pas votre saisie : "
                        .'le service de messagerie ne répond pas. Réessayez dans un instant, '
                        .'ou contactez le support si le problème persiste.',
                ]);
        }

        return $status == Password::RESET_LINK_SENT
                    ? back()->with('status', __($status))
                    : back()->withInput($request->only('email'))
                        ->withErrors(['email' => __($status)]);
    }
}
