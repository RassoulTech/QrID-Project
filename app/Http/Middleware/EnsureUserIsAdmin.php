<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Réserve une route à l'administration.
 *
 * 403 et non 404 : l'utilisateur est authentifié, il a le droit de savoir que
 * cette page existe mais ne lui est pas destinée. Masquer n'apporterait rien,
 * la route est publique dans le code.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isAdmin(), 403, trans('auth.not_admin'));

        return $next($request);
    }
}
