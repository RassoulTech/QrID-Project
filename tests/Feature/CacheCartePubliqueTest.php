<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Profile;
use App\Models\ProfileEvent;
use App\Models\SocialLink;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Cache\ArrayStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * LE CACHE DE LA CARTE PUBLIQUE.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * POURQUOI CETTE PAGE, ET AUCUNE AUTRE
 * ═══════════════════════════════════════════════════════════════════════════
 * C'est la page qui prend TOUT le trafic du produit : chaque scan de QR Code,
 * chaque lien partagé sur WhatsApp aboutit ici. Et son contenu ne dépend
 * d'aucun visiteur — deux personnes qui ouvrent la même carte voient
 * exactement la même chose.
 *
 * Mesuré : 94 ms de rendu sans cache, 45 ms avec. Le conteneur de production
 * dispose d'un DIXIÈME de processeur, ce qui multiplie ces durées d'autant.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * LES DEUX FAÇONS DONT UN CACHE PEUT NUIRE
 * ═══════════════════════════════════════════════════════════════════════════
 *   IL AVALE LES VISITES   servir un rendu mémorisé sans compter la visite
 *                          ferait s'arrêter net les statistiques d'un client
 *                          dont la carte marche bien — au moment précis où
 *                          elle commence à circuler.
 *
 *   IL SERT DU PÉRIMÉ      une carte modifiée qui continue d'afficher
 *                          l'ancienne version fait croire au porteur que sa
 *                          correction n'a pas été enregistrée.
 *
 * Aucune des deux ne lève d'erreur. Les deux détruisent la confiance.
 */
class CacheCartePubliqueTest extends TestCase
{
    use RefreshDatabase;

    private function carteEnLigne(): Profile
    {
        $plan = Plan::factory()->create(['slug' => 'standard', 'duration_days' => 30]);
        $client = User::factory()->create();

        Subscription::factory()->create([
            'user_id' => $client->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'ends_at' => now()->addDays(20),
        ]);

        return Profile::factory()->create([
            'user_id' => $client->id,
            'first_name' => 'Awa',
            'last_name' => 'Ndiaye',
            'slug' => 'awa-ndiaye',
            'is_active' => true,
        ]);
    }

    private function visites(Profile $carte): int
    {
        return ProfileEvent::where('profile_id', $carte->id)->count();
    }

    // =======================================================================
    // LE CACHE FONCTIONNE
    // =======================================================================

    public function test_the_render_is_memorised(): void
    {
        $carte = $this->carteEnLigne();

        $this->get(route('profile.public', $carte->slug))->assertOk();

        $this->assertNotEmpty(
            array_filter(
                array_keys(Cache::getStore() instanceof ArrayStore ? Cache::getStore()->all() : []),
                fn (string $cle) => str_contains($cle, 'carte:'.$carte->slug),
            ),
            'Aucune entrée de cache pour cette carte : le rendu est refait à chaque visite.',
        );
    }

    /**
     * LE POINT CRITIQUE : LA VISITE EST COMPTÉE À CHAQUE FOIS.
     *
     * Servir un rendu mémorisé sans compter la visite ferait s'arrêter net
     * les statistiques d'un client dont la carte marche bien.
     */
    public function test_every_visit_is_counted_even_when_served_from_cache(): void
    {
        $carte = $this->carteEnLigne();

        foreach (range(1, 4) as $ieme) {
            $this->get(route('profile.public', $carte->slug))->assertOk();
        }

        $this->assertSame(4, $this->visites($carte),
            'Des visites ont été perdues : le cache a avalé le comptage.');
    }

    // =======================================================================
    // L'INVALIDATION
    // =======================================================================

    /**
     * MODIFIER SA CARTE CHANGE CE QUE LE VISITEUR VOIT, TOUT DE SUITE.
     *
     * La clé porte la date de modification du profil : l'invalidation est
     * automatique, sans qu'aucune purge n'ait à être écrite ni retenue.
     */
    public function test_editing_the_card_changes_what_visitors_see(): void
    {
        $carte = $this->carteEnLigne();

        $this->get(route('profile.public', $carte->slug))->assertOk()->assertSee('Awa');

        $carte->update(['first_name' => 'Fatou']);

        $this->get(route('profile.public', $carte->slug))
            ->assertOk()
            ->assertSee('Fatou')
            ->assertDontSee('>Awa<', false);
    }

    /**
     * ET AJOUTER UN LIEN AUSSI.
     *
     * Les liens vivent dans leur propre table : sans `$touches` sur
     * SocialLink, la date du profil ne bougerait pas, le visiteur verrait
     * l'ancienne carte, et le porteur croirait son ajout perdu.
     */
    public function test_adding_a_social_link_changes_what_visitors_see(): void
    {
        $carte = $this->carteEnLigne();

        $this->get(route('profile.public', $carte->slug))->assertOk();
        $avant = $carte->fresh()->updated_at;

        // La date de modification est à la seconde : sans ce décalage, un
        // ajout dans la même seconde produirait la même clé de cache.
        $this->travel(2)->seconds();

        SocialLink::create([
            'profile_id' => $carte->id,
            'platform' => 'instagram',
            'url' => 'https://instagram.com/awa',
            'position' => 1,
        ]);

        $this->assertTrue($carte->fresh()->updated_at->greaterThan($avant),
            "L'ajout d'un lien n'a pas touché son profil : le cache servira ".
            'une carte sans ce lien jusqu\'à son expiration.');
    }

    // =======================================================================
    // CE QUE LA CLÉ DOIT SÉPARER
    // =======================================================================

    /**
     * DEUX LANGUES, DEUX RENDUS.
     *
     * Sans la langue dans la clé, un visiteur anglophone recevrait la page
     * d'un francophone passé avant lui.
     */
    public function test_each_language_gets_its_own_render(): void
    {
        $carte = $this->carteEnLigne();

        $fr = $this->withHeaders(['Accept-Language' => 'fr'])
            ->get(route('profile.public', $carte->slug))->getContent();

        $en = $this->withHeaders(['Accept-Language' => 'en'])
            ->get(route('profile.public', $carte->slug))->getContent();

        $this->assertNotSame($fr, $en,
            'Les deux langues partagent le même rendu mémorisé.');
    }

    // =======================================================================
    // CE QUE LE CACHE NE DOIT JAMAIS SERVIR
    // =======================================================================

    /**
     * UNE CARTE DEVENUE INVISIBLE NE SORT PAS DU CACHE.
     *
     * La vérification de visibilité est faite AVANT toute lecture du cache :
     * une carte dépubliée ou dont l'abonnement a expiré doit répondre comme
     * telle, même si son rendu est encore mémorisé.
     */
    public function test_an_unpublished_card_is_never_served_from_cache(): void
    {
        $carte = $this->carteEnLigne();

        $this->get(route('profile.public', $carte->slug))->assertOk();

        $carte->update(['is_active' => false]);

        $this->get(route('profile.public', $carte->slug))
            ->assertStatus(404);
    }

    /** Idem quand c'est l'abonnement qui s'arrête. */
    public function test_an_expired_subscription_takes_the_card_offline_despite_the_cache(): void
    {
        $carte = $this->carteEnLigne();

        $this->get(route('profile.public', $carte->slug))->assertOk();

        $carte->user->subscriptions()->update(['ends_at' => now()->subDay()]);

        $this->get(route('profile.public', $carte->slug))->assertStatus(404);
    }
}
