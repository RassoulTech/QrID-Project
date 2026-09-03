<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LA PHOTO DE COMPTE — celle qui remplace les initiales.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ELLE N'EST PAS LA PHOTO DE LA CARTE, ET LA DISTINCTION COMPTE
 * ═══════════════════════════════════════════════════════════════════════════
 * La carte publique a UNE image : la couverture, choisie dans l'assistant,
 * vue par les prospects. Celle-ci est autre chose — l'avatar de l'espace
 * client, en haut à droite de chaque écran, que personne d'autre ne voit.
 *
 * Les confondre serait une erreur de produit : quelqu'un peut vouloir un
 * bandeau soigné sur sa carte commerciale et sa propre tête dans son espace,
 * ou l'inverse. Le produit affiche aujourd'hui ses initiales — « MD », « AD »
 * — ce qui est un repli correct, mais un repli.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * MÊME MÉCANIQUE QUE LA COUVERTURE : LE CHEMIN ET LES OCTETS
 * ═══════════════════════════════════════════════════════════════════════════
 * Le disque du conteneur est recréé à chaque déploiement. Un avatar qui n'y
 * vivrait que sur le disque disparaîtrait à la première mise en ligne, et le
 * client conclurait que le produit ne sait pas garder une image.
 *
 * Le chemin sert de cache, les octets font foi — exactement le dispositif
 * déjà éprouvé pour `cover_data`, et pour la même raison.
 *
 * `google_avatar` reste à part : c'est une ADRESSE chez Google, pas une image
 * qu'on détient. Elle sert de repli quand le compte vient de Google et que
 * rien n'a été importé.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_path')->nullable()->after('google_avatar');

            // MEDIUMBLOB comme la couverture : un avatar recadré tient en
            // quelques dizaines de kilooctets, la marge est là pour les
            // replis où l'image d'origine passe telle quelle.
            $table->binary('avatar_data')->nullable()->after('avatar_path');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar_path', 'avatar_data']);
        });
    }
};
