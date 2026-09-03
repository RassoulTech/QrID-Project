<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Profile;
use App\Models\ProfileEvent;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * LE COMPTEUR DE PARTAGES.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * CE QU'IL ENREGISTRE, ET CE QU'IL REFUSE DE PRÉTENDRE
 * ═══════════════════════════════════════════════════════════════════════════
 * Un partage INITIÉ : quelqu'un a appuyé, WhatsApp ou la feuille du système
 * s'est ouvert. Ce qui se passe ensuite échappe entièrement à l'application —
 * elle ne voit ni le message, ni le destinataire, et ne saura jamais si
 * l'envoi a eu lieu.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * UNE ROUTE PUBLIQUE QUI ÉCRIT EN BASE
 * ═══════════════════════════════════════════════════════════════════════════
 * C'est la seule du produit dans ce cas, et elle demande donc trois choses
 * qu'aucun test ne doit laisser filer :
 *
 *   · elle n'accepte QUE les canaux déclarés — sinon la colonne se remplit
 *     de valeurs inventées par qui veut ;
 *   · elle ne dit RIEN sur l'existence d'un slug — sinon on énumère les
 *     cartes du produit une par une ;
 *   · elle n'écrit rien pour une carte hors ligne.
 */
class SuiviPartageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Une carte RÉELLEMENT visible du public.
     *
     * `is_active` ne suffit pas : `isPubliclyVisible()` exige en plus un
     * abonnement en cours. Une carte publiée dont l'abonnement a expiré
     * n'est plus consultable — et n'a donc rien à compter.
     */
    private function carteEnLigne(): Profile
    {
        $plan = Plan::factory()->create(['slug' => 'standard', 'duration_days' => 30]);
        $user = User::factory()->create();

        Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'ends_at' => now()->addDays(20),
        ]);

        return Profile::factory()->create([
            'user_id' => $user->id,
            'slug' => 'awa-ndiaye',
            'is_active' => true,
        ]);
    }

    // =======================================================================
    // LE CAS NOMINAL
    // =======================================================================

    public function test_a_share_is_recorded_with_its_channel(): void
    {
        $carte = $this->carteEnLigne();

        $this->post(route('profile.partage', $carte->slug), ['canal' => 'whatsapp'])
            ->assertNoContent();

        $evenement = ProfileEvent::where('type', ProfileEvent::TYPE_SHARE)->firstOrFail();

        $this->assertSame($carte->id, $evenement->profile_id);
        $this->assertSame('whatsapp', $evenement->canal);
    }

    /** Chaque canal déclaré est accepté — la liste n'est pas décorative. */
    public function test_every_declared_channel_is_accepted(): void
    {
        $carte = $this->carteEnLigne();

        foreach (ProfileEvent::CANAUX as $canal) {
            $this->post(route('profile.partage', $carte->slug), ['canal' => $canal])
                ->assertNoContent();
        }

        $this->assertSame(
            count(ProfileEvent::CANAUX),
            ProfileEvent::where('type', ProfileEvent::TYPE_SHARE)->count(),
        );
    }

    /**
     * L'ADRESSE N'EST JAMAIS CONSERVÉE EN CLAIR — seulement son empreinte,
     * suffisante pour dédoublonner, inutilisable pour identifier quelqu'un.
     */
    public function test_the_visitor_ip_is_never_stored_in_clear(): void
    {
        $carte = $this->carteEnLigne();

        $this->post(route('profile.partage', $carte->slug), ['canal' => 'copie']);

        $evenement = ProfileEvent::where('type', ProfileEvent::TYPE_SHARE)->firstOrFail();

        $this->assertNotNull($evenement->ip_hash);
        $this->assertSame(64, mb_strlen($evenement->ip_hash), 'Attendu une empreinte sha256.');
        $this->assertStringNotContainsString('127.0.0.1', (string) $evenement->ip_hash);
    }

    // =======================================================================
    // CE QUI EST REFUSÉ
    // =======================================================================

    /**
     * UN CANAL INVENTÉ EST REFUSÉ.
     *
     * Sans cette borne, n'importe qui remplit la colonne de valeurs de son
     * choix, et la ventilation par canal ne veut plus rien dire.
     */
    public function test_an_unknown_channel_is_refused(): void
    {
        $carte = $this->carteEnLigne();

        $this->postJson(route('profile.partage', $carte->slug), ['canal' => 'pigeon'])
            ->assertStatus(422);

        $this->assertSame(0, ProfileEvent::where('type', ProfileEvent::TYPE_SHARE)->count());
    }

    public function test_a_missing_channel_is_refused(): void
    {
        $carte = $this->carteEnLigne();

        $this->postJson(route('profile.partage', $carte->slug), [])
            ->assertStatus(422);
    }

    /**
     * UN SLUG INCONNU REND 204, JAMAIS 404.
     *
     * La route est publique et sans authentification. Un 404 permettrait de
     * demander « ce slug existe-t-il ? » autant de fois qu'on veut, et
     * d'énumérer les cartes du produit une par une. Le navigateur, lui, n'a
     * rien à faire de la réponse : il envoie et poursuit.
     */
    public function test_an_unknown_slug_says_nothing_and_records_nothing(): void
    {
        $this->post(route('profile.partage', 'carte-qui-nexiste-pas'), ['canal' => 'whatsapp'])
            ->assertNoContent();

        $this->assertSame(0, ProfileEvent::count());
    }

    /**
     * UNE CARTE HORS LIGNE NE COMPTE PAS.
     *
     * Elle n'est pas consultable ; un partage la concernant ne peut venir
     * que d'une adresse devinée ou d'un script. Et la réponse reste la même
     * que pour un slug inconnu, pour la même raison.
     */
    public function test_an_offline_card_records_nothing(): void
    {
        // Elle appartient à un abonné en règle : seul `is_active` la
        // distingue. Sans cela le test passerait pour la mauvaise raison —
        // l'abonnement absent — et ne dirait rien de la publication.
        $carte = $this->carteEnLigne();
        $carte->update(['is_active' => false]);

        $this->post(route('profile.partage', $carte->slug), ['canal' => 'whatsapp'])
            ->assertNoContent();

        $this->assertSame(0, ProfileEvent::count());
    }

    // =======================================================================
    // LA LIMITE DE CADENCE
    // =======================================================================

    /**
     * ELLE EST LE SEUL CONTRÔLE, PUISQUE LA ROUTE EST SANS JETON CSRF.
     *
     * Le CSRF protège d'une action AUTHENTIFIÉE usurpée ; ici il n'y a ni
     * authentification ni victime, et le jeton obligerait à ouvrir une
     * session pour chaque visiteur anonyme de la page la plus fréquentée du
     * produit. La cadence, elle, borne réellement ce qu'un script peut faire.
     */
    public function test_the_route_is_rate_limited(): void
    {
        $carte = $this->carteEnLigne();

        $intergiciels = collect(Route::getRoutes())
            ->first(fn ($r) => $r->getName() === 'profile.partage')
            ->gatherMiddleware();

        $this->assertContains('throttle:20,1', $intergiciels,
            'La route écrit en base sans jeton CSRF : la limite de cadence est '.
            'le seul contrôle qui borne un script.');
    }
}
