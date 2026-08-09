<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Préférence de thème, clair ou sombre.
 *
 * EN BASE, et non dans un cookie ou dans localStorage : la classe qui pilote
 * le thème est posée sur <html> par le SERVEUR, au premier octet de la
 * réponse. C'est ce qui évite l'éclair blanc au chargement d'une page sombre,
 * et c'est ce qui fait fonctionner la bascule sans une ligne de JavaScript.
 *
 * Un cookie aurait suffi techniquement, mais la préférence suivrait le
 * navigateur au lieu de suivre la personne.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('theme', 10)->default('light')->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('theme');
        });
    }
};
