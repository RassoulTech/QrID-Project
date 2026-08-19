<?php

namespace App\Http\Middleware;

use App\Support\Langue;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * APPLIQUE LA LANGUE AVANT LE PREMIER RENDU.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CÔTÉ SERVEUR, ET AUCUN CLIGNOTEMENT
 * ═══════════════════════════════════════════════════════════════════════
 * Une bascule faite en JavaScript après affichage laisse voir la page dans
 * l'ancienne langue pendant un instant, puis la retraduit sous les yeux. Sur
 * une 3G, cet instant dure une seconde entière.
 *
 * La langue est donc posée AVANT que la moindre vue ne soit rendue, et le HTML
 * servi est déjà dans la bonne. C'est aussi ce qui la rend utilisable sans
 * JavaScript.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale(Langue::courante());

        return $next($request);
    }
}
