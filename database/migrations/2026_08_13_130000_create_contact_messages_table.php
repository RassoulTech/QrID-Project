<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LES MESSAGES DU FORMULAIRE DE CONTACT SONT ÉCRITS EN BASE.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI NE PAS SE CONTENTER DE L'E-MAIL
 * ═══════════════════════════════════════════════════════════════════════
 * Un formulaire de contact qui se contente d'envoyer un e-mail perd le
 * message dès que l'envoi échoue. Or l'envoi a échoué pendant trois jours sur
 * ce projet, en production, sans que rien ne le signale.
 *
 * Et un message perdu ici n'est pas un désagrément : c'est un client qui a
 * pris la peine d'écrire, qui n'aura aucune réponse, et qui n'a aucun moyen
 * de savoir que sa demande s'est évaporée. Il en conclura que personne ne
 * répond.
 *
 * L'ORDRE EST DONC : écrire d'abord, notifier ensuite. La base est la source
 * de vérité, l'e-mail n'est qu'une alerte. Si l'alerte se perd, le message
 * reste.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CE QUI N'EST PAS STOCKÉ
 * ═══════════════════════════════════════════════════════════════════════
 * L'adresse IP en clair. Elle n'aiderait à rien — on ne bannit personne à la
 * main sur ce produit — et constituerait une donnée personnelle de plus à
 * protéger. Le contrôle de cadence se fait par le limiteur de requêtes, qui
 * n'a besoin de rien persister.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('email')->index();
            $table->string('phone')->nullable();
            $table->string('subject');
            $table->text('message');

            /*
             | Rattachement au compte, s'il y en a un.
             |
             | nullOnDelete : un message reste lisible après la suppression du
             | compte. C'est souvent à ce moment-là qu'on a besoin de le
             | relire — quelqu'un qui écrit puis supprime son compte a dit
             | quelque chose qui mérite d'être compris.
             */
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Suivi minimal côté équipe. Pas de statut à plusieurs valeurs :
            // un message est traité ou il ne l'est pas.
            $table->timestamp('handled_at')->nullable();

            $table->timestamps();

            // Les messages se lisent du plus récent au plus ancien, toujours.
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
