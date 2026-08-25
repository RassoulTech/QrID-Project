<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LES DEUX INDEX COMPOSITES QUI MANQUAIENT.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CE QUI EXISTAIT DÉJÀ, ET POURQUOI ÇA NE SUFFISAIT PAS
 * ═══════════════════════════════════════════════════════════════════════
 * L'audit du schéma a montré une base bien indexée : les quatorze clés
 * étrangères sont couvertes, `profiles.slug` est unique, comme
 * `payments.provider_ref`, et `payments(status, created_at)` existe.
 *
 * Il manquait exactement deux choses, et ce sont des index à TROIS
 * colonnes. Un index à deux colonnes sert une requête qui filtre sur les
 * deux premières ; dès qu'une troisième condition s'ajoute, MySQL ne peut
 * plus l'utiliser que partiellement et filtre le reste ligne à ligne.
 *
 *   profile_events  (profile_id, type)  et  (profile_id, created_at)
 *       existent, mais les statistiques d'un profil filtrent sur les TROIS :
 *       « les scans de ce profil depuis trente jours ».
 *
 *   subscriptions   (user_id, status)   et  (status, ends_at)
 *       existent, mais la recherche d'abonnement actif filtre sur les
 *       trois : « l'abonnement actif de ce compte qui n'a pas expiré ».
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ET LES ANCIENS ? ON LES GARDE
 * ═══════════════════════════════════════════════════════════════════════
 * Un index à trois colonnes sert aussi les requêtes qui n'en filtrent que
 * les deux premières : (profile_id, type, created_at) couvre donc
 * (profile_id, type). Le retirer serait tentant.
 *
 * On ne le fait pas ici. Ces tables sont écrites à chaque scan, et un index
 * de moins ne se mesure qu'en microsecondes d'écriture — tandis qu'un index
 * retiré par erreur se mesure en secondes de lecture. La question mérite
 * d'être posée quand le volume d'écriture le justifiera, pas avant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profile_events', function (Blueprint $table) {
            $table->index(['profile_id', 'type', 'created_at'], 'pe_profil_type_date');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'ends_at'], 'sub_user_statut_fin');
        });
    }

    public function down(): void
    {
        Schema::table('profile_events', function (Blueprint $table) {
            $table->dropIndex('pe_profil_type_date');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex('sub_user_statut_fin');
        });
    }
};
