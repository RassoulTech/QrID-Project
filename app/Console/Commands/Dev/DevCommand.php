<?php

namespace App\Console\Commands\Dev;

use Illuminate\Console\Command;

/**
 * Base des commandes de développement.
 *
 * Ces commandes court-circuitent des protections applicatives (vérification
 * d'e-mail, jetons signés). Elles REFUSENT catégoriquement de s'exécuter
 * hors de l'environnement local.
 */
abstract class DevCommand extends Command
{
    protected function guardLocal(): bool
    {
        if (! app()->environment('local')) {
            $this->error('Commande de développement : exécution refusée hors de APP_ENV=local.');

            return false;
        }

        return true;
    }
}
