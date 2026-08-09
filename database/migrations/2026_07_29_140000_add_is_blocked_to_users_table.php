<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Suspension d'un compte.
 *
 * Un compte suspendu ne peut plus se connecter, et sa session en cours est
 * détruite à la requête suivante. On ne supprime pas : un litige de paiement
 * se règle, et l'historique comptable doit survivre.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'is_blocked')) {
                $table->boolean('is_blocked')->default(false)->after('role');
                $table->timestamp('blocked_at')->nullable()->after('is_blocked');
                $table->string('blocked_reason')->nullable()->after('blocked_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_blocked', 'blocked_at', 'blocked_reason']);
        });
    }
};
