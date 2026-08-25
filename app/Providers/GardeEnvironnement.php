<?php

namespace App\Providers;

use Illuminate\Database\Console\Migrations\FreshCommand;
use Illuminate\Database\Console\WipeCommand;
use Illuminate\Support\ServiceProvider;

/**
 * LE GARDE-FOU DES COMMANDES DESTRUCTRICES.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CE QU'IL EMPÊCHE, ET POURQUOI --force NE SUFFIT PAS
 * ═══════════════════════════════════════════════════════════════════════
 * `migrate:fresh` supprime TOUTES les tables puis les recrée. En local,
 * c'est le geste ordinaire d'un développeur. En production, c'est la perte
 * de tous les comptes clients, de tous les profils, de tous les paiements.
 *
 * Laravel demande bien une confirmation hors environnement local — mais
 * `--force` la lève, et `--force` est présent dans presque toutes les
 * lignes de commande de déploiement, y compris dans notre entrypoint
 * Docker, où il sert légitimement à `migrate`.
 *
 * Une faute de frappe entre `migrate` et `migrate:fresh` dans un fichier
 * qui porte déjà `--force` suffirait donc à vider la base de production
 * sans qu'aucune question ne soit posée.
 *
 * ICI, LA QUESTION N'EST PLUS POSÉE : LA COMMANDE REFUSE. Le seul moyen de
 * la lever est de changer APP_ENV, ce qui ne se fait pas par accident.
 *
 * MÊME TRAITEMENT POUR db:wipe, qui fait la même chose sans les migrations.
 */
class GardeEnvironnement extends ServiceProvider
{
    public function boot(): void
    {
        foreach ([FreshCommand::class, WipeCommand::class] as $commande) {
            $this->app->resolving($commande, function ($instance) {
                if ($this->app->environment('local', 'testing')) {
                    return;
                }

                throw new \RuntimeException(
                    'REFUS : cette commande détruit toutes les tables et ne peut '
                    ."s'exécuter que dans l'environnement local. "
                    .'Environnement courant : '.$this->app->environment().'. '
                    ."Si l'intention est bien de repartir de zéro en production, "
                    .'le faire explicitement depuis la console de la base.'
                );
            });
        }
    }
}
