<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LA LANGUE DU COMPTE.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI EN BASE, ET PAS SEULEMENT EN SESSION
 * ═══════════════════════════════════════════════════════════════════════
 * Un visiteur choisit sa langue pour une visite : un cookie suffit. Un CLIENT
 * la choisit une fois pour toutes, et s'attend à la retrouver depuis son
 * téléphone comme depuis son ordinateur.
 *
 * Surtout : les e-mails partent HORS SESSION. Un rappel d'échéance, un reçu de
 * paiement, une alerte — aucun de ces messages n'a de cookie à consulter. Sans
 * cette colonne, ils partiraient tous en français, y compris à qui a choisi
 * l'anglais dans l'interface.
 *
 * Exactement le même raisonnement que pour le thème, une colonne plus tôt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 5)->default('fr')->after('theme');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }
};
