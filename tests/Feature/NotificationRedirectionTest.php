<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * OÙ UNE NOTIFICATION A LE DROIT DE MENER.
 *
 * Ouvrir une notification redirige. Une redirection dont la destination
 * vient de la base est une redirection ouverte dès l'instant où une donnée
 * saisie par quelqu'un parvient jusqu'à cette colonne — et le lien porte
 * alors le domaine de QrID, ce qui est exactement ce qu'un hameçonnage
 * cherche à emprunter.
 *
 * Aucune notification n'est construite ainsi aujourd'hui. Ces tests
 * existent pour que ça reste vrai.
 */
class NotificationRedirectionTest extends TestCase
{
    use RefreshDatabase;

    private function notification(User $proprietaire, ?string $url): Notification
    {
        return Notification::create([
            'user_id' => $proprietaire->id,
            'type' => 'info',
            'title' => 'Titre',
            'body' => 'Corps',
            'url' => $url,
        ]);
    }

    public function test_an_internal_path_is_followed(): void
    {
        $client = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($client)
            ->get(route('notifications.open', $this->notification($client, '/abonnement')))
            ->assertRedirect('/abonnement');
    }

    public function test_an_absolute_url_on_our_own_host_is_followed(): void
    {
        $client = User::factory()->create(['email_verified_at' => now()]);
        $interne = rtrim((string) config('app.url'), '/').'/abonnement';

        $this->actingAs($client)
            ->get(route('notifications.open', $this->notification($client, $interne)))
            ->assertRedirect($interne);
    }

    /**
     * LE CŒUR : une destination extérieure est refusée, et l'on retombe sur
     * le tableau de bord plutôt que sur un site tiers.
     */
    public function test_an_external_url_is_refused(): void
    {
        $client = User::factory()->create(['email_verified_at' => now()]);

        foreach ([
            'https://exemple-malveillant.test/piege',
            'http://exemple-malveillant.test',
            // Le double slash : une adresse relative au protocole, qui part
            // pourtant sur un autre domaine. C'est le contournement que la
            // vérification « commence par / » laisserait passer si elle
            // était écrite naïvement.
            '//exemple-malveillant.test/piege',
        ] as $piege) {
            $this->actingAs($client)
                ->get(route('notifications.open', $this->notification($client, $piege)))
                ->assertRedirect(route('dashboard'));
        }
    }

    public function test_an_empty_destination_falls_back_to_the_dashboard(): void
    {
        $client = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($client)
            ->get(route('notifications.open', $this->notification($client, null)))
            ->assertRedirect(route('dashboard'));
    }

    /** Ouvrir marque lu — le comportement d'origine ne doit pas bouger. */
    public function test_opening_still_marks_it_read(): void
    {
        $client = User::factory()->create(['email_verified_at' => now()]);
        $alerte = $this->notification($client, '/abonnement');

        $this->actingAs($client)->get(route('notifications.open', $alerte));

        $this->assertNotNull($alerte->fresh()->read_at);
    }
}
