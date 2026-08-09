<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal des e-mails envoyés. Permet de répondre factuellement à un client
 * affirmant n'avoir rien reçu : qui, quel type, quand, avec quel statut.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_logs', function (Blueprint $table) {
            $table->id();
            $table->string('recipient')->index();
            $table->string('subject');
            $table->string('mailable')->nullable()->index(); // classe du Mailable
            $table->string('mailer', 32);                    // smtp, log, ses…
            $table->string('status', 16)->default('sent');   // sent | failed
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_logs');
    }
};
