<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Contrôleur de base.
 *
 * Laravel 12 ne fournit plus AuthorizesRequests par défaut : sans ce trait,
 * tout appel à $this->authorize() lève une BadMethodCallException. Les
 * Policies sont notre garde-fou principal, elles doivent être appelables
 * depuis n'importe quel contrôleur.
 */
abstract class Controller
{
    use AuthorizesRequests;
}
