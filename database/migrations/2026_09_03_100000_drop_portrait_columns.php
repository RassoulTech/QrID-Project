<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * LE PORTRAIT DISPARAÎT — mais pas les images des clients qui en ont un.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * CE QUE CETTE MIGRATION TERMINE
 * ═══════════════════════════════════════════════════════════════════════════
 * Le produit demandait deux images : un portrait et une bannière. L'assistant
 * n'en demande plus qu'une depuis longtemps — la couverture — mais les
 * colonnes du portrait sont restées, et la moitié du code continuait de les
 * lire.
 *
 * Le tableau de bord annonçait « Aucune photo » pour une image que rien ne
 * permettait de fournir. L'appareil de la page d'accueil réservait une place
 * à un portrait absent. Le fichier de contact et l'aperçu de partage
 * cherchaient d'abord le portrait, et retombaient sur rien.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * LA RECOPIE PASSE AVANT LA SUPPRESSION, ET CE N'EST PAS NÉGOCIABLE
 * ═══════════════════════════════════════════════════════════════════════════
 * Des profils créés avant le changement ont un portrait et PAS de couverture :
 * leur seule image est dans la colonne qu'on s'apprête à supprimer. La
 * supprimer sèchement effacerait la photo de ces clients — définitivement, et
 * sans qu'aucun message ne le signale.
 *
 * On recopie donc d'abord, et seulement là où la couverture est vide : un
 * profil qui a déjà choisi sa bannière garde la sienne, c'est son choix le
 * plus récent.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * CE QUE CELA REND, MESURABLE
 * ═══════════════════════════════════════════════════════════════════════════
 * `photo_data` est un MEDIUMBLOB. Chaque lecture de profil le chargeait, sur
 * la page publique comme dans l'espace client — y compris quand la page ne
 * fait qu'en construire une adresse. Deux images valaient deux blobs sur le
 * réseau à chaque scan de QR Code ; il n'en reste qu'un.
 */
return new class extends Migration
{
    public function up(): void
    {
        /*
         | La recopie, par lots.
         |
         | `chunkById` plutôt qu'un UPDATE global : les octets d'un profil
         | peuvent peser plusieurs mégaoctets, et les charger tous d'un coup
         | ferait dépasser la mémoire sur un conteneur de 512 Mo. Cent
         | profils à la fois tiennent toujours.
         */
        DB::table('profiles')
            ->select('id')
            ->whereNotNull('photo_path')
            ->whereNull('cover_path')
            ->orderBy('id')
            ->chunkById(100, function ($lignes) {
                foreach ($lignes as $ligne) {
                    $profil = DB::table('profiles')
                        ->where('id', $ligne->id)
                        ->first(['photo_path', 'photo_data']);

                    if ($profil === null) {
                        continue;
                    }

                    DB::table('profiles')->where('id', $ligne->id)->update([
                        'cover_path' => $profil->photo_path,
                        'cover_data' => $profil->photo_data,
                    ]);
                }
            });

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['photo_path', 'photo_data']);
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('bio');
            $table->binary('photo_data')->nullable()->after('photo_path');
        });

        /*
         | LES OCTETS NE REVIENNENT PAS, ET C'EST HONNÊTE.
         |
         | Recopier la couverture vers le portrait rendrait à chaque profil
         | une image qu'il n'avait peut-être jamais eue : celle qu'il a
         | choisie APRÈS le changement se retrouverait dans les deux colonnes,
         | et l'on ne saurait plus laquelle était la sienne.
         |
         | Une migration inverse rend le SCHÉMA, pas un passé qu'elle ne peut
         | pas reconstituer. Les images, elles, sont toutes dans `cover_data`.
         */
    }
};
