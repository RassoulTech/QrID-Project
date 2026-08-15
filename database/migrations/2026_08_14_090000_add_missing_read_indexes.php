<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * QUATRE INDEX MANQUANTS — bloc 3 du plan de lancement.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CE QUI A ÉTÉ AJOUTÉ, ET CE QUI NE L'A PAS ÉTÉ
 * ═══════════════════════════════════════════════════════════════════════
 * Un index n'est pas gratuit : il grossit à chaque écriture, occupe du disque,
 * et ralentit les insertions. On n'en pose donc que sur des requêtes NOMMÉES,
 * qui existent réellement dans le code et tournent souvent.
 *
 * Les colonnes déjà couvertes n'ont rien reçu de plus : profiles.is_active,
 * subscriptions ['status','ends_at'], payments ['status','created_at'],
 * profile_events ['profile_id','created_at'] font déjà leur travail.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * L'ORDRE DES COLONNES D'UN INDEX COMPOSITE N'EST PAS INDIFFÉRENT
 * ═══════════════════════════════════════════════════════════════════════
 * Un index ['role','created_at'] sert une requête qui filtre sur `role` PUIS
 * borne `created_at`. L'inverse — ['created_at','role'] — ne servirait à rien
 * ici : le moteur ne peut utiliser une colonne d'index qu'après avoir fixé
 * toutes celles qui la précèdent. La colonne d'ÉGALITÉ vient donc en premier,
 * celle d'INTERVALLE ensuite.
 */
return new class extends Migration
{
    public function up(): void
    {
        /*
         | users ['role', 'created_at']
         |
         | La requête la plus répétée du produit. AdminStatsService la lance
         | DEUX FOIS PAR CARTE — période courante et période précédente — sur
         | six cartes, à chaque affichage de la vue d'ensemble :
         |
         |     User::where('role', 'user')->whereBetween('created_at', [...])
         |
         | Elle sert aussi l'histogramme des inscriptions, le récapitulatif
         | quotidien, et la liste des clients triée par date. Sans l'index,
         | chacune balaye toute la table des comptes.
         */
        Schema::table('users', function (Blueprint $table) {
            $table->index(['role', 'created_at'], 'users_role_created_at_index');
        });

        /*
         | payments.method
         |
         | Le filtre « Moyen de paiement » de la liste d'administration. Les
         | deux index existants portent sur le statut ; celui-ci est le seul
         | filtre de l'écran qu'aucun ne couvrait.
         */
        Schema::table('payments', function (Blueprint $table) {
            $table->index('method', 'payments_method_index');
        });

        /*
         | mail_logs ['status', 'created_at']
         |
         | La table n'avait AUCUN index sur `status`, alors que c'est la seule
         | colonne sur laquelle on la filtre :
         |
         |   · l'alerte du récapitulatif quotidien compte les échecs du jour ;
         |   · l'écran « État système » les affiche.
         |
         | Cette table grossit d'une ligne par e-mail envoyé — c'est celle qui
         | croîtra le plus vite du schéma. Le scan complet passe inaperçu
         | aujourd'hui et deviendra le premier ralentissement visible.
         */
        Schema::table('mail_logs', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'mail_logs_status_created_at_index');
        });

        /*
         | profiles.created_at
         |
         | Deux usages : le comptage des cartes créées sur une période, et la
         | sélection de `profiles:remind`, qui cherche les cartes non publiées
         | plus anciennes qu'un délai.
         |
         | Pas de composite avec is_active : celui-ci existe déjà seul, et le
         | moteur sait combiner deux index sur une même table. Un troisième
         | index composite coûterait des écritures pour un gain nul.
         */
        Schema::table('profiles', function (Blueprint $table) {
            $table->index('created_at', 'profiles_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_created_at_index');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_method_index');
        });

        Schema::table('mail_logs', function (Blueprint $table) {
            $table->dropIndex('mail_logs_status_created_at_index');
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropIndex('profiles_created_at_index');
        });
    }
};
