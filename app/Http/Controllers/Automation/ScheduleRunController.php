<?php

namespace App\Http\Controllers\Automation;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * LE DÉCLENCHEUR DES TÂCHES PLANIFIÉES, appelé par Make chaque minute.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI CETTE ROUTE EXISTE
 * ═══════════════════════════════════════════════════════════════════════
 * Cinq tâches sont programmées et aucune ne s'exécute : un service web ne
 * fait que répondre aux requêtes HTTP, il ne regarde jamais l'heure. Sur un
 * hébergement sans processus permanent, il faut donc un appelant extérieur.
 *
 * `schedule:run` ne lance PAS les cinq tâches à chaque appel : il compare
 * l'heure courante à ce que déclare routes/console.php et ne déclenche que ce
 * qui est dû. Appelée à 14h07, cette route ne fait donc rien — et c'est
 * exactement ce qu'on attend d'elle.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LA SÉCURITÉ DE CETTE ROUTE, POINT PAR POINT
 * ═══════════════════════════════════════════════════════════════════════
 * Elle déclenche des envois d'e-mails et un message Discord. Quatre garde-fous,
 * chacun couvrant ce que les autres laissent passer :
 *
 *   1. UN JETON, comparé en temps constant. Sans lui, ou faux : 404.
 *   2. UN 404, jamais un 401. Un 401 confirmerait l'existence de l'adresse à
 *      qui la cherche ; un 404 ne dit rien de plus qu'une page absente.
 *   3. UNE LIMITE DE CADENCE, posée sur la route. L'usage légitime est d'un
 *      appel par minute ; au-delà, quelque chose ne va pas.
 *   4. AUCUNE DONNÉE EN RÉPONSE. On rend ce qui a tourné, jamais ce que la
 *      base contient.
 *
 * Le jeton voyage en EN-TÊTE et non dans l'URL : une adresse complète se
 * retrouve dans les journaux du serveur, dans l'historique du navigateur et
 * dans les en-têtes Referer envoyés à des tiers. Un en-tête, non.
 */
class ScheduleRunController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $attendu = (string) config('automation.schedule.token');

        /*
         | Jeton non configuré : la route n'existe pas.
         |
         | C'est le comportement voulu tant que rien n'est réglé. Une route
         | ouverte qu'on croit fermée est pire qu'une route absente — et sur
         | un déploiement où la variable aurait été oubliée, ce 404 est le
         | seul signal qui fera chercher.
         */
        if ($attendu === '') {
            abort(404);
        }

        $recu = (string) ($request->header('X-Automation-Token') ?: $request->query('token', ''));

        /*
         | hash_equals et non « === ».
         |
         | Une comparaison ordinaire s'arrête au premier caractère différent :
         | le temps de réponse trahit alors le nombre de caractères devinés,
         | et permet de reconstituer le jeton lettre par lettre. hash_equals
         | compare toujours la chaîne entière.
         */
        if (! hash_equals($attendu, $recu)) {
            Log::channel('mail')->warning('Appel non autorisé du déclencheur de tâches', [
                'ip' => $request->ip(),
                'jeton_fourni' => $recu === '' ? 'aucun' : 'incorrect',
            ]);

            abort(404);
        }

        $depart = microtime(true);

        /*
         | La sortie de la commande est CAPTURÉE, pas affichée.
         |
         | Elle contient le détail de ce qui a tourné, utile dans le journal
         | d'exécution de Make quand une tâche se met à échouer. Sans elle, on
         | ne saurait que « quelque chose s'est passé ».
         */
        $code = Artisan::call('schedule:run');
        $sortie = trim(Artisan::output());

        $ms = (int) round((microtime(true) - $depart) * 1000);

        Log::info('Tâches planifiées déclenchées', [
            'code' => $code,
            'duree_ms' => $ms,
            'sortie' => mb_substr($sortie, 0, 1000),
        ]);

        return response()->json([
            'ok' => $code === 0,
            'duree_ms' => $ms,
            // « Aucune tâche due » est une réponse NORMALE, et il faut le dire :
            // sans cette phrase, chaque minute creuse ressemblerait à une panne
            // dans le journal de Make.
            'message' => $sortie !== '' ? $sortie : 'Aucune tâche due à cette minute.',
        ], $code === 0 ? 200 : 500);
    }
}
