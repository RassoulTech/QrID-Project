<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LES AGRÉGATS JOURNALIERS — une ligne par profil et par jour.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CE QUE CETTE TABLE REMPLACE, MESURÉ
 * ═══════════════════════════════════════════════════════════════════════
 * La page de statistiques de l'administration agrégeait `profile_events` en
 * entier, trois fois. Mesuré sur cette base, avec 1 000 profils :
 *
 *     2 000 événements  ......      45 ms
 *   100 000 événements  ......     450 ms
 * 1 000 000 événements  ......  32 301 ms
 *
 * Fois dix sur le volume, fois soixante-douze sur le temps : la table
 * temporaire déborde sur le disque et la dégradation cesse d'être linéaire.
 * À un million d'événements — soit environ mille clients actifs pendant
 * trois ans — la page était morte.
 *
 * EXPLAIN nommait la cause : la table pilote est `profiles`, donc MySQL
 * agrège TOUT l'historique de chaque profil, construit une table
 * temporaire, la trie, puis n'en garde que dix lignes. Le filtre sur la
 * date n'écarte rien en amont.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI UNE TABLE ET NON UN CACHE
 * ═══════════════════════════════════════════════════════════════════════
 * Un cache aurait masqué le problème sans le résoudre : le premier visiteur
 * après expiration aurait toujours payé les trente secondes, et le cache
 * d'une page d'administration se vide à chaque déploiement.
 *
 * Ici, le travail est fait UNE FOIS par nuit, sur les seuls événements de
 * la veille, et les pages lisent une table mille fois plus petite. Une
 * année de mille profils actifs tient dans 365 000 lignes d'agrégats là où
 * les événements bruts en comptent des millions.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LA CLÉ EST (profile_id, jour), ET ELLE EST UNIQUE
 * ═══════════════════════════════════════════════════════════════════════
 * C'est elle qui rend l'agrégation REJOUABLE. Une nuit manquée, un
 * conteneur redémarré au mauvais moment, une reprise manuelle sur trente
 * jours : l'écriture se fait en upsert, et repasser deux fois sur le même
 * jour donne exactement le même résultat. Sans cette contrainte, une
 * commande relancée doublerait les compteurs sans que rien ne le signale.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_stats_daily', function (Blueprint $table) {
            $table->id();

            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();

            // Une DATE, pas un datetime : c'est le grain de l'agrégat, et une
            // date se compare et s'indexe sans fonction — contrairement au
            // DATE(created_at) qui interdisait tout index sur la table source.
            $table->date('jour');

            $table->unsignedInteger('vues')->default(0);
            $table->unsignedInteger('scans')->default(0);
            $table->unsignedInteger('saves')->default(0);
            $table->unsignedInteger('total')->default(0);

            $table->timestamps();

            // L'upsert s'appuie dessus : voir la note en tête.
            $table->unique(['profile_id', 'jour']);

            // Les totaux de l'administration balaient par DATE, tous profils
            // confondus. Sans cet index, ce balayage redeviendrait complet.
            $table->index('jour');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_stats_daily');
    }
};
