<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * L'EXTENSION unaccent — pour que la recherche ignore les accents.
 *
 * MySQL les ignorait de lui-même, par sa collation utf8mb4_unicode_ci.
 * PostgreSQL ne le fait que si on le lui demande : whereLikeSouple appelle
 * unaccent() des deux côtés de la comparaison (voir AppServiceProvider).
 *
 * Sans cette extension, toute recherche de l'administration lèverait
 * « function unaccent(character varying) does not exist ». L'erreur serait
 * au moins visible — mais à la première recherche, pas au déploiement.
 *
 * unaccent fait partie des extensions livrées avec PostgreSQL et autorisées
 * chez Neon : aucun droit de super-utilisateur n'est nécessaire.
 *
 * MySQL et SQLite ne sont pas concernés : la migration s'abstient.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS unaccent');
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP EXTENSION IF EXISTS unaccent');
    }
};
