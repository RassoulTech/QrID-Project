<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * LA ROUTE DE RÉVEIL — la plus légère du produit, et elle doit le rester.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * À QUOI ELLE SERT
 * ═══════════════════════════════════════════════════════════════════════════
 * Render arrête un service gratuit après quinze minutes sans requête, et le
 * réveil prend une cinquantaine de secondes — pendant lesquelles le premier
 * visiteur à scanner une carte attend devant un écran blanc, et conclut le
 * plus souvent que le lien est mort.
 *
 * Un service de cron externe appelle cette adresse toutes les dix minutes.
 * Le conteneur ne s'endort jamais.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * CE QUE CE FICHIER PROTÈGE
 * ═══════════════════════════════════════════════════════════════════════════
 * Sa LÉGÈRETÉ, qui est sa seule raison d'être. Elle sera appelée six fois par
 * heure, indéfiniment. Le jour où quelqu'un y ajoute « juste une petite
 * vérification en base », ce sont 52 000 requêtes par an pour une réponse
 * que personne ne lit.
 *
 * C'est le genre d'ajout qui paraît anodin et qu'aucune relecture n'attrape :
 * la route continue de fonctionner, elle coûte simplement de plus en plus.
 */
class ReveilTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_answers_without_touching_the_database(): void
    {
        // Un premier appel amorce l'application : sans lui, on compterait les
        // requêtes de démarrage plutôt que celles de la route.
        $this->get(route('reveil'));

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->get(route('reveil'))->assertOk();

        $requetes = DB::getQueryLog();

        $this->assertSame([], $requetes,
            'La route de réveil interroge la base. Appelée six fois par heure, '.
            "cela fait 52 000 requêtes par an pour une réponse que personne ne lit :\n  - ".
            implode("\n  - ", array_map(fn ($r) => $r['query'], $requetes)));
    }

    /** Elle rend un corps minuscule : c'est un signe de vie, pas une page. */
    public function test_it_returns_almost_nothing(): void
    {
        $corps = $this->get(route('reveil'))->assertOk()->getContent();

        $this->assertLessThan(32, mb_strlen($corps),
            'La route de réveil rend une page entière au lieu de deux caractères.');
    }

    /**
     * AUCUN INTERMÉDIAIRE NE DOIT RÉPONDRE À SA PLACE.
     *
     * Un ping servi depuis un cache — celui du service de cron, d'un proxy,
     * de Render lui-même — ne réveillerait rien du tout. La route
     * fonctionnerait en apparence et le conteneur continuerait de dormir.
     */
    public function test_it_forbids_any_caching(): void
    {
        $reponse = $this->get(route('reveil'))->assertOk();

        $this->assertStringContainsString('no-store',
            (string) $reponse->headers->get('Cache-Control'),
            'Un ping mis en cache ne réveille rien : le conteneur dormirait '.
            'pendant que le service de cron croit le tenir éveillé.');
    }

    /** Elle n'a rien à faire dans un moteur de recherche. */
    public function test_it_stays_out_of_search_engines(): void
    {
        $this->get(route('reveil'))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    /**
     * ELLE EST OUVERTE, MAIS BORNÉE.
     *
     * Sans jeton, délibérément : elle ne lit rien, n'écrit rien et ne révèle
     * rien. Un jeton compliquerait la configuration du service de cron sans
     * écarter la moindre attaque — n'importe qui peut de toute façon appeler
     * la page d'accueil pour obtenir le même effet.
     *
     * La cadence borne l'usage plutôt que de l'interdire.
     */
    public function test_it_is_rate_limited(): void
    {
        $intergiciels = collect(Route::getRoutes())
            ->first(fn ($r) => $r->getName() === 'reveil')
            ->gatherMiddleware();

        $this->assertContains('throttle:60,1', $intergiciels,
            'La route est ouverte et sans limite de cadence : elle devient un '.
            'robinet pour qui veut occuper le conteneur.');
    }

    /** Et un visiteur non connecté y accède — c'est tout son intérêt. */
    public function test_a_guest_can_reach_it(): void
    {
        $this->get(route('reveil'))->assertOk();
    }
}
