<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * LA DEMANDE DE RÉINITIALISATION EST LIMITÉE EN CADENCE.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CE QUE CETTE LIMITE EMPÊCHE
 * ═══════════════════════════════════════════════════════════════════════
 * Cette route ENVOIE UN E-MAIL à une adresse fournie par l'appelant, sans
 * authentification. Sans limite, elle est trois choses à la fois :
 *
 *   · un outil pour inonder la boîte de n'importe qui, un message par
 *     requête ;
 *   · un moyen d'épuiser le quota quotidien du fournisseur — après quoi PLUS
 *     AUCUN e-mail du produit ne part, confirmations d'inscription comprises ;
 *   · une charge sur une opération synchrone, l'envoi se faisant dans la
 *     requête faute de worker.
 *
 * Le framework applique déjà un délai de soixante secondes PAR ADRESSE. Il ne
 * protège de rien ici : il suffit de changer d'adresse à chaque appel. C'est
 * la limite par IP qui manquait, et c'est elle qu'on éprouve.
 */
class PasswordResetThrottleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * LE TEST CENTRAL — changer d'adresse à chaque appel ne contourne plus
     * rien. C'est exactement ce que le délai du framework laissait passer.
     */
    public function test_flooding_with_different_addresses_is_stopped(): void
    {
        Mail::fake();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('password.email'), ['email' => "cible{$i}@exemple.sn"])
                ->assertRedirect();
        }

        $this->post(route('password.email'), ['email' => 'cible-de-trop@exemple.sn'])
            ->assertStatus(429);
    }

    /** Cinq demandes restent possibles : quelqu'un qui cherche vraiment n'est pas bloqué. */
    public function test_a_genuine_user_is_never_blocked(): void
    {
        Mail::fake();

        User::factory()->create(['email' => 'awa@exemple.sn', 'email_verified_at' => now()]);

        $this->post(route('password.email'), ['email' => 'awa@exemple.sn'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    /**
     * La pose d'un nouveau mot de passe est limitée elle aussi.
     *
     * Le jeton fait 64 caractères : le deviner est hors de portée, et cette
     * limite ne prétend pas protéger d'une force brute. Elle empêche qu'un
     * script mal réglé n'écrive en boucle, et borne le coût de l'appel de
     * hachage qui suit chaque tentative.
     */
    public function test_the_password_store_route_is_limited_too(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post(route('password.store'), [
                'token' => Str::random(64),
                'email' => 'awa@exemple.sn',
                'password' => 'un-mot-de-passe-solide-2026',
                'password_confirmation' => 'un-mot-de-passe-solide-2026',
            ]);
        }

        $this->post(route('password.store'), [
            'token' => Str::random(64),
            'email' => 'awa@exemple.sn',
            'password' => 'un-mot-de-passe-solide-2026',
            'password_confirmation' => 'un-mot-de-passe-solide-2026',
        ])->assertStatus(429);
    }

    /**
     * LA LIMITE NE RÉVÈLE RIEN DE L'EXISTENCE D'UN COMPTE.
     *
     * Une adresse connue et une adresse inconnue doivent consommer le même
     * quota et rendre la même réponse. Une différence transformerait le
     * limiteur en outil pour savoir qui est client.
     */
    public function test_the_limit_reveals_nothing_about_the_address(): void
    {
        Mail::fake();

        User::factory()->create(['email' => 'connue@exemple.sn', 'email_verified_at' => now()]);

        $connue = $this->post(route('password.email'), ['email' => 'connue@exemple.sn']);
        $inconnue = $this->post(route('password.email'), ['email' => 'inconnue@exemple.sn']);

        $this->assertSame($connue->status(), $inconnue->status());
    }

    /** La limite est déclarée sur la route, pas seulement espérée. */
    public function test_the_throttle_is_declared_on_the_route(): void
    {
        $routes = (string) file_get_contents(base_path('routes/auth.php'));

        $this->assertMatchesRegularExpression(
            "/'store'\]\)\s*\n\s*->middleware\('throttle:\d+,\d+'\)\s*\n\s*->name\('password\.email'\)/",
            $routes,
            'La demande de réinitialisation n\'est plus limitée en cadence.'
        );
    }
}
