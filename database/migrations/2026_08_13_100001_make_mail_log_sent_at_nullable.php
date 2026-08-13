<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `mail_logs.sent_at` DEVIENT NULLABLE — correction d'un défaut sérieux.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CE QUI SE PASSAIT
 * ═══════════════════════════════════════════════════════════════════════
 * La table naît pour journaliser les e-mails ENVOYÉS : `sent_at` y était donc
 * obligatoire, ce qui se tenait. Puis on lui a demandé de porter aussi les
 * ÉCHECS — et un e-mail qui n'est pas parti n'a pas de date d'envoi.
 *
 * Chaque tentative d'écrire une ligne d'échec passait `sent_at = null` et
 * heurtait la contrainte NOT NULL. L'écriture était rejetée, puis avalée par
 * le try/catch qui protège la journalisation. Résultat : AUCUNE PANNE D'ENVOI
 * N'A JAMAIS ÉTÉ ENREGISTRÉE.
 *
 * La conséquence est double, et la seconde est la pire :
 *
 *   · l'écran « État système » affichait une liste sans le moindre échec,
 *     donc rassurante, pendant que les e-mails ne partaient pas ;
 *   · on a cherché la cause d'une panne d'envoi dans un journal que le
 *     défaut lui-même empêchait de se remplir.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI NULL PLUTÔT QUE now()
 * ═══════════════════════════════════════════════════════════════════════
 * Écrire l'heure courante aurait contourné la contrainte en une ligne. Mais
 * une colonne nommée « sent_at » portant une date sur un message jamais parti
 * est un mensonge stocké : six mois plus tard, personne ne saura que cette
 * date ne veut rien dire. NULL dit exactement ce qui s'est passé — rien.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mail_logs', function (Blueprint $table) {
            $table->timestamp('sent_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        /*
         | Retour en arrière volontairement NON DESTRUCTIF.
         |
         | Restaurer NOT NULL exigerait d'inventer une date pour chaque ligne
         | d'échec, ou de les supprimer. Les deux détruiraient précisément
         | l'information que cette migration existe pour conserver. La colonne
         | reste donc nullable : c'est le seul sens qui vaille.
         */
    }
};
