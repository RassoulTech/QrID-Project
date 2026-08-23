<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * photo_data PASSE DE BLOB À MEDIUMBLOB.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * UN PLAFOND À 65 535 OCTETS, ET DEUX FAÇONS DE LE DÉCOUVRIR
 * ═══════════════════════════════════════════════════════════════════════
 * $table->binary() produit un BLOB sur MySQL, et un BLOB ne peut pas
 * dépasser 64 Ko. Une photo de profil recadrée en 512×512 et compressée en
 * JPEG pèse le plus souvent 20 à 40 Ko — d'où l'impression que la place
 * suffisait. Mesuré sur une image très détaillée, le même réglage monte à
 * 184 Ko : un feuillage en arrière-plan, un vêtement à motifs ou une photo
 * prise en basse lumière suffisent à tripler le poids.
 *
 * Au-delà du plafond, MySQL fait l'une de ces deux choses :
 *
 *   — en mode strict, il refuse l'écriture (erreur 1406) et la dernière
 *     étape du parcours de création répond 500, sans que le client
 *     comprenne que sa photo est en cause ;
 *
 *   — sinon, il TRONQUE en silence. La colonne contient alors un JPEG
 *     incomplet : la page publique affiche une image à moitié grise, ou
 *     rien du tout, et aucun journal ne signale quoi que ce soit.
 *
 * Le second cas est le pire, et c'est celui qu'on aurait constaté sans
 * comprendre — la photo « s'abîme » à l'enregistrement.
 *
 * MEDIUMBLOB porte 16 Mo. Le service de création plafonne de son côté la
 * taille encodée : les deux protections sont volontairement redondantes,
 * parce que celle-ci vaut aussi pour les octets écrits par un autre chemin.
 *
 * SQLITE N'A PAS DE SOUS-TYPES DE BLOB : la migration ne le concerne pas et
 * s'abstient plutôt que d'échouer sur une instruction qu'il ne connaît pas.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! $this->surMysql()) {
            return;
        }

        DB::statement('ALTER TABLE profiles MODIFY photo_data MEDIUMBLOB NULL');
    }

    public function down(): void
    {
        if (! $this->surMysql()) {
            return;
        }

        /*
         | LE RETOUR ARRIÈRE TRONQUE, et il faut le dire ici plutôt que de le
         | découvrir. Redescendre à BLOB coupe à 64 Ko toute photo plus
         | lourde. C'est acceptable pour un rollback — le fichier survit sur
         | le disque le temps du déploiement — mais ce n'est pas neutre.
         */
        DB::statement('ALTER TABLE profiles MODIFY photo_data BLOB NULL');
    }

    private function surMysql(): bool
    {
        return Schema::getConnection()->getDriverName() === 'mysql';
    }
};
