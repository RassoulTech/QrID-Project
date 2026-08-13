<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MÉMOIRE DES RELANCES — ce qui empêche d'écrire deux fois la même chose.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI DES COLONNES, ET NON UN CALCUL SUR LES DATES
 * ═══════════════════════════════════════════════════════════════════════
 * On pouvait déduire les relances des seules dates : « les profils créés il y
 * a entre 24 et 48 heures ». Ce raccourci tient tant que la commande tourne
 * exactement une fois par jour — et rien ne le garantit. Un déploiement qui
 * relance le planificateur, un rattrapage manuel après une panne, deux
 * exécutions rapprochées : chaque cas renvoie le même e-mail.
 *
 * Or l'erreur n'est pas symétrique. Un rappel manquant est un manque à
 * gagner ; un rappel envoyé trois fois fait classer l'expéditeur en
 * indésirable — et cette réputation-là se paie ensuite sur les e-mails qui
 * comptent vraiment, à commencer par la réinitialisation de mot de passe.
 *
 * Deux colonnes suffisent à rendre la question sans objet : le fait est écrit,
 * pas déduit.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * DEUX COLONNES SUR profiles, UNE SUR subscriptions — POURQUOI L'ÉCART
 * ═══════════════════════════════════════════════════════════════════════
 * Les rappels de profil sont une SÉQUENCE : le premier, puis le second, et
 * jamais de troisième. Il faut donc savoir combien sont partis.
 *
 * Les relances d'échéance sont commandées par le calendrier : le palier se
 * déduit de ends_at. Une seule date suffit — « a-t-on déjà écrit aujourd'hui ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            // 0, 1 ou 2. Jamais davantage : la valeur borne la séquence.
            $table->unsignedTinyInteger('reminder_count')->default(0)->after('is_active');
            $table->timestamp('reminder_sent_at')->nullable()->after('reminder_count');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->timestamp('notified_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['reminder_count', 'reminder_sent_at']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('notified_at');
        });
    }
};
