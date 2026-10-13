<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * LE PARTAGE DEVIENT ENREGISTRABLE SOUS POSTGRESQL.
 *
 * `profile_events.type` est né `enum('view','scan','save')`. Sous
 * PostgreSQL, Laravel traduit une énumération par une colonne texte ET une
 * contrainte CHECK nommée profile_events_type_check.
 *
 * La migration add_share_tracking_to_events a transformé la colonne en
 * chaîne libre pour accueillir « share ». MySQL abandonne l'énumération avec
 * le type ; PostgreSQL change le type mais GARDE la contrainte. Résultat :
 * chaque partage levait « violates check constraint », la carte répondait
 * en erreur, et les compteurs de partage restaient à zéro.
 *
 * On retire donc la contrainte, ce qui aligne PostgreSQL sur MySQL et
 * SQLite : la liste des types vit dans ProfileEvent et dans la validation,
 * comme l'avait décidé la migration d'origine.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE profile_events DROP CONSTRAINT IF EXISTS profile_events_type_check');
    }

    public function down(): void
    {
        // Rien à rétablir : la contrainte refuserait les partages déjà écrits.
    }
};
