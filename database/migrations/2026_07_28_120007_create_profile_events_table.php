<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Événements de consultation d'un profil (vue, scan de QR Code, enregistrement).
 * Table à forte volumétrie : pas d'updated_at, index composé pour les
 * statistiques par période.
 *
 * profile_id → cascadeOnDelete : les statistiques d'un profil supprimé n'ont
 * plus d'objet, et ce ne sont pas des pièces comptables.
 *
 * ip_hash = sha256(ip + app.key) : jamais d'adresse IP en clair.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();

            $table->enum('type', ['view', 'scan', 'save']);

            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('referer', 512)->nullable();

            $table->timestamp('created_at')->nullable();

            $table->index(['profile_id', 'created_at']); // index obligatoire
            $table->index(['profile_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_events');
    }
};
