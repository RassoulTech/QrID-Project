<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Profile;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\ComptesVitrineSeeder;
use Database\Seeders\PlanSeeder;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * LES COMPTES VITRINE — des cartes qui ne s'éteignent jamais.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CE QUE CE MÉCANISME RÉPARE
 * ═══════════════════════════════════════════════════════════════════════
 * Une carte de démonstration expire comme celle de n'importe quel client. Sans
 * passerelle de paiement en production, elle ne peut alors être rallumée qu'à
 * la main — et l'on découvre qu'elle est éteinte DEVANT le prospect.
 *
 * C'est exactement ce qui est arrivé le 17 août : l'essai gratuit du compte du
 * propriétaire a expiré, sa carte s'est éteinte, et il a fallu des heures pour
 * la rallumer — en traversant six défauts empilés.
 *
 * Déclarer l'adresse une fois suffit désormais : chaque démarrage reporte
 * l'échéance d'un an et republie la carte.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LES GARDE-FOUS, ET POURQUOI CHACUN EXISTE
 * ═══════════════════════════════════════════════════════════════════════
 * Un mécanisme qui accorde des abonnements sans paiement doit être étroit,
 * lisible et sans surprise. Les tests ci-dessous valent surtout par ce qu'ils
 * INTERDISENT.
 */
class ComptesVitrineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TemplateSeeder::class);
        $this->seed(PlanSeeder::class);
    }

    private function client(string $email, bool $publie = false): User
    {
        $user = User::factory()->create(['email' => $email, 'email_verified_at' => now()]);

        Profile::factory()->create(['user_id' => $user->id, 'is_active' => $publie]);

        return $user;
    }

    private function jouer(string $emails): void
    {
        config(['vitrine.emails' => $emails]);

        $this->seed(ComptesVitrineSeeder::class);
    }

    // =======================================================================
    // CE QUE LE MÉCANISME FAIT
    // =======================================================================

    /** LE TEST QUI COMPTE : la carte déclarée est en ligne, sans intervention. */
    public function test_a_declared_account_ends_up_publicly_visible(): void
    {
        $user = $this->client('vitrine@exemple.sn');

        $this->assertFalse($user->profile->isPubliclyVisible());

        $this->jouer('vitrine@exemple.sn');

        $this->assertTrue($user->fresh()->profile->isPubliclyVisible());
    }

    /** Un abonnement EXPIRÉ est relevé, pas contourné. */
    public function test_an_expired_subscription_is_pushed_forward(): void
    {
        $user = $this->client('vitrine@exemple.sn', publie: true);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => Plan::where('slug', 'essai-gratuit')->value('id'),
            'status' => Subscription::STATUS_ACTIVE,
            'ends_at' => now()->subMonth(),
        ]);

        $this->assertFalse($user->hasActiveSubscription());

        $this->jouer('vitrine@exemple.sn');

        $this->assertTrue($user->fresh()->hasActiveSubscription());
    }

    /** Plusieurs adresses, séparées par des virgules, espaces compris. */
    public function test_it_reads_a_comma_separated_list(): void
    {
        $une = $this->client('une@exemple.sn');
        $deux = $this->client('deux@exemple.sn');

        $this->jouer(' une@exemple.sn , DEUX@exemple.sn ');

        $this->assertTrue($une->fresh()->profile->isPubliclyVisible());
        $this->assertTrue($deux->fresh()->profile->isPubliclyVisible());
    }

    // =======================================================================
    // CE QU'IL S'INTERDIT — la partie qui compte vraiment
    // =======================================================================

    /**
     * SANS DÉCLARATION, IL NE FAIT RIEN.
     *
     * C'est ce qui sépare un mécanisme d'exploitation d'une porte dérobée.
     */
    public function test_without_the_variable_nothing_happens(): void
    {
        $user = $this->client('personne@exemple.sn');

        $this->jouer('');

        $this->assertFalse($user->fresh()->profile->isPubliclyVisible());
        $this->assertSame(0, Subscription::count());
    }

    /** Une adresse inconnue ne fabrique PAS de compte en production. */
    public function test_an_unknown_address_creates_no_account(): void
    {
        $this->jouer('fantome@exemple.sn');

        $this->assertSame(0, User::where('email', 'fantome@exemple.sn')->count());
        $this->assertSame(0, Subscription::count());
    }

    /**
     * UNE CARTE SUSPENDUE N'EST PAS REPUBLIÉE.
     *
     * La suspension vient d'un humain, sur décision. Qu'un démarrage
     * d'application la lève reviendrait à effacer cette décision toutes les
     * nuits, sans que personne ne s'en aperçoive.
     */
    public function test_it_never_republishes_a_suspended_card(): void
    {
        $user = $this->client('suspendu@exemple.sn');

        $user->profile->forceFill([
            'is_active' => false,
            'deactivated_at' => now(),
            'deactivated_reason' => 'Contenu signalé',
        ])->save();

        $this->jouer('suspendu@exemple.sn');

        $this->assertFalse((bool) $user->fresh()->profile->is_active);
    }

    /**
     * AUCUN CHIFFRE D'AFFAIRES INVENTÉ.
     *
     * L'abonnement est posé sur la formule GRATUITE et aucun paiement n'est
     * écrit. Une vitrine qui gonflerait les recettes ferait piloter
     * l'entreprise sur des chiffres faux.
     */
    public function test_it_invents_no_revenue(): void
    {
        $user = $this->client('vitrine@exemple.sn');

        $this->jouer('vitrine@exemple.sn');

        $abonnement = $user->fresh()->subscriptions()->firstOrFail();

        $this->assertTrue($abonnement->plan->isFree());
        $this->assertSame(0, Payment::count());
    }

    /**
     * REJOUABLE SANS EMPILER. Ce seeder tourne à CHAQUE démarrage : sans
     * cette garantie, un an de déploiements laisserait des centaines de lignes
     * sur le même compte et les écrans d'abonnements deviendraient illisibles.
     */
    public function test_running_it_again_stacks_nothing(): void
    {
        $user = $this->client('vitrine@exemple.sn');

        $this->jouer('vitrine@exemple.sn');
        $this->jouer('vitrine@exemple.sn');
        $this->jouer('vitrine@exemple.sn');

        $this->assertSame(1, $user->fresh()->subscriptions()->count());
    }

    /** Et un compte non déclaré reste rigoureusement intact. */
    public function test_an_undeclared_account_is_left_alone(): void
    {
        $vitrine = $this->client('vitrine@exemple.sn');
        $ordinaire = $this->client('cliente@exemple.sn');

        $this->jouer('vitrine@exemple.sn');

        $this->assertTrue($vitrine->fresh()->profile->isPubliclyVisible());
        $this->assertFalse($ordinaire->fresh()->profile->isPubliclyVisible());
        $this->assertSame(0, $ordinaire->fresh()->subscriptions()->count());
    }
}
