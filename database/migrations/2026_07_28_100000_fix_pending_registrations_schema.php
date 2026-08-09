<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Corrige pending_registrations dont l'ancienne version (telephone/token) a été
 * créée avant la réécriture du schéma. On recrée la table au bon format.
 *
 * Sécurité : n'agit QUE si la colonne `phone` manque ET que la table est vide.
 * Si des données existent (production), on interrompt avec un message explicite
 * pour forcer l'écriture d'une migration de reprise de données.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('pending_registrations', 'phone')) {
            return; // déjà au bon format (ex. après migrate:fresh) — rien à faire.
        }

        $count = DB::table('pending_registrations')->count();

        if ($count > 0) {
            throw new RuntimeException(
                "pending_registrations contient {$count} ligne(s) : recréation refusée. ".
                'Écris une migration de reprise de données plutôt que ce raccourci de dev.'
            );
        }

        Schema::dropIfExists('pending_registrations');

        Schema::create('pending_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->index();
            $table->string('phone', 20);
            $table->string('password');
            $table->string('token_hash', 64);
            $table->timestamp('expires_at');
            $table->string('ip_hash', 64)->nullable();
            $table->unsignedTinyInteger('resend_count')->default(0);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('token_hash');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        // Pas de retour arrière : on ne restaure pas un schéma erroné.
    }
};
