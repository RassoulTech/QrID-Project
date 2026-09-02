<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * L'INDEX QUI MANQUAIT À LA TÂCHE CHARGÉE DE RENDRE TOUT RAPIDE.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * LE DÉFAUT
 * ═══════════════════════════════════════════════════════════════════════════
 * `profile_events` porte trois index, et les trois commencent par
 * `profile_id` :
 *
 *   (profile_id, created_at)
 *   (profile_id, type)
 *   (profile_id, type, created_at)
 *
 * Ils servent parfaitement les écrans d'un client, qui regarde SES chiffres.
 * Mais deux requêtes du produit balaient par DATE sans filtrer de profil :
 *
 *   1. l'agrégation nocturne — « toutes les visites d'hier, groupées par
 *      profil ». C'est la tâche dont le rôle est précisément de rendre les
 *      statistiques rapides.
 *   2. la lecture des statistiques de l'administration, tous profils
 *      confondus, sur la portion que l'agrégation n'a pas encore couverte.
 *
 * Le moteur ne peut employer une colonne d'index qu'après avoir fixé celles
 * qui la précèdent. Une requête qui ne connaît pas `profile_id` ne peut donc
 * SEEK dans aucun des trois : elle les parcourt d'un bout à l'autre.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * MESURE — 2 224 événements en base locale
 * ═══════════════════════════════════════════════════════════════════════════
 * EXPLAIN de l'agrégation d'UNE journée, avant cet index :
 *
 *     type = index   key = profile_events_profile_id_created_at_index
 *     rows = 2303    Extra = Using where; Using index
 *
 * `type = index` est un balayage COMPLET de l'index, pas une lecture de
 * plage — et `rows` vaut la table entière pour agréger une seule journée.
 * Le coût de la nuit croît donc avec TOUT l'historique, alors qu'il ne
 * devrait croître qu'avec le trafic de la veille. C'est le genre de courbe
 * qui ne se remarque pas à mille profils et qui devient une panne à cent
 * mille.
 *
 * La même requête, après :
 *
 *     type = range   key = pe_date_profil_type
 *     rows = 4       Extra = Using where; Using index; …
 *
 * 2 303 lignes examinées deviennent 4 — celles de la journée demandée. Le
 * balayage est devenu une lecture de plage, et le nombre de lignes lues ne
 * dépend plus que du trafic du jour agrégé.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * POURQUOI TROIS COLONNES, ET DANS CET ORDRE
 * ═══════════════════════════════════════════════════════════════════════════
 *     created_at   la borne de la requête — elle doit venir en tête pour
 *                  que la lecture soit une PLAGE et non un balayage.
 *     profile_id   la colonne de regroupement.
 *     type         les sommes conditionnelles SUM(type = ?).
 *
 * Les trois colonnes lues sont dans l'index : le moteur n'a plus à ouvrir la
 * table. C'est ce que MySQL appelle un index couvrant, et c'est ce qui rend
 * la nuit proportionnelle à la journée qu'elle traite.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * CE QUE CET INDEX COÛTE
 * ═══════════════════════════════════════════════════════════════════════════
 * `profile_events` est écrite à chaque scan de carte. Un index de plus, ce
 * sont des microsecondes ajoutées à chaque écriture — et une table qui
 * grossit un peu plus vite sur le disque.
 *
 * L'arbitrage est le même que celui déjà retenu par la migration
 * `add_scale_indexes` : un index superflu se mesure en microsecondes
 * d'écriture, un index manquant se mesure en secondes de lecture. Ici il ne
 * s'agit même pas d'une lecture d'écran mais du travail de fond dont dépend
 * la rapidité de tous les autres.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profile_events', function (Blueprint $table) {
            $table->index(['created_at', 'profile_id', 'type'], 'pe_date_profil_type');
        });
    }

    public function down(): void
    {
        Schema::table('profile_events', function (Blueprint $table) {
            $table->dropIndex('pe_date_profil_type');
        });
    }
};
