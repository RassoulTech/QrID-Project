<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Coût de la connexion.
 *
 * On ne mesure pas des millisecondes ici : sqlite en mémoire ne dit rien du
 * réel. On vérifie ce qui, lui, est déterministe et cause la lenteur —
 * le nombre de requêtes, le coût bcrypt, et l'absence d'e-mail synchrone.
 */
class LoginPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_logging_in_costs_a_handful_of_queries(): void
    {
        User::factory()->create([
            'email' => 'awa@exemple.sn',
            'password' => 'motdepasse-solide-123',
            'email_verified_at' => now(),
        ]);

        $requetes = 0;
        DB::listen(function () use (&$requetes) {
            $requetes++;
        });

        $this->post('/login', [
            'email' => 'awa@exemple.sn',
            'password' => 'motdepasse-solide-123',
        ]);

        // Lecture de l'utilisateur, écriture du remember_token. Au-delà de 5,
        // quelque chose interroge la base sans raison dans le cycle de login.
        $this->assertLessThanOrEqual(5, $requetes, "La connexion a coûté {$requetes} requêtes.");
    }

    public function test_no_mail_is_sent_during_login(): void
    {
        Mail::fake();

        User::factory()->create([
            'email' => 'awa@exemple.sn',
            'password' => 'motdepasse-solide-123',
            'email_verified_at' => now(),
        ]);

        $this->post('/login', [
            'email' => 'awa@exemple.sn',
            'password' => 'motdepasse-solide-123',
        ]);

        // Un envoi synchrone, c'est un aller-retour SMTP dans la requête :
        // plusieurs secondes, et une connexion qui échoue si le serveur tombe.
        Mail::assertNothingSent();
    }

    /** L'adresse est indexée : sans index, la lecture devient un scan complet. */
    public function test_the_email_column_is_indexed(): void
    {
        $indexes = collect(Schema::getIndexes('users'))
            ->flatMap(fn ($i) => $i['columns'])
            ->all();

        $this->assertContains('email', $indexes);
    }

    /**
     * Coût bcrypt.
     *
     * bcrypt est volontairement lent, c'est sa raison d'être. Mais chaque
     * incrément DOUBLE le temps : 12 rounds coûte quatre fois 10, soit
     * l'essentiel d'un budget de 300 ms à lui seul. 10 en développement,
     * 12 en production où la marge existe.
     */
    public function test_the_bcrypt_cost_is_configurable_and_actually_read(): void
    {
        // phpunit.xml impose 4 : si la valeur lue n'est pas 4, c'est que
        // config/hashing.php manque et que le .env n'est jamais consulté.
        $this->assertSame(
            4,
            (int) config('hashing.bcrypt.rounds'),
            'BCRYPT_ROUNDS n\'est pas lu : vérifiez la présence de config/hashing.php.'
        );

        // Et le fichier de configuration branche bien la variable d'environnement.
        $this->assertStringContainsString(
            "env('BCRYPT_ROUNDS'",
            file_get_contents(config_path('hashing.php'))
        );
    }
}
