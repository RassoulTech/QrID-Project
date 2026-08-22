<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LA PHOTO DE PROFIL, RENDUE DURABLE.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LE DISQUE DE RENDER EST ÉPHÉMÈRE, ET C'EST DÉFINITIF
 * ═══════════════════════════════════════════════════════════════════════
 * Chaque déploiement remplace le conteneur : storage/app/public repart vide.
 * La colonne photo_path survivait, le fichier non — la page publique affichait
 * alors les initiales, et le client concluait que sa photo n'avait jamais été
 * enregistrée.
 *
 * Constaté trois fois en production le 18 août, sur la même journée.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI LA BASE, ET NON UN STOCKAGE OBJET
 * ═══════════════════════════════════════════════════════════════════════
 * Un stockage objet — S3, R2 — est la réponse manuelle, et elle reste la
 * meilleure à grande échelle. Mais elle suppose un contrat, des identifiants
 * et une décision de budget : trois choses qu'un correctif ne peut pas se
 * procurer, et le produit ouvre dans deux jours.
 *
 * Une photo de profil est recadrée en carré et compressée en JPEG : elle pèse
 * quelques dizaines de kilo-octets. Mille clients tiennent dans quelques
 * dizaines de méga-octets — sans commune mesure avec le coût d'une photo
 * perdue à chaque mise en ligne.
 *
 * LE DISQUE RESTE, EN CACHE. On sert le fichier quand il est là, on le
 * réécrit depuis la base quand il manque. La base est la source de vérité,
 * le disque n'est plus qu'une commodité.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            // binary → BLOB sur MySQL, BLOB sur SQLite. Nullable : les profils
            // sans photo n'occupent rien.
            $table->binary('photo_data')->nullable()->after('photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn('photo_data');
        });
    }
};
