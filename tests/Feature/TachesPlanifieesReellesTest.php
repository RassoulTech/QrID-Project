<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * CE QUE LA MISE EN SERVICE DU PLANIFICATEUR A RÉVÉLÉ.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * DES TÂCHES ÉCRITES, TESTÉES, ET JAMAIS EXÉCUTÉES
 * ═══════════════════════════════════════════════════════════════════════════
 * Huit tâches attendaient un planificateur qui n'existait pas. Le jour de sa
 * mise en service, deux ont échoué dans la minute — et pour deux raisons
 * entièrement différentes :
 *
 *   app:sauvegarder            mysqldump n'était pas installé dans l'image.
 *                              Une vraie panne, invisible tant que personne
 *                              n'exécutait la commande.
 *
 *   app:reconcilier-paiements  aucune panne : elle rendait délibérément un
 *                              code non nul pour signaler une anomalie
 *                              métier. Sous un planificateur, ce code devient
 *                              une ligne production.ERROR quotidienne.
 *
 * Le second cas est le plus insidieux. Une erreur quotidienne qui n'en est
 * pas une apprend à ignorer les erreurs — et le jour où le planificateur
 * tombe vraiment, sa ligne rouge se perd au milieu des fausses.
 */
class TachesPlanifieesReellesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TROUVER UN PAIEMENT BLOQUÉ EST LE TRAVAIL DE CETTE COMMANDE, PAS SA
     * PANNE.
     */
    public function test_finding_a_stuck_payment_is_not_reported_as_a_failure(): void
    {
        Log::spy();

        $client = User::factory()->create();

        Payment::factory()->create([
            'user_id' => $client->id,
            'status' => Payment::STATUS_PENDING,
            'created_at' => now()->subDays(5),
        ]);

        $this->artisan('app:reconcilier-paiements')->assertSuccessful();
    }

    /**
     * Le signal, lui, reste entier : il part dans les journaux, que le
     * récapitulatif quotidien relaie.
     */
    public function test_the_stuck_payment_is_still_reported_in_the_logs(): void
    {
        Log::spy();

        $client = User::factory()->create();

        Payment::factory()->create([
            'user_id' => $client->id,
            'status' => Payment::STATUS_PENDING,
            'amount_fcfa' => 2500,
            'created_at' => now()->subDays(5),
        ]);

        $this->artisan('app:reconcilier-paiements')->assertSuccessful();

        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message) => str_contains($message, 'Paiements en attente'))
            ->once();
    }

    /** Rien à signaler reste un succès, comme avant. */
    public function test_nothing_to_report_is_still_a_success(): void
    {
        $this->artisan('app:reconcilier-paiements')->assertSuccessful();
    }

    /**
     * Un paiement récent n'est pas « bloqué » : le délai est ce qui
     * distingue une transaction en cours d'une transaction abandonnée.
     */
    public function test_a_recent_pending_payment_is_left_alone(): void
    {
        Log::spy();

        $client = User::factory()->create();

        Payment::factory()->create([
            'user_id' => $client->id,
            'status' => Payment::STATUS_PENDING,
            'created_at' => now()->subHours(2),
        ]);

        $this->artisan('app:reconcilier-paiements')->assertSuccessful();

        Log::shouldNotHaveReceived('warning');
    }
}
