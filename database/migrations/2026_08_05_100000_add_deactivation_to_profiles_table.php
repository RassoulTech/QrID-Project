<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TROIS ÉTATS DE PROFIL, PAS DEUX.
 *
 * `is_active` seul ne distinguait pas un brouillon jamais publié d'un profil
 * publié puis coupé par l'administration. Les deux valaient false, et la liste
 * des profils affichait « brouillon » pour un compte sanctionné.
 *
 *   publié      · is_active = true
 *   brouillon   · is_active = false, deactivated_at = null
 *   désactivé   · deactivated_at renseigné
 *
 * Le motif est DUPLIQUÉ ici alors qu'il est déjà dans admin_actions. C'est
 * volontaire, et c'est le même choix que users.blocked_reason : la liste des
 * profils affiche le motif de chaque ligne, et aller le chercher dans le
 * journal imposerait une jointure sur une table qui grossit sans limite et
 * dont plusieurs entrées peuvent viser la même cible. admin_actions reste la
 * source d'audit, cette colonne n'est qu'un cache d'affichage.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->timestamp('deactivated_at')->nullable()->after('is_active');
            $table->string('deactivated_reason')->nullable()->after('deactivated_at');

            // La liste des profils filtre par état : sans index, un balayage
            // complet à chaque ouverture de l'écran.
            $table->index('deactivated_at');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropIndex(['deactivated_at']);
            $table->dropColumn(['deactivated_at', 'deactivated_reason']);
        });
    }
};
