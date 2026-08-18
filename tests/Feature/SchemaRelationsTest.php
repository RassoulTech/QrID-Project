<?php

namespace Tests\Feature;

use App\Models\AdminAction;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Profile;
use App\Models\ProfileEvent;
use App\Models\SocialLink;
use App\Models\Subscription;
use App\Models\Template;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Database\Seeders\PlanSeeder;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Vérifie que le schéma tient : relations chargeables, règles métier
 * respectées, et absence de N+1 sur les listes.
 */
class SchemaRelationsTest extends TestCase
{
    use RefreshDatabase;

    private function seedReferenceData(): void
    {
        $this->seed(TemplateSeeder::class);
        $this->seed(PlanSeeder::class);
    }

    // -----------------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------------

    public function test_all_relations_load_without_error(): void
    {
        $this->seedReferenceData();

        $user = User::factory()->create();
        $template = Template::first();
        $plan = Plan::where('slug', 'standard')->firstOrFail();

        $profile = Profile::factory()->published()->create([
            'user_id' => $user->id,
            'template_id' => $template->id,
        ]);

        SocialLink::factory()->count(3)->sequence(
            ['platform' => 'linkedin', 'position' => 0],
            ['platform' => 'facebook', 'position' => 1],
            ['platform' => 'instagram', 'position' => 2],
        )->create(['profile_id' => $profile->id]);

        $subscription = Subscription::factory()->active()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
        ]);

        Payment::factory()->successful()->create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
        ]);

        ProfileEvent::factory()->count(5)->create(['profile_id' => $profile->id]);

        AdminAction::factory()->create(['admin_id' => $user->id]);

        // User
        $this->assertInstanceOf(Profile::class, $user->profile);
        $this->assertCount(1, $user->subscriptions);
        $this->assertCount(1, $user->payments);
        $this->assertCount(1, $user->adminActions);

        // Profile
        $this->assertTrue($profile->user->is($user));
        $this->assertTrue($profile->template->is($template));
        $this->assertCount(3, $profile->socialLinks);
        $this->assertCount(5, $profile->events);

        // Liens sociaux triés par position
        $this->assertSame([0, 1, 2], $profile->socialLinks->pluck('position')->all());

        // Subscription
        $this->assertTrue($subscription->user->is($user));
        $this->assertTrue($subscription->plan->is($plan));
        $this->assertCount(1, $subscription->payments);

        // Payment
        $payment = Payment::firstOrFail();
        $this->assertTrue($payment->user->is($user));
        $this->assertTrue($payment->subscription->is($subscription));
    }

    // -----------------------------------------------------------------------
    // Absence de N+1
    // -----------------------------------------------------------------------

    public function test_profile_listing_has_no_n_plus_one(): void
    {
        $this->seedReferenceData();

        // 10 profils, chacun avec utilisateur, modèle et liens sociaux.
        Profile::factory()->count(10)->published()->create()->each(
            fn (Profile $p) => SocialLink::factory()->count(2)->create(['profile_id' => $p->id])
        );

        DB::enableQueryLog();

        $profiles = Profile::with(['user', 'template', 'socialLinks'])->get();

        foreach ($profiles as $profile) {
            $profile->user->name;
            $profile->template?->name;
            $profile->socialLinks->count();
        }

        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        // 1 (profils) + 3 relations = 4 requêtes, quel que soit le nombre de lignes.
        $this->assertLessThanOrEqual(
            5,
            $queries,
            "N+1 détecté : {$queries} requêtes pour 10 profils. Vérifier le eager loading."
        );
    }

    public function test_subscription_listing_has_no_n_plus_one(): void
    {
        $this->seedReferenceData();

        Subscription::factory()->count(10)->active()->create();

        DB::enableQueryLog();

        Subscription::with(['user', 'plan'])->get()->each(function (Subscription $s) {
            $s->user->email;
            $s->plan->name;
        });

        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(4, $queries, "N+1 détecté : {$queries} requêtes.");
    }

    // -----------------------------------------------------------------------
    // Règles métier
    // -----------------------------------------------------------------------

    public function test_profile_is_publicly_visible_only_with_active_subscription(): void
    {
        $this->seedReferenceData();
        $plan = Plan::where('slug', 'standard')->firstOrFail();

        // Publié + abonnement actif → visible
        $ok = User::factory()->create();
        $okProfile = Profile::factory()->published()->create(['user_id' => $ok->id]);
        Subscription::factory()->active()->create(['user_id' => $ok->id, 'plan_id' => $plan->id]);
        $this->assertTrue($okProfile->fresh()->isPubliclyVisible());

        // Publié + abonnement expiré → invisible
        $expired = User::factory()->create();
        $expiredProfile = Profile::factory()->published()->create(['user_id' => $expired->id]);
        Subscription::factory()->expired()->create(['user_id' => $expired->id, 'plan_id' => $plan->id]);
        $this->assertFalse($expiredProfile->fresh()->isPubliclyVisible());

        // Brouillon + abonnement actif → invisible
        $draft = User::factory()->create();
        $draftProfile = Profile::factory()->draft()->create(['user_id' => $draft->id]);
        Subscription::factory()->active()->create(['user_id' => $draft->id, 'plan_id' => $plan->id]);
        $this->assertFalse($draftProfile->fresh()->isPubliclyVisible());

        // La portée doit donner le même résultat que la méthode.
        $this->assertSame(1, Profile::publiclyVisible()->count());
    }

    public function test_slug_can_only_be_changed_once(): void
    {
        $profile = Profile::factory()->create();

        $this->assertTrue($profile->canChangeSlug());

        $profile->markSlugAsChanged();

        $this->assertFalse($profile->fresh()->canChangeSlug());
        $this->assertNotNull($profile->fresh()->slug_changed_at);
    }

    public function test_user_has_only_one_profile(): void
    {
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        // hasOne : la relation ne renvoie jamais une collection.
        $this->assertInstanceOf(Profile::class, $user->profile);
    }

    public function test_amounts_are_integers_never_floats(): void
    {
        $this->seedReferenceData();

        $plan = Plan::where('slug', 'standard')->firstOrFail();
        $this->assertIsInt($plan->price_fcfa);
        $this->assertSame(3500, $plan->price_fcfa);

        $payment = Payment::factory()->successful()->create(['amount_fcfa' => 2500]);
        $this->assertIsInt($payment->fresh()->amount_fcfa);
    }

    // -----------------------------------------------------------------------
    // Intégrité référentielle
    // -----------------------------------------------------------------------

    public function test_payment_survives_user_deletion(): void
    {
        // RÈGLE : un paiement est une pièce comptable. Il ne disparaît jamais.
        $user = User::factory()->create();
        $payment = Payment::factory()->successful()->create([
            'user_id' => $user->id,
            'subscription_id' => null,
        ]);

        $user->delete();

        $payment->refresh();
        $this->assertNull($payment->user_id);          // orphelin…
        $this->assertSame(2500, $payment->amount_fcfa); // …mais intact
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_profile_and_events_are_removed_with_user(): void
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        ProfileEvent::factory()->count(3)->create(['profile_id' => $profile->id]);

        $user->forceDelete();

        // profiles utilise softDeletes : la cascade SQL supprime réellement la ligne.
        $this->assertDatabaseCount('profiles', 0);
        $this->assertDatabaseCount('profile_events', 0);
    }

    public function test_admin_action_survives_admin_deletion(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        AdminAction::factory()->create(['admin_id' => $admin->id, 'action' => 'suspend_profile']);

        $admin->delete();

        $this->assertDatabaseCount('admin_actions', 1);
        $this->assertDatabaseHas('admin_actions', ['admin_id' => null, 'action' => 'suspend_profile']);
    }

    // -----------------------------------------------------------------------
    // Jeu de démonstration
    // -----------------------------------------------------------------------

    public function test_demo_seeder_produces_a_coherent_dataset(): void
    {
        $this->seedReferenceData();
        $this->seed(DemoSeeder::class);

        $this->assertGreaterThanOrEqual(10, User::count());
        $this->assertGreaterThanOrEqual(10, Profile::count());
        $this->assertGreaterThan(0, Subscription::where('status', 'active')->count());
        $this->assertGreaterThan(0, Payment::successful()->count());
        $this->assertGreaterThan(0, ProfileEvent::count());

        // Chaque profil de démonstration doit être chargeable avec ses relations.
        Profile::with(['user', 'template', 'socialLinks', 'events'])->get()
            ->each(function (Profile $p) {
                $this->assertNotNull($p->user);
                $this->assertNotEmpty($p->full_name);
                $this->assertMatchesRegularExpression('/^\+221\d{9}$/', $p->phone);
            });
    }
}
