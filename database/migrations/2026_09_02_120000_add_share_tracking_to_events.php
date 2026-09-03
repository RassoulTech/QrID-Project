<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LE PARTAGE DEVIENT UN ÉVÉNEMENT MESURÉ.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * CE QU'ON COMPTE, ET CE QU'ON REFUSE DE COMPTER
 * ═══════════════════════════════════════════════════════════════════════════
 * Une carte se transmet plus qu'elle ne se consulte. Trois compteurs
 * existaient — vues, scans, enregistrements — et aucun ne disait si le lien
 * CIRCULE. C'est pourtant la seule question qui compte pour qui vend des
 * cartes de visite.
 *
 * Mais on ne peut mesurer que ce que le navigateur nous dit. Ce qui est
 * enregistré ici est un PARTAGE INITIÉ : quelqu'un a appuyé sur le bouton et
 * WhatsApp — ou la feuille système — s'est ouvert.
 *
 * Ce n'est PAS « un message a été envoyé ». L'application n'en sait rien et
 * ne peut pas le savoir : ce qui se passe après l'ouverture de WhatsApp lui
 * échappe entièrement. Un compteur nommé « messages envoyés » serait un
 * chiffre inventé, et un chiffre inventé sur un tableau de bord fait douter
 * de tous les autres.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * L'ÉNUMÉRATION DISPARAÎT, ET C'EST LE POINT DÉLICAT DE CETTE MIGRATION
 * ═══════════════════════════════════════════════════════════════════════════
 * `type` était un `enum('view','scan','save')`. Une énumération refuse une
 * valeur inconnue AU NIVEAU DE LA BASE, ce qu'aucune validation applicative
 * ne garantit le jour où un script écrit directement. C'est une bonne chose,
 * et on la perd ici à contrecœur.
 *
 * La raison est que ce produit tourne sur DEUX moteurs :
 *
 *   production   MySQL, où l'on étend une énumération par
 *                `ALTER TABLE … MODIFY type ENUM(…)`
 *   tests        SQLite en mémoire, où `enum` devient une contrainte CHECK
 *                et où cette commande est une erreur de syntaxe
 *
 * Une migration qui se ramifie selon le moteur produirait un schéma de test
 * DIFFÉRENT du schéma de production — c'est-à-dire une suite qui cesse de
 * dire la vérité sur ce qu'elle protège. C'est exactement le genre d'écart
 * qui laisse passer une panne jusqu'en production.
 *
 * `->change()` vers une chaîne courte fonctionne à l'identique sur les deux :
 * MySQL fait un ALTER, SQLite reconstruit la table. Le même schéma des deux
 * côtés, au prix d'une contrainte que la validation applicative et les
 * constantes de ProfileEvent reprennent à leur compte.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * POURQUOI UNE COLONNE `canal` PLUTÔT QUE QUATRE TYPES
 * ═══════════════════════════════════════════════════════════════════════════
 * On aurait pu créer `share_whatsapp`, `share_copie`, `share_natif`… Chaque
 * canal nouveau aurait alors demandé une migration, et chaque agrégat une
 * colonne de plus.
 *
 * Un type + un canal séparent la NATURE de l'acte de son MOYEN. Compter les
 * partages ne demande alors qu'un filtre sur le type ; les ventiler par canal
 * reste possible sans toucher au schéma.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profile_events', function (Blueprint $table) {
            $table->string('type', 20)->change();

            /*
             | Le moyen employé. Nul pour les trois types historiques, qui
             | n'en ont pas — une vue n'a pas de canal.
             |
             | `natif` est la feuille de partage du système : elle propose
             | WhatsApp parmi tout le reste, et l'on ne saura JAMAIS ce que
             | la personne a choisi ensuite. La distinguer de `whatsapp` est
             | donc une honnêteté, pas un détail : l'un dit « elle a choisi
             | WhatsApp », l'autre « elle a ouvert le choix ».
             */
            $table->string('canal', 20)->nullable()->after('type');
        });

        Schema::table('profile_stats_daily', function (Blueprint $table) {
            // Comme ses trois voisines : un compteur par jour et par profil,
            // pour que la lecture n'ait jamais à parcourir les événements.
            $table->unsignedInteger('partages')->default(0)->after('saves');
        });
    }

    public function down(): void
    {
        Schema::table('profile_stats_daily', function (Blueprint $table) {
            $table->dropColumn('partages');
        });

        Schema::table('profile_events', function (Blueprint $table) {
            $table->dropColumn('canal');
        });

        /*
         | LE TYPE RESTE UNE CHAÎNE, MÊME EN ARRIÈRE.
         |
         | Revenir à l'énumération d'origine buterait sur les lignes de type
         | « share » déjà écrites : MySQL les transformerait en chaîne vide,
         | produisant des événements d'un type qui n'existe plus — et
         | silencieusement.
         |
         | Une migration inverse doit défaire ce qu'on a ajouté, pas
         | reconstruire un état que les données ne permettent plus.
         */
    }
};
