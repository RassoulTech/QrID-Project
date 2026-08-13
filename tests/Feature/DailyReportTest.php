<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\MailLog;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Profile;
use App\Models\Subscription;
use App\Models\User;
use App\Services\RapportQuotidien;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * BLOC 2 — LE RÉCAPITULATIF QUOTIDIEN DISCORD.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LA RÈGLE QUE CES TESTS PROTÈGENT AVANT TOUTE AUTRE
 * ═══════════════════════════════════════════════════════════════════════
 * Le message part TOUS LES JOURS, même quand il n'y a rien à dire.
 *
 * Un récapitulatif qui se tait les jours creux rend l'absence de message
 * ambiguë : personne ne peut plus distinguer « rien ne s'est passé » de
 * « l'automatisation est cassée ». Et c'est toujours la seconde qu'on
 * découvre trop tard — on s'habitue au silence, puis on constate un mois plus
 * tard que le planificateur ne tourne plus.
 *
 * C'est le même raisonnement qui a fait retirer le voyant rouge permanent de
 * GitHub Actions : un signal qu'on apprend à ignorer ne signale plus rien.
 */
class DailyReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['notifications.discord.webhook' => 'https://discord.com/api/webhooks/123456789/jeton-de-test']);
    }

    /** La charge utile réellement envoyée à Discord. */
    private function chargeUtile(): array
    {
        $envois = Http::recorded();

        $this->assertNotEmpty($envois, 'Aucun appel vers Discord.');

        /** @var Request $requete */
        $requete = $envois[0][0];

        return $requete->data();
    }

    private function embed(): array
    {
        return $this->chargeUtile()['embeds'][0];
    }

    /** Tout le texte de l'embed, champs compris — pratique pour chercher. */
    private function texte(): string
    {
        $embed = $this->embed();

        return json_encode($embed, JSON_UNESCAPED_UNICODE);
    }

    private function planPayant(): Plan
    {
        return Plan::factory()->create([
            'slug' => 'mensuel', 'name' => 'Mensuel',
            'duration_days' => 30, 'price_fcfa' => 2500, 'is_active' => true,
        ]);
    }

    // =======================================================================
    // LE MESSAGE PART TOUJOURS
    // =======================================================================

    /**
     * LE TEST CENTRAL DU BLOC. Base vide, aucune activité : le message part
     * quand même, et il dit que tout fonctionne.
     */
    public function test_the_report_is_sent_even_on_a_completely_empty_day(): void
    {
        Http::fake();

        $this->artisan('report:daily')->assertSuccessful();

        Http::assertSentCount(1);

        $this->assertStringContainsString('Aucune activité', $this->texte());
        $this->assertStringContainsString('Tout fonctionne', $this->texte());
    }

    /** Une journée vide donne un message COURT : pas de tableau de zéros. */
    public function test_an_empty_day_produces_a_short_message(): void
    {
        Http::fake();

        $this->artisan('report:daily');

        $this->assertArrayNotHasKey(
            'fields',
            $this->embed(),
            'Une journée sans activité affiche un tableau de zéros : le message devient illisible à force d\'être long.'
        );
    }

    /** Une journée active, elle, porte tous ses chiffres. */
    public function test_an_active_day_carries_its_figures(): void
    {
        Http::fake();

        User::factory()->count(3)->create(['role' => User::ROLE_USER]);

        $this->artisan('report:daily')->assertSuccessful();

        $texte = $this->texte();

        $this->assertStringContainsString('Nouveaux comptes', $texte);
        $this->assertStringContainsString('État actuel', $texte);
        $this->assertStringNotContainsString('Aucune activité', $texte);
    }

    // =======================================================================
    // LA COMPARAISON AVEC LA VEILLE
    // =======================================================================

    /**
     * CHAQUE CHIFFRE PORTE CELUI DE LA VEILLE.
     *
     * Sans point de comparaison, « 3 inscriptions » ne dit rien : c'est bien
     * ou c'est mauvais selon ce qu'on faisait hier.
     */
    public function test_each_figure_carries_yesterdays_value(): void
    {
        Http::fake();

        User::factory()->count(3)->create(['role' => User::ROLE_USER]);

        User::factory()->count(5)->create([
            'role' => User::ROLE_USER,
            'created_at' => now()->subDay(),
        ]);

        $this->artisan('report:daily');

        $champs = collect($this->embed()['fields']);
        $comptes = $champs->firstWhere('name', 'Nouveaux comptes');

        $this->assertStringContainsString('3', $comptes['value']);
        $this->assertStringContainsString('hier : 5', $comptes['value']);
    }

    /**
     * LA COMPARAISON EST EN VALEUR ABSOLUE, jamais en pourcentage.
     *
     * Passer de 1 à 2 inscriptions n'est pas « +100 % » : sur de petits
     * nombres, le pourcentage impressionne sans rien dire. « 2 (hier 1) » se
     * lit sans interprétation.
     */
    public function test_the_comparison_is_never_a_percentage(): void
    {
        Http::fake();

        User::factory()->create(['role' => User::ROLE_USER]);
        User::factory()->create(['role' => User::ROLE_USER, 'created_at' => now()->subDay()]);

        $this->artisan('report:daily');

        $this->assertStringNotContainsString('%', $this->texte());
    }

    /** Le calcul du jour ignore l'avant-veille. */
    public function test_the_day_before_yesterday_is_not_counted(): void
    {
        User::factory()->create(['role' => User::ROLE_USER, 'created_at' => now()->subDays(2)]);

        $chiffres = (new RapportQuotidien)->chiffres();

        $this->assertSame(0, $chiffres['comptes']['valeur']);
        $this->assertSame(0, $chiffres['comptes']['veille']);
    }

    /** Les recettes du jour sont sommées, et seulement les paiements réussis. */
    public function test_only_successful_payments_count_towards_revenue(): void
    {
        Http::fake();

        $plan = $this->planPayant();
        $user = User::factory()->create();

        Payment::factory()->create([
            'user_id' => $user->id, 'amount_fcfa' => 2500,
            'status' => Payment::STATUS_SUCCESS,
        ]);

        Payment::factory()->create([
            'user_id' => $user->id, 'amount_fcfa' => 9999,
            'status' => Payment::STATUS_FAILED,
        ]);

        $chiffres = (new RapportQuotidien)->chiffres();

        $this->assertSame(2500, $chiffres['recettes']['valeur']);
        $this->assertSame(1, $chiffres['paiements']['valeur']);
        $this->assertNotNull($plan);
    }

    // =======================================================================
    // LES ALERTES PASSENT DEVANT
    // =======================================================================

    /**
     * UN PAIEMENT BLOQUÉ DEPUIS PLUS D'UNE HEURE EST SIGNALÉ EN TÊTE.
     *
     * Une heure, et non vingt-quatre comme sur l'écran d'administration : ce
     * message part une fois par jour, un seuil de 24 h laisserait passer une
     * journée entière avant le premier signalement.
     */
    public function test_a_stuck_payment_is_announced_first(): void
    {
        Http::fake();

        $this->planPayant();

        Payment::factory()->create([
            'user_id' => User::factory()->create()->id,
            'status' => Payment::STATUS_PENDING,
            'created_at' => now()->subHours(3),
        ]);

        $this->artisan('report:daily');

        $embed = $this->embed();

        $this->assertStringContainsString('À traiter', $embed['description']);
        $this->assertStringContainsString('bloqué', $embed['description']);
    }

    /**
     * UN E-MAIL QUI N'EST PAS PARTI EST SIGNALÉ.
     *
     * Ce motif existe à cause d'une panne réelle : l'envoi a cessé de
     * fonctionner pendant trois jours sans que rien ne le signale. Une ligne
     * ici l'aurait rendu visible le premier soir.
     */
    public function test_a_failed_email_is_announced(): void
    {
        Http::fake();

        MailLog::create([
            'recipient' => 'client@qrid.sn',
            'subject' => 'Réinitialisation',
            'mailable' => 'App\\Mail\\ResetPasswordMail',
            'mailer' => 'smtp',
            'status' => 'failed',
            'error' => 'SMTP injoignable',
            'sent_at' => null,
        ]);

        $this->artisan('report:daily');

        $this->assertStringContainsString('n\'est pas parti', $this->embed()['description']);
    }

    /** La couleur change quand quelque chose appelle une action. */
    public function test_the_colour_changes_when_something_needs_attention(): void
    {
        Http::fake();
        $this->artisan('report:daily');
        $calme = $this->embed()['color'];

        Http::fake();
        MailLog::create([
            'recipient' => 'x@qrid.sn', 'subject' => 'x', 'mailable' => 'x',
            'mailer' => 'smtp', 'status' => 'failed', 'error' => 'x', 'sent_at' => null,
        ]);
        $this->artisan('report:daily');

        $this->assertNotSame(
            $calme,
            $this->embed()['color'],
            'Une journée avec alerte a la même couleur qu\'une journée calme : la distinction ne se voit pas.'
        );
    }

    /** Sans alerte, la description ne fabrique pas d'inquiétude. */
    public function test_a_calm_day_says_so_plainly(): void
    {
        Http::fake();

        User::factory()->create(['role' => User::ROLE_USER]);

        $this->artisan('report:daily');

        $this->assertStringNotContainsString('À traiter', $this->embed()['description']);
    }

    // =======================================================================
    // L'ÉTAT COURANT
    // =======================================================================

    /** L'état porte les abonnements actifs, les essais et les cartes en ligne. */
    public function test_the_current_state_is_reported(): void
    {
        $essai = Plan::factory()->create(['slug' => 'essai-gratuit', 'price_fcfa' => 0, 'duration_days' => 15]);
        $payant = $this->planPayant();

        Subscription::factory()->create([
            'user_id' => User::factory()->create()->id,
            'plan_id' => $essai->id,
            'status' => Subscription::STATUS_ACTIVE,
            'ends_at' => now()->addDays(10),
        ]);

        Subscription::factory()->create([
            'user_id' => User::factory()->create()->id,
            'plan_id' => $payant->id,
            'status' => Subscription::STATUS_ACTIVE,
            'ends_at' => now()->addDays(20),
        ]);

        Profile::factory()->create(['is_active' => true]);

        $etat = (new RapportQuotidien)->etat();

        $this->assertSame(2, $etat['abonnements_actifs']);
        $this->assertSame(1, $etat['essais'], 'Un essai est un abonnement actif sur une formule gratuite.');
        $this->assertSame(1, $etat['cartes_en_ligne']);
    }

    /** Les messages de contact du jour sont comptés. */
    public function test_contact_messages_are_counted(): void
    {
        ContactMessage::create([
            'name' => 'Awa', 'email' => 'awa@exemple.sn', 'subject' => 'information',
            'message' => 'Bonjour, je voudrais des informations sur vos cartes.',
        ]);

        $this->assertSame(1, (new RapportQuotidien)->chiffres()['messages']['valeur']);
    }

    // =======================================================================
    // CE QUI NE DOIT PAS ARRIVER
    // =======================================================================

    /**
     * SANS WEBHOOK, LA COMMANDE ÉCHOUE — elle ne rend pas un succès muet.
     *
     * Une commande qui rendrait SUCCESS sans rien envoyer produirait, dans le
     * journal du planificateur, exactement la même ligne qu'un envoi réussi.
     * Le jour où l'on cherchera pourquoi le salon est silencieux, cette ligne
     * fera perdre une heure.
     */
    public function test_a_missing_webhook_fails_loudly(): void
    {
        Http::fake();
        config(['notifications.discord.webhook' => null]);

        $this->artisan('report:daily')->assertFailed();

        Http::assertNothingSent();
    }

    /** Un refus de Discord fait échouer la commande, sans exception. */
    public function test_a_refusal_from_discord_fails_without_throwing(): void
    {
        Http::fake(['discord.com/*' => Http::response('webhook inconnu', 404)]);

        $this->artisan('report:daily')->assertFailed();
    }

    /** Une panne réseau ne fait pas remonter d'exception non plus. */
    public function test_a_network_failure_is_contained(): void
    {
        Http::fake(fn () => throw new \RuntimeException('réseau injoignable'));

        $this->artisan('report:daily')->assertFailed();
    }

    /**
     * L'URL DU WEBHOOK EST UN SECRET : elle ne doit jamais atteindre un journal.
     *
     * Quiconque la possède peut écrire dans le salon. On consigne de quoi
     * distinguer deux webhooks, jamais de quoi s'en servir.
     */
    public function test_the_webhook_url_never_reaches_the_logs(): void
    {
        $source = (string) file_get_contents(app_path('Services/DiscordNotifier.php'));

        // Aucune trace du webhook complet dans un appel de journalisation.
        $this->assertStringNotContainsString("'webhook' => \$webhook", $source);
        $this->assertStringContainsString('salonTronque', $source);
    }

    /** La simulation n'envoie rien et réussit. */
    public function test_the_dry_run_sends_nothing(): void
    {
        Http::fake();

        $this->artisan('report:daily --dry-run')->assertSuccessful();

        Http::assertNothingSent();
    }

    /** La simulation fonctionne même sans webhook : c'est un outil de mise au point. */
    public function test_the_dry_run_works_without_a_webhook(): void
    {
        config(['notifications.discord.webhook' => null]);

        $this->artisan('report:daily --dry-run')->assertSuccessful();
    }

    // =======================================================================
    // LA PLANIFICATION
    // =======================================================================

    /**
     * LA TÂCHE EST DÉCLARÉE, AVEC SON FUSEAU.
     *
     * Le serveur tourne en UTC. Sans ->timezone(), le message partirait à 21 h
     * UTC — ce qui donne bien 21 h à Dakar aujourd'hui, mais cesserait d'être
     * vrai au premier changement de région d'hébergement.
     */
    public function test_the_report_is_scheduled_with_an_explicit_timezone(): void
    {
        $console = (string) file_get_contents(base_path('routes/console.php'));

        $this->assertStringContainsString("Schedule::command('report:daily')", $console);
        $this->assertStringContainsString('->timezone(', $console);
        $this->assertStringContainsString('withoutOverlapping', $console);
    }
}
