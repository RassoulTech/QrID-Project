<?php

namespace Tests\Feature;

use Illuminate\Database\Console\Migrations\FreshCommand;
use PHPUnit\Framework\Attributes\DataProvider;
use Illuminate\Database\Console\WipeCommand;
use Tests\TestCase;

/**
 * LE GARDE-FOU DES COMMANDES DESTRUCTRICES.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI CE TEST EXISTE
 * ═══════════════════════════════════════════════════════════════════════
 * `migrate:fresh` supprime toutes les tables. Laravel demande bien une
 * confirmation hors environnement local — mais `--force` la lève, et
 * `--force` figure dans presque toutes les lignes de commande de
 * déploiement, y compris notre entrypoint Docker où il sert légitimement à
 * `migrate`.
 *
 * Une faute de frappe entre `migrate` et `migrate:fresh` dans un fichier qui
 * porte déjà `--force` viderait la production sans qu'aucune question soit
 * posée. Ce test verrouille le refus.
 */
class GardeEnvironnementTest extends TestCase
{
    /** @return array<string, array{class-string}> */
    public static function commandesDestructrices(): array
    {
        return [
            'migrate:fresh' => [FreshCommand::class],
            'db:wipe' => [WipeCommand::class],
        ];
    }

    #[DataProvider('commandesDestructrices')]
    public function test_it_refuses_outside_local(string $commande): void
    {
        app()['env'] = 'production';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/REFUS/');

        app()->make($commande);
    }

    #[DataProvider('commandesDestructrices')]
    public function test_it_allows_local_and_testing(string $commande): void
    {
        // La suite tourne en « testing » : la commande doit se résoudre.
        $this->assertNotNull(app()->make($commande));
    }
}
