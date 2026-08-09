<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Réseaux sociaux d'un profil, ordonnés par « position ».
 *
 * profile_id → cascadeOnDelete : un lien social n'existe que par son profil.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_links', function (Blueprint $table) {
            $table->id();

            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();

            $table->string('platform', 40);  // linkedin, facebook, instagram, tiktok…
            $table->string('url');
            $table->unsignedTinyInteger('position')->default(0);

            $table->timestamps();

            $table->index(['profile_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_links');
    }
};
