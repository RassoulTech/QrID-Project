<?php

namespace Tests\Support;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

/**
 * Contrôle CSRF qui s'applique VRAIMENT pendant les tests.
 *
 * Deux pièges se cumulent ici, et expliquent qu'aucun test de CsrfExpiryTest
 * n'ait jamais rien vérifié :
 *
 * 1. Le middleware du groupe « web » est ValidateCsrfToken. VerifyCsrfToken
 *    n'est plus que son ancêtre, présent pour la compatibilité : agir sur lui
 *    n'a aucun effet sur les requêtes.
 * 2. Le middleware se neutralise lui-même dès que l'application tourne sous
 *    PHPUnit (runningUnitTests()). $this->withMiddleware(...) n'y change rien :
 *    cette méthode ne fait que retirer une liaison du conteneur.
 *
 * À substituer dans le conteneur, jamais utilisé en production :
 *
 *     $this->app->bind(ValidateCsrfToken::class, EnforcedCsrfToken::class);
 */
class EnforcedCsrfToken extends ValidateCsrfToken
{
    protected function runningUnitTests(): bool
    {
        return false;
    }
}
