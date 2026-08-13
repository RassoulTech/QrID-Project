<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Support\DestinatairesEquipe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * QUI REÇOIT LES ALERTES DE L'ÉQUIPE.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CE FICHIER EXISTE À CAUSE D'UNE PANNE RÉELLE
 * ═══════════════════════════════════════════════════════════════════════
 * Les messages du formulaire de contact ne partaient pas, et la cause n'avait
 * rien à voir avec le formulaire.
 *
 * AdminDemoSeeder crée deux administrateurs sur un domaine fictif, pour que le
 * journal d'audit de l'espace admin distingue plusieurs auteurs. Les alertes
 * partaient donc vers trois adresses, dont deux inexistantes — et chez la
 * plupart des fournisseurs, UN SEUL destinataire invalide fait rejeter TOUT
 * LE MESSAGE. Les deux adresses fictives empêchaient l'unique adresse réelle
 * de recevoir quoi que ce soit.
 *
 * Le défaut ne se voyait nulle part : la suite était verte, le message était
 * enregistré en base, la page affichait sa confirmation. Seul l'envoi réel le
 * révélait.
 */
class TeamRecipientsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * LE TEST CENTRAL. Deux adresses de démonstration ne doivent plus
     * empêcher l'adresse réelle de recevoir.
     */
    public function test_demo_addresses_never_poison_a_real_recipient(): void
    {
        User::factory()->create(['role' => User::ROLE_ADMIN, 'email' => 'reel@qrid.sn']);
        User::factory()->create(['role' => User::ROLE_ADMIN, 'email' => 'awa.diop@qrid-demo.sn']);
        User::factory()->create(['role' => User::ROLE_ADMIN, 'email' => 'ousmane.ba@qrid-demo.sn']);

        $destinataires = DestinatairesEquipe::alertes();

        $this->assertSame(['reel@qrid.sn'], $destinataires);
    }

    /** Le domaine écarté est configurable, pas gravé dans le code. */
    public function test_the_excluded_domains_are_configurable(): void
    {
        config(['notifications.excluded_domains' => ['exemple.test']]);

        User::factory()->create(['role' => User::ROLE_ADMIN, 'email' => 'garde@qrid.sn']);
        User::factory()->create(['role' => User::ROLE_ADMIN, 'email' => 'jete@exemple.test']);

        $this->assertSame(['garde@qrid.sn'], DestinatairesEquipe::alertes());
    }

    /** Une adresse mal formée est écartée, sans faire échouer la résolution. */
    public function test_a_malformed_address_is_dropped(): void
    {
        User::factory()->create(['role' => User::ROLE_ADMIN, 'email' => 'valide@qrid.sn']);

        config(['notifications.admin_recipients' => ['valide@qrid.sn', 'pas-une-adresse', '']]);

        $this->assertSame(['valide@qrid.sn'], DestinatairesEquipe::alertes());
    }

    /** Les doublons sont réduits : deux fois la même adresse, un seul envoi. */
    public function test_duplicates_are_collapsed(): void
    {
        config(['notifications.admin_recipients' => ['a@qrid.sn', 'A@QRID.SN', 'a@qrid.sn']]);

        $this->assertSame(['a@qrid.sn'], DestinatairesEquipe::alertes());
    }

    /** La liste explicite l'emporte sur les comptes en base. */
    public function test_the_explicit_list_wins(): void
    {
        User::factory()->create(['role' => User::ROLE_ADMIN, 'email' => 'perso@qrid.sn']);

        config(['notifications.admin_recipients' => ['support@qrid.sn']]);

        $this->assertSame(['support@qrid.sn'], DestinatairesEquipe::alertes());
    }

    /**
     * SANS LISTE EXPLICITE, ON LIT LA BASE.
     *
     * Ce n'est pas une commodité : une liste figée dans une variable
     * d'environnement se périme au premier changement d'équipe, et le jour où
     * quelqu'un part, ses alertes partent avec lui sans que personne ne s'en
     * aperçoive.
     */
    public function test_without_an_explicit_list_the_database_is_read(): void
    {
        config(['notifications.admin_recipients' => []]);

        User::factory()->create(['role' => User::ROLE_ADMIN, 'email' => 'admin@qrid.sn']);
        User::factory()->create(['role' => User::ROLE_USER, 'email' => 'client@qrid.sn']);

        $this->assertSame(['admin@qrid.sn'], DestinatairesEquipe::alertes());
    }

    /** Le formulaire de contact vise l'adresse de support quand elle existe. */
    public function test_the_contact_form_prefers_the_support_address(): void
    {
        User::factory()->create(['role' => User::ROLE_ADMIN, 'email' => 'perso@qrid.sn']);
        config(['landing.support.email' => 'support@qrid.sn']);

        $this->assertSame(['support@qrid.sn'], DestinatairesEquipe::contact());
    }

    /** À défaut, il retombe sur les mêmes destinataires que les alertes. */
    public function test_the_contact_form_falls_back_to_the_team(): void
    {
        config(['landing.support.email' => null]);

        User::factory()->create(['role' => User::ROLE_ADMIN, 'email' => 'admin@qrid.sn']);

        $this->assertSame(['admin@qrid.sn'], DestinatairesEquipe::contact());
    }

    /**
     * UNE SEULE RÉSOLUTION POUR TOUT LE PRODUIT.
     *
     * La règle vivait à deux endroits, avec deux ordres de priorité
     * différents. Deux implémentations d'une même règle finissent toujours par
     * diverger — et ici, la divergence se constatait sur un message qui
     * n'arrive pas.
     */
    public function test_the_rule_lives_in_one_place_only(): void
    {
        foreach ([
            app_path('Services/AdminNotifier.php'),
            app_path('Http/Controllers/ContactController.php'),
        ] as $fichier) {
            $source = (string) file_get_contents($fichier);

            $this->assertStringContainsString('DestinatairesEquipe::', $source);
            $this->assertStringNotContainsString(
                'User::admins()',
                $source,
                basename($fichier).' résout à nouveau les destinataires lui-même.'
            );
        }
    }

    /** Aucun destinataire : la situation est anormale et doit être traçable. */
    public function test_no_recipient_at_all_returns_an_empty_list(): void
    {
        config(['notifications.admin_recipients' => []]);

        $this->assertSame([], DestinatairesEquipe::alertes());
    }
}
