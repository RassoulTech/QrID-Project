<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Brouillon du parcours de création de profil.
 *
 * POURQUOI UNE TABLE, alors que l'avancement vit déjà en session.
 *
 * La déconnexion détruit la session — invalidate() puis regenerateToken(),
 * c'est la parade contre la fixation de session, on n'y touche pas. Or un
 * utilisateur qui se déconnecte au milieu du parcours doit retrouver sa saisie
 * en revenant : sans cette table, tout était perdu, et il fallait tout ressaisir.
 *
 * La session reste le chemin rapide ; cette table est la mémoire durable.
 * Elle ne contient QUE des données déjà validées par un Form Request, jamais
 * une saisie brute.
 *
 * Table dédiée plutôt qu'une colonne JSON sur users : c'est de l'état
 * temporaire, avec son propre cycle de vie et sa propre purge — exactement
 * comme pending_registrations, dont elle reprend la logique.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_drafts', function (Blueprint $table) {
            $table->id();

            // Un seul brouillon par compte : le parcours est unique.
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            // Copie fidèle de l'état de session : data, completed, editing.
            $table->json('state');

            // Première étape non franchie. Dupliqué hors du JSON pour que le
            // tableau de bord puisse proposer « reprendre » sans désérialiser.
            $table->unsignedTinyInteger('next_step')->default(1);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_drafts');
    }
};
