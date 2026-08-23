<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * LA BANNIÈRE DE COUVERTURE, CHOISIE PAR LE PORTEUR.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CE QUE REMPLACE CETTE COLONNE
 * ═══════════════════════════════════════════════════════════════════════
 * Le haut de la page publique était un dégradé vert, identique chez tout le
 * monde. Sur une carte de visite, cette bande est l'endroit le plus visible
 * de la page : la première chose qu'on voit après un scan. La laisser
 * inchangée d'un profil à l'autre revient à imprimer la même carte pour
 * tous les clients et à leur demander de la reconnaître.
 *
 * Elle reste FACULTATIVE, et c'est important : personne ne doit être
 * bloqué à la création parce qu'il n'a pas d'image sous la main. Sans
 * couverture, la page rend une bannière composée qui porte le nom du
 * produit — voir x-couverture.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * MÊME DOUBLE STOCKAGE QUE LA PHOTO
 * ═══════════════════════════════════════════════════════════════════════
 * Le disque du conteneur est éphémère : un chemin seul ne survit pas au
 * déploiement. Les octets vont donc en base, le disque n'est qu'un cache.
 * MEDIUMBLOB dès le départ — une bannière est plus large qu'un portrait, et
 * le plafond de 64 Ko d'un BLOB simple a déjà coûté une correction.
 *
 * SQLite ignore les sous-types de BLOB : la conversion ne le concerne pas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('cover_path')->nullable()->after('photo_data');
            $table->binary('cover_data')->nullable()->after('cover_path');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE profiles MODIFY cover_data MEDIUMBLOB NULL');
        }
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['cover_path', 'cover_data']);
        });
    }
};
