<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LA CHAÎNE DE COMMANDE DES CARTES PHYSIQUES.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI UNE TABLE, ET NON DEUX COLONNES SUR LE COMPTE
 * ═══════════════════════════════════════════════════════════════════════
 * Une carte n'est pas un attribut du client : c'est un OBJET qui traverse des
 * états, chez un imprimeur, dans un lot, puis dans un courrier. Chacune de ces
 * étapes porte une date, et c'est la suite de ces dates qui répond à la seule
 * question qui compte quand un client écrit : « où en est ma carte ? »
 *
 * Un remplacement, plus tard, sera une SECONDE ligne — payante et distincte.
 * Deux colonnes sur users ne sauraient pas représenter cela.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * L'ADRESSE EST COPIÉE, PAS RÉFÉRENCÉE
 * ═══════════════════════════════════════════════════════════════════════
 * Le client déménagera. Sa fiche changera. La carte, elle, a été expédiée à
 * une adresse donnée, un jour donné : si l'on pointait vers son profil, le
 * bordereau d'expédition se réécrirait tout seul et l'on ne saurait plus où le
 * colis est parti. Une pièce logistique se fige, comme une pièce comptable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Le profil peut disparaître sans emporter la commande : la carte
            // a été produite, elle existe physiquement.
            $table->foreignId('profile_id')->nullable()->constrained()->nullOnDelete();

            $table->string('status', 20)->default('pending');

            // ─── Adresse de livraison, figée au moment de la commande ───
            $table->string('recipient_name')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('address_line')->nullable();
            $table->string('city', 120)->nullable();
            $table->string('region', 120)->nullable();
            $table->text('delivery_notes')->nullable();

            // Le lot de production. Nul tant que la commande attend.
            $table->string('batch_id', 40)->nullable();

            $table->timestamp('produced_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();

            $table->timestamps();

            /*
             | LES INDEX SUIVENT LES DEUX SEULES LECTURES FRÉQUENTES :
             | « les commandes en attente, les plus anciennes d'abord » —
             | c'est l'écran de production — et « les commandes d'un lot ».
             */
            $table->index(['status', 'created_at']);
            $table->index('batch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_orders');
    }
};
