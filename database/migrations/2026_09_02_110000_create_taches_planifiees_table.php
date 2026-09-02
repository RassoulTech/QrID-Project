<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LA MÉMOIRE DES TÂCHES QUOTIDIENNES — « celle-ci a-t-elle déjà tourné
 * aujourd'hui ? »
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * POURQUOI CETTE TABLE EXISTE
 * ═══════════════════════════════════════════════════════════════════════════
 * Le planificateur de Laravel raisonne à la MINUTE : `dailyAt('02:30')` ne
 * part que si on l'interroge pendant la minute 02:30. Ce contrat suppose un
 * processus qui l'interroge sans jamais s'interrompre.
 *
 * Ce n'est pas le cas ici. Le conteneur s'endort quand plus personne ne
 * visite le site — c'est le fonctionnement du plan gratuit de Render — et le
 * planificateur s'endort avec lui. La minute 02:30 passe alors sans que
 * personne ne la voie, et la tâche ne s'exécute JAMAIS : ni en retard, ni
 * avec une erreur. Elle est simplement sautée, en silence.
 *
 * Les tâches cessent donc de demander « quelle minute est-il ? » pour
 * demander « ai-je déjà tourné aujourd'hui, et l'heure est-elle passée ? ».
 * Un conteneur endormi les RETARDE ; il ne les saute plus.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * POURQUOI EN BASE ET NON DANS LE CACHE
 * ═══════════════════════════════════════════════════════════════════════════
 * Le cache aurait suffi à première vue. Mais `docker/entrypoint.sh` lance
 * `php artisan optimize:clear` à CHAQUE déploiement, ce qui vide le cache
 * applicatif. Un marqueur qui y vivrait serait effacé, et toutes les tâches
 * du jour repartiraient après chaque mise en ligne.
 *
 * Sans conséquence pour la plupart — l'agrégation est un upsert rejouable,
 * la purge est idempotente, les relances portent déjà leur propre garde
 * (`reminder_count`, `notified_at`). Mais le récapitulatif quotidien, lui,
 * n'a aucune garde : il serait publié deux fois dans le salon d'équipe, et
 * deux récapitulatifs de la même journée font douter de tous les autres.
 *
 * Une table est donc le bon support : elle survit au déploiement, ce que le
 * cache ne fait pas.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ELLE RESTE MINUSCULE
 * ═══════════════════════════════════════════════════════════════════════════
 * Une ligne par tâche planifiée — huit aujourd'hui. Elle ne grossit pas avec
 * le temps : chaque exécution MET À JOUR sa ligne au lieu d'en ajouter une.
 * Ce n'est pas un journal, c'est un signet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taches_planifiees', function (Blueprint $table) {
            // La clé est le nom de la commande : lisible dans un SELECT, et
            // naturellement unique. Un identifiant numérique n'apporterait
            // rien à une table dont on ne cherche jamais que par le nom.
            $table->string('cle', 191)->primary();

            // Le dernier jour où la tâche a été TENTÉE — pas réussie. Une
            // tâche en échec ne doit pas repartir toutes les cinq minutes
            // jusqu'à minuit : elle repartira demain, et l'échec se lit dans
            // les journaux et sur l'écran « État système ».
            $table->date('dernier_jour')->nullable();

            $table->timestamp('mis_a_jour_le')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taches_planifiees');
    }
};
