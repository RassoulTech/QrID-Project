<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LE LIEN DE LOCALISATION EXACT.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI UN CHAMP, ET NON UNE RECHERCHE DEVINÉE
 * ═══════════════════════════════════════════════════════════════════════
 * « Thiès, Sénégal » lancé dans une recherche cartographique tombe au centre
 * d'une ville de 300 000 habitants. Pour un commerce, c'est inutilisable : le
 * visiteur voulait la boutique, il obtient un quartier.
 *
 * Le porteur qui a une fiche Google Maps colle son lien : le bouton mène alors
 * à SA position, au mètre près. Sans lien, on retombe sur la recherche — moins
 * bien, mais jamais rien.
 *
 * Le champ est FACULTATIF : l'exiger obligerait chaque client à aller chercher
 * une URL avant de publier sa carte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('maps_url', 500)->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn('maps_url');
        });
    }
};
