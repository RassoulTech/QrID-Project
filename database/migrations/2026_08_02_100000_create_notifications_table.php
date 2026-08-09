<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notifications du client — événements RÉELS de son compte.
 *
 * Table propre au produit plutôt que celle du framework : la table
 * `notifications` de Laravel stocke un blob JSON sérialisé, illisible en SQL
 * et impossible à filtrer proprement. Ici chaque colonne a un sens, on peut
 * compter les non-lues d'une requête et purger par type.
 *
 * Une notification pointe TOUJOURS vers une page du produit (`url`) : une
 * alerte sur laquelle on ne peut pas agir n'a aucune raison d'exister.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // paiement_valide · abonnement_expire_bientot · premiere_vue · contact_enregistre
            $table->string('type', 40);

            $table->string('title', 120);
            $table->string('body', 255)->nullable();
            $table->string('url', 255)->nullable();

            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Le compteur de non-lues est lu à CHAQUE page de l'espace client :
            // il doit se résoudre sur index, jamais par un balayage de table.
            $table->index(['user_id', 'read_at']);

            /*
             | Idempotence : une même notification ne doit pas se répéter à
             | chaque requête. « premiere_vue » n'arrive qu'une fois, et une
             | alerte d'expiration ne doit pas revenir toutes les cinq minutes.
             | La clé unique porte sur le couple compte + type + jour.
             */
            $table->string('cle_unicite', 120)->nullable();
            $table->unique(['user_id', 'cle_unicite']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
