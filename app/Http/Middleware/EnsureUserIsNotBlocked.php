<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Coupe la session d'un compte suspendu pendant sa navigation.
 *
 * Refuser la connexion ne suffit pas : un compte suspendu alors qu'il est
 * déjà connecté doit être éjecté à la requête suivante, sans attendre
 * l'expiration de sa session.
 */
class EnsureUserIsNotBlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isBlocked()) {
            Log::warning('Session interrompue : compte suspendu.', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'at' => now()->toDateTimeString(),
            ]);

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => trans('auth.blocked')]);
        }

        return $next($request);
    }
}
