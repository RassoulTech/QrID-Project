<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Remplace le middleware « verified » du framework.
 *
 * POURQUOI ne pas utiliser EnsureEmailIsVerified : il redirige vers
 * route('verification.notice'), qui n'existe PAS ici — l'inscription en
 * double opt-in ne crée un compte qu'après confirmation, il n'y a donc aucun
 * écran « vérifiez votre e-mail ». Le middleware du framework lèverait une
 * RouteNotFoundException, soit une 500.
 *
 * Un compte non vérifié est un ÉTAT IMPOSSIBLE dans cette architecture. S'il
 * survient, c'est un bug : on le journalise en erreur, on ferme la session, et
 * on renvoie sur la connexion. Jamais de page blanche.
 */
class EnsureAccountIsUsable
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->email_verified_at === null) {
            Log::error('État impossible : compte authentifié sans e-mail vérifié.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'created_at' => $user->created_at?->toDateTimeString(),
                'route' => $request->route()?->getName(),
                'at' => now()->toDateTimeString(),
            ]);

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => __('auth.flash.compte_incomplet'),
            ]);
        }

        return $next($request);
    }
}
