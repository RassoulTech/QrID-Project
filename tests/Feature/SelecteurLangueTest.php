<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Langue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * LE SÉLECTEUR DE LANGUE.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * DEUX MÉMOIRES, ET LA SECONDE N'EST PAS UN CONFORT
 * ═══════════════════════════════════════════════════════════════════════
 * Le COMPTE porte la préférence d'un client : il la retrouve sur son
 * téléphone comme sur son poste. Et surtout, c'est elle que liront les
 * E-MAILS — un rappel d'échéance part hors session, sans aucun cookie à
 * consulter. Sans la colonne en base, tous les messages partiraient en
 * français, y compris à qui a choisi l'anglais.
 *
 * Le COOKIE sert au visiteur, qui n'a aucun compte où écrire quoi que ce
 * soit. Sans lui, le sélecteur serait inutilisable sur la landing.
 *
 * Il est posé dans les DEUX cas : c'est ce qui rend le premier rendu après
 * connexion déjà dans la bonne langue, avant que la session ne soit lue.
 */
class SelecteurLangueTest extends TestCase
{
    use RefreshDatabase;

    // =======================================================================
    // LE VISITEUR
    // =======================================================================

    /** Le français par défaut, sans rien demander à personne. */
    public function test_french_is_the_default(): void
    {
        $this->assertSame(Langue::FRANCAIS, Langue::courante());
    }

    public function test_a_guest_can_switch_and_it_sticks_in_a_cookie(): void
    {
        $this->from(route('home'))
            ->post(route('preferences.langue'), ['langue' => Langue::ANGLAIS])
            ->assertRedirect(route('home'))
            ->assertCookie(Langue::nomDuCookie(), Langue::ANGLAIS);
    }

    /** Une langue inconnue est refusée, pas appliquée en silence. */
    public function test_an_unknown_language_is_refused(): void
    {
        $this->from(route('home'))
            ->post(route('preferences.langue'), ['langue' => 'zz'])
            ->assertSessionHasErrors('langue');
    }

    // =======================================================================
    // LE COMPTE
    // =======================================================================

    /**
     * POUR UN COMPTE, LES DEUX MÉMOIRES SONT ÉCRITES.
     *
     * La base sert aux e-mails, le cookie sert au premier rendu.
     */
    public function test_switching_while_logged_in_writes_both_memories(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('preferences.langue'), ['langue' => Langue::ANGLAIS])
            ->assertCookie(Langue::nomDuCookie(), Langue::ANGLAIS);

        $this->assertSame(Langue::ANGLAIS, $user->fresh()->locale);
    }

    /** La préférence du compte l'emporte sur le cookie. */
    public function test_the_account_wins_over_the_cookie(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'locale' => Langue::ANGLAIS,
        ]);

        $this->actingAs($user);

        $this->assertSame(Langue::ANGLAIS, Langue::courante());
    }

    // =======================================================================
    // LE RENDU
    // =======================================================================

    /**
     * LA LANGUE EST POSÉE AVANT LE PREMIER RENDU.
     *
     * Une bascule faite après affichage laisserait voir la page dans
     * l'ancienne langue puis la retraduirait sous les yeux — une seconde
     * entière sur une 3G.
     */
    public function test_the_served_html_is_already_translated(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'locale' => Langue::ANGLAIS,
        ]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $this->assertSame(Langue::ANGLAIS, app()->getLocale());
    }

    /** Le sélecteur est présent sur les pages publiques comme authentifiées. */
    public function test_the_selector_is_everywhere(): void
    {
        $this->get(route('home'))->assertOk()->assertSee('preferences/langue', false);
        $this->get(route('login'))->assertOk()->assertSee('preferences/langue', false);

        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('preferences/langue', false);
    }

    /**
     * LE FORMULAIRE EST EN POST.
     *
     * Un GET serait suivi par les robots d'indexation et les préchargeurs de
     * navigateur, qui basculeraient la langue de visiteurs n'ayant rien
     * demandé — et pollueraient les pages en cache.
     */
    public function test_the_switch_refuses_a_get(): void
    {
        $this->get('/preferences/langue')->assertStatus(405);
    }
}
