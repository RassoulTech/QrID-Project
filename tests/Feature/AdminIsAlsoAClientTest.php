<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Profile;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * L'EXPLOITANT QUI SE SERT DE SON PROPRE PRODUIT.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LE DÉFAUT
 * ═══════════════════════════════════════════════════════════════════════
 * La liste des clients ne montrait que les comptes dont le RÔLE vaut
 * « user ». Or `ADMIN_EMAIL` promeut le compte du propriétaire à chaque
 * déploiement : le jour où il est devenu administrateur, il a disparu de sa
 * propre liste de clients.
 *
 * Sa fiche devenait introuvable — et avec elle la seule voie pour prolonger
 * son abonnement, débloquer son compte ou lire ses paiements. L'encaissement
 * manuel, qui est la stratégie d'ouverture retenue faute de passerelle, n'était
 * plus praticable sur lui.
 *
 * Constaté en production le 17 août : trois passages sur /admin/clients, aucune
 * fiche ouverte, parce qu'il n'y avait rien à ouvrir.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LA RÈGLE
 * ═══════════════════════════════════════════════════════════════════════
 * Un client est un compte qui a une CARTE, pas un compte d'un certain rôle.
 * Un administrateur sans carte n'est pas un client et reste hors de la liste :
 * l'y faire figurer fausserait les chiffres autant que le regard.
 */
class AdminIsAlsoAClientTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);
    }

    /** LE TEST QUI COMPTE : un administrateur qui a une carte est un client. */
    public function test_an_admin_who_owns_a_card_appears_in_the_client_list(): void
    {
        $proprietaire = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'name' => 'Mouhamed Dione',
            'email' => 'proprietaire@exemple.sn',
            'email_verified_at' => now(),
        ]);

        Profile::factory()->create(['user_id' => $proprietaire->id]);

        $this->actingAs($this->admin)
            ->get(route('admin.clients.index'))
            ->assertOk()
            ->assertSee('proprietaire@exemple.sn', false);
    }

    /**
     * Et sa fiche s'ouvre — c'est elle qui porte la prolongation d'abonnement.
     *
     * Sans cet accès, l'encaissement manuel n'existe pour personne. L'essai
     * EXPIRÉ reproduit exactement l'état de la production : c'est lui qu'il
     * faut pouvoir prolonger.
     */
    public function test_his_file_opens_and_carries_the_extension_form(): void
    {
        $plan = Plan::factory()->create(['slug' => 'essai-gratuit', 'price_fcfa' => 0, 'duration_days' => 15]);

        $proprietaire = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        Profile::factory()->create(['user_id' => $proprietaire->id]);

        Subscription::factory()->create([
            'user_id' => $proprietaire->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'ends_at' => now()->subDays(3),   // essai expiré
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.clients.show', $proprietaire))
            ->assertOk()
            ->assertSee(route('admin.clients.subscription.extend', $proprietaire), false);
    }

    /**
     * L'INVERSE RESTE VRAI — un administrateur SANS carte n'est pas un client.
     *
     * Sans cette limite, les trois comptes d'exploitation gonfleraient la
     * liste et l'on piloterait le produit sur des chiffres faux.
     */
    public function test_an_admin_without_a_card_stays_out_of_the_list(): void
    {
        $exploitant = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'exploitant-sans-carte@exemple.sn',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.clients.index'))
            ->assertOk()
            ->assertDontSee('exploitant-sans-carte@exemple.sn', false);
    }

    /** Non-régression : un client ordinaire reste évidemment listé. */
    public function test_an_ordinary_client_is_still_listed(): void
    {
        $client = User::factory()->create([
            'role' => User::ROLE_USER,
            'email' => 'cliente@exemple.sn',
            'email_verified_at' => now(),
        ]);

        Profile::factory()->create(['user_id' => $client->id]);

        $this->actingAs($this->admin)
            ->get(route('admin.clients.index'))
            ->assertOk()
            ->assertSee('cliente@exemple.sn', false);
    }

    /** Et un compte sans carte ni rôle admin l'est aussi : il vient de s'inscrire. */
    public function test_a_brand_new_account_without_a_card_is_still_listed(): void
    {
        User::factory()->create([
            'role' => User::ROLE_USER,
            'email' => 'inscrite-hier@exemple.sn',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.clients.index'))
            ->assertOk()
            ->assertSee('inscrite-hier@exemple.sn', false);
    }
}
