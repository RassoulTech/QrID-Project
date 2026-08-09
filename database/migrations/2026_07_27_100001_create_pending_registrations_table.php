<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Index simple, PAS unique : plusieurs tentatives peuvent transiter
            // sur la même adresse ; la logique applicative invalide l'ancienne.
            $table->string('email')->index();
            $table->string('phone', 20);
            $table->string('password');          // toujours hashé avant insertion
            $table->string('token_hash', 64);    // sha256 du jeton ; jamais le jeton en clair
            $table->timestamp('expires_at');
            $table->string('ip_hash', 64)->nullable(); // sha256(ip + app.key)
            $table->unsignedTinyInteger('resend_count')->default(0);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('token_hash');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_registrations');
    }
};
