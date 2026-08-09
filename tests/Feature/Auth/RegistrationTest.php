<?php

namespace Tests\Feature\Auth;

use App\Models\PendingRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    /**
     * Flux double opt-in : l'inscription NE crée PAS de compte.
     * Elle dépose une demande en attente et redirige vers la page de confirmation.
     */
    public function test_registration_does_not_create_user_and_redirects_to_pending(): void
    {
        Mail::fake();

        $token = 'idem-token';

        $response = $this->withSession(['registration.idem' => $token])
            ->post('/register', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'phone' => '77 383 13 64',
                'password' => 'motdepasse-solide-123',
                'password_confirmation' => 'motdepasse-solide-123',
                '_idem' => $token,
            ]);

        $response->assertRedirect(route('registration.pending'));

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseHas('pending_registrations', ['email' => 'test@example.com']);
    }

    /**
     * Le clic sur le lien de confirmation crée le compte, connecte l'utilisateur
     * et vide la demande en attente.
     */
    public function test_confirmation_link_creates_the_account(): void
    {
        $raw = Str::random(64);

        PendingRegistration::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '+221773831364',
            'password' => Hash::make('motdepasse-solide-123'),
            'token_hash' => hash('sha256', $raw),
            'expires_at' => Carbon::now()->addHour(),
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'resend_count' => 0,
            'last_sent_at' => Carbon::now(),
            'created_at' => Carbon::now(),
        ]);

        $response = $this->get(route('registration.confirm', ['token' => $raw]));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $user = User::firstOrFail();
        $this->assertSame('test@example.com', $user->email);
        $this->assertSame('+221773831364', $user->phone);
        $this->assertNotNull($user->email_verified_at);

        $this->assertDatabaseCount('pending_registrations', 0);
    }

    /**
     * Jeton expiré : aucun compte créé, renvoi vers la page dédiée.
     */
    public function test_expired_token_does_not_create_account(): void
    {
        $raw = Str::random(64);

        PendingRegistration::create([
            'name' => 'Test User',
            'email' => 'expire@example.com',
            'phone' => '+221773831364',
            'password' => Hash::make('motdepasse-solide-123'),
            'token_hash' => hash('sha256', $raw),
            'expires_at' => Carbon::now()->subMinute(),
            'resend_count' => 0,
            'created_at' => Carbon::now()->subHours(2),
        ]);

        $this->get(route('registration.confirm', ['token' => $raw]))
            ->assertRedirect(route('registration.expired'));

        // La page dédiée s'ouvre, adresse préremplie pour relancer en une saisie.
        $this->get(route('registration.expired'))
            ->assertOk()
            ->assertSee('expire', false);

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('pending_registrations', 0);
    }
}
