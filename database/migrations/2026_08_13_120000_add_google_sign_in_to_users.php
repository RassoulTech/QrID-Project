<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CONNEXION GOOGLE — ce qu'il faut en base, et rien de plus.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * DEUX COLONNES, DEUX RAISONS DISTINCTES
 * ═══════════════════════════════════════════════════════════════════════
 *
 * `google_id` — l'identifiant Google du compte, UNIQUE.
 *
 *   On n'identifie JAMAIS quelqu'un par son adresse seule au retour de
 *   Google : une adresse peut changer de propriétaire (un salarié quitte une
 *   entreprise, son adresse est réattribuée), tandis que l'identifiant Google
 *   est stable et ne se réattribue pas. L'adresse sert à RETROUVER un compte
 *   existant la première fois ; l'identifiant sert à le reconnaître ensuite.
 *
 *   L'unicité n'est pas décorative : sans elle, deux comptes du produit
 *   pourraient pointer vers le même compte Google, et la connexion suivante
 *   choisirait au hasard lequel ouvrir.
 *
 * `password` DEVIENT FACULTATIF.
 *
 *   Quelqu'un qui s'inscrit par Google n'a pas de mot de passe, et lui en
 *   fabriquer un aléatoire serait pire que de n'en pas mettre : il existerait
 *   sans que personne ne le connaisse, et le compte paraîtrait protégé par un
 *   secret que son propriétaire ne peut ni utiliser ni changer.
 *
 *   NULL dit la vérité : ce compte n'a pas de mot de passe. Le formulaire
 *   « mot de passe oublié » reste ouvert et permet d'en poser un — c'est le
 *   chemin par lequel un compte Google acquiert un second moyen d'accès.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique()->after('email');

            // Le nom affiché sur l'écran de consentement Google. Conservé pour
            // l'affichage seulement : la photo n'est PAS reprise, elle
            // appartient au profil et le client la choisit lui-même.
            $table->string('google_avatar')->nullable()->after('google_id');
        });

        /*
         | SQLite ne sait pas modifier une colonne : le pilote de test recrée
         | la table entière. Laravel s'en charge, mais uniquement si la
         | modification est déclarée seule — d'où ce second appel.
         */
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_id', 'google_avatar']);
        });

        /*
         | On NE REMET PAS password en NOT NULL.
         |
         | Des comptes créés par Google n'en ont pas : restaurer la contrainte
         | exigerait soit de leur inventer un mot de passe que personne ne
         | connaît, soit de les supprimer. Les deux sont pires que la colonne
         | restée facultative.
         */
    }
};
