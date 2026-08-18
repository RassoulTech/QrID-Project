<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LA CARTE PVC EST OFFERTE UNE SEULE FOIS.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI UN INDICATEUR, ET NON UN CALCUL
 * ═══════════════════════════════════════════════════════════════════════
 * On pourrait déduire le droit à la carte en comptant les paiements réussis.
 * Ce serait faux dès le premier remboursement, le premier paiement annulé à
 * la main, ou le premier compte migré depuis une autre grille tarifaire — et
 * l'erreur coûterait une carte physique imprimée et expédiée.
 *
 * Un fait aussi coûteux se CONSTATE et se pose une fois. Il ne se recalcule
 * pas à chaque affichage.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CE QUE LA DATE APPORTE EN PLUS DU BOOLÉEN
 * ═══════════════════════════════════════════════════════════════════════
 * « Depuis quand » répond seul à la question qui viendra : ce client
 * attend-il sa carte depuis trois jours ou depuis six semaines ? Un booléen
 * seul obligerait à croiser avec la table des paiements pour le savoir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('physical_card_granted_at')
                ->nullable()
                ->after('theme');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('physical_card_granted_at');
        });
    }
};
