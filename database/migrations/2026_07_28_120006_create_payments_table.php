<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paiements — PIÈCES COMPTABLES.
 *
 * Règle absolue : un paiement ne disparaît JAMAIS avec la suppression d'un
 * compte. C'est pourquoi user_id et subscription_id sont NULLABLE et en
 * nullOnDelete : la ligne survit, orpheline mais intacte, avec son montant,
 * sa référence fournisseur et son payload.
 *
 *  - user_id         → nullOnDelete : la trace comptable survit au compte.
 *  - subscription_id → nullOnDelete : idem si l'abonnement est purgé.
 *
 * provider_ref unique : clé d'IDEMPOTENCE. Un webhook rejoué ne peut pas
 * créer un doublon de paiement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();

            $table->string('provider', 40);                       // paydunya, cinetpay…
            $table->string('provider_ref')->nullable()->unique();  // idempotence

            $table->enum('method', ['wave', 'orange_money', 'free_money']);

            $table->unsignedInteger('amount_fcfa');                // entier, jamais float
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');

            $table->json('payload')->nullable();                   // réponse brute du fournisseur

            $table->timestamps();
            $table->softDeletes();

            // provider_ref est déjà unique (donc indexé). Index complémentaires :
            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
