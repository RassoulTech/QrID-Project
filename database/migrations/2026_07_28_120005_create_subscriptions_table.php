<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Abonnements. Détermine la visibilité publique d'un profil.
 *
 * Clés étrangères :
 *  - user_id → cascadeOnDelete   : sans compte, l'abonnement n'a plus d'objet
 *                                  (les paiements, eux, survivent : voir payments).
 *  - plan_id → restrictOnDelete  : on ne supprime JAMAIS une formule référencée ;
 *                                  on la désactive via is_active. Sinon on perdrait
 *                                  la trace de ce que le client a réellement souscrit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->enum('status', ['pending', 'active', 'expired', 'cancelled'])
                ->default('pending');

            $table->timestamps();

            $table->index(['user_id', 'status']);   // index obligatoire
            $table->index(['status', 'ends_at']);   // relances J-7 / J-3 / J-1 / J
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
