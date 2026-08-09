<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Profil professionnel public. Cœur du produit.
 *
 * Clés étrangères :
 *  - user_id     → cascadeOnDelete : un profil n'a aucun sens sans son propriétaire.
 *  - template_id → nullOnDelete    : retirer un modèle ne doit jamais détruire
 *                                    les profils qui l'utilisaient (repli sur le défaut).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('slug')->unique();

            $table->string('first_name');
            $table->string('last_name');
            $table->string('job_title')->nullable();
            $table->string('company')->nullable();
            $table->text('bio')->nullable();

            // Numéros au format canonique +221XXXXXXXXX (voir App\Rules\SenegalPhone).
            $table->string('phone', 20)->nullable();
            $table->string('whatsapp', 20)->nullable();

            $table->string('public_email')->nullable();
            $table->string('website')->nullable();
            $table->string('address')->nullable();
            $table->string('photo_path')->nullable();

            $table->foreignId('template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('primary_color', 7)->nullable(); // #RRGGBB

            $table->boolean('is_active')->default(false);   // publié ou brouillon
            $table->timestamp('slug_changed_at')->nullable(); // slug modifiable une seule fois

            $table->timestamps();
            $table->softDeletes();

            // slug est déjà unique (donc indexé) ; index complémentaires utiles :
            $table->index(['user_id', 'is_active']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
