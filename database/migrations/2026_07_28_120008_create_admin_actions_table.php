<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal des actions sensibles de l'administration (auteur + horodatage).
 *
 * admin_id → nullOnDelete : le journal doit SURVIVRE à la suppression du
 * compte administrateur. Un journal d'audit qui s'efface avec son auteur
 * ne vaut rien.
 *
 * target_type / target_id : référence polymorphe libre (App\Models\User#42),
 * volontairement sans contrainte FK pour ne jamais bloquer une suppression
 * ni perdre la trace d'une cible disparue.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_actions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('admin_id')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->string('action', 80);              // suspend_profile, refund_payment…
            $table->string('target_type')->nullable(); // App\Models\Profile
            $table->unsignedBigInteger('target_id')->nullable();
            $table->text('reason')->nullable();

            $table->timestamp('created_at')->nullable();

            $table->index(['target_type', 'target_id']);
            $table->index(['admin_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_actions');
    }
};
