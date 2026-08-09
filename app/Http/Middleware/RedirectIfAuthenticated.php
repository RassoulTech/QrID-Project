<?php

namespace App\Http\Middleware;

use App\Support\HomeRedirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Un utilisateur déjà connecté n'a rien à faire sur /login ou /register.
 *
 * On remplace le middleware du framework pour envoyer l'administrateur vers
 * SON écran d'accueil, et non vers le tableau de bord client.
 */
class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        foreach ($guards ?: [null] as $guard) {
            if (Auth::guard($guard)->check()) {
                return redirect()->to(HomeRedirect::for(Auth::guard($guard)->user()));
            }
        }

        return $next($request);
    }
}
