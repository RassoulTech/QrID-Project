<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modèle par défaut — celui proposé à l'étape 2 du parcours de création.
 *
 * La maquette « Gestion des modèles » porte un badge « Par défaut » et une
 * action « Définir par défaut ». Rien dans le schéma ne permettait de le
 * marquer : le parcours prenait le premier modèle actif rencontré, ce qui
 * dépendait de l'ordre d'insertion.
 *
 * L'unicité n'est PAS garantie par un index unique : `is_default = false`
 * serait alors limité à une seule ligne sur MySQL. C'est le service qui
 * remet les autres à false, dans une transaction.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->after('is_active');
            $table->index('is_default');
        });
    }

    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->dropIndex(['is_default']);
            $table->dropColumn('is_default');
        });
    }
};
