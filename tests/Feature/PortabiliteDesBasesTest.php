<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * LE MÊME PRODUIT SUR MYSQL, POSTGRESQL ET SQLITE.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI CE FICHIER EXISTE
 * ═══════════════════════════════════════════════════════════════════════
 * La base est passée d'Aiven (MySQL) à Neon (PostgreSQL) le 12 octobre
 * 2026. Le code est resté le même ; ce sont les MOTEURS qui diffèrent, et
 * leurs différences ont une particularité commune : la plupart ne lèvent
 * aucune erreur. Elles changent une réponse.
 *
 *   · MySQL compare sans tenir compte de la casse ni des accents ;
 *     PostgreSQL compare à l'octet près. « ndeye » ne trouvait plus
 *     « Ndèye », « Awa@Gmail.com » ne se connectait plus.
 *   · PostgreSQL refuse un texte qui n'est pas de l'UTF-8 : une image
 *     JPEG envoyée comme texte n'était pas enregistrée.
 *   · PostgreSQL refuse de comparer « abc » à un entier : une adresse
 *     mal formée donnait une erreur 500 au lieu d'une 404.
 *
 * Chaque test ci-dessous porte l'un de ces écarts. La suite complète tourne
 * sur SQLite ET sur PostgreSQL dans l'intégration continue ; c'est sur
 * PostgreSQL que ces tests mordent vraiment.
 */
class PortabiliteDesBasesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);
    }

    // =======================================================================
    // RECHERCHE — casse et accents
    // =======================================================================

    public function test_la_recherche_ignore_la_casse(): void
    {
        User::factory()->create(['name' => 'Awa Ndiaye', 'email' => 'awa@exemple.sn']);

        $this->assertTrue(User::query()->whereLikeSouple('name', '%NDIAYE%')->exists());
        $this->assertTrue(User::query()->whereLikeSouple('name', '%ndiaye%')->exists());
    }

    /**
     * Les accents : la promesse de MySQL, tenue sous PostgreSQL par
     * unaccent(). SQLite ne sait pas la tenir — son LIKE ne replie que
     * l'ASCII — et n'est qu'un moteur de test : on ne l'y vérifie pas.
     */
    public function test_la_recherche_ignore_les_accents(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('SQLite ne replie pas les accents ; MySQL et PostgreSQL, si.');
        }

        User::factory()->create(['name' => 'Ndèye Sène', 'email' => 'ndeye@exemple.sn']);

        $this->assertTrue(User::query()->whereLikeSouple('name', '%ndeye sene%')->exists());
        $this->assertTrue(User::query()->whereLikeSouple('name', '%NDÈYE%')->exists());
    }

    public function test_la_recherche_admin_trouve_un_client_tape_en_minuscules(): void
    {
        User::factory()->create([
            'name' => 'Moussa Diop',
            'email' => 'moussa@exemple.sn',
            'role' => User::ROLE_USER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.clients.index', ['q' => 'moussa diop']))
            ->assertOk()
            ->assertSee('moussa@exemple.sn');
    }

    // =======================================================================
    // CONNEXION — l'adresse saisie avec des majuscules
    // =======================================================================

    public function test_une_adresse_saisie_avec_des_majuscules_se_connecte(): void
    {
        $user = User::factory()->create(['email' => 'awa@exemple.sn']);

        $this->post('/login', [
            'email' => '  Awa@Exemple.SN ',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    // =======================================================================
    // OCTETS — une image n'est pas du texte
    // =======================================================================

    /**
     * Des octets qui ne sont PAS de l'UTF-8 et qui contiennent un octet nul :
     * exactement ce qu'un PostgreSQL refuse comme texte.
     */
    private function octetsBinaires(): string
    {
        return "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00".random_bytes(2048)."\x00\xFF\xD9";
    }

    public function test_une_couverture_binaire_revient_intacte_et_relisible(): void
    {
        $octets = $this->octetsBinaires();

        $profile = Profile::factory()->create();
        $profile->forceFill(['cover_data' => $octets])->save();

        $relu = Profile::query()->findOrFail($profile->id);

        // Deux lectures : sous PostgreSQL, la colonne arrive en flux, et un
        // flux lu jusqu'au bout rendrait une chaîne vide la seconde fois.
        $this->assertSame($octets, $relu->cover_data);
        $this->assertSame($octets, $relu->cover_data);
    }

    public function test_un_avatar_binaire_revient_intact(): void
    {
        $octets = $this->octetsBinaires();

        $user = User::factory()->create();
        $user->forceFill(['avatar_data' => $octets])->save();

        $this->assertSame($octets, User::query()->findOrFail($user->id)->avatar_data);
    }

    // =======================================================================
    // CONFIGURATION — l'adresse de Neon n'a pas de port
    // =======================================================================

    /**
     * Relit config/database.php avec un environnement donné, puis résout la
     * connexion PostgreSQL comme Laravel le fait à l'ouverture.
     *
     * @param  array<string, string>  $variables
     * @return array<string, mixed>
     */
    private function pgsqlResolu(array $variables): array
    {
        $avant = [];

        foreach ($variables as $cle => $valeur) {
            $avant[$cle] = [$_ENV[$cle] ?? null, $_SERVER[$cle] ?? null, getenv($cle)];
            $_ENV[$cle] = $_SERVER[$cle] = $valeur;
            putenv("{$cle}={$valeur}");
        }

        try {
            $config = (require config_path('database.php'))['connections']['pgsql'];

            return (new \Illuminate\Support\ConfigurationUrlParser)->parseConfiguration($config);
        } finally {
            foreach ($avant as $cle => [$env, $server, $getenv]) {
                if ($env === null) {
                    unset($_ENV[$cle]);
                } else {
                    $_ENV[$cle] = $env;
                }
                if ($server === null) {
                    unset($_SERVER[$cle]);
                } else {
                    $_SERVER[$cle] = $server;
                }
                putenv($getenv === false ? $cle : "{$cle}={$getenv}");
            }
        }
    }

    /**
     * Sur Render, DB_PORT vaut encore le port d'Aiven au moment de la
     * bascule. L'adresse de Neon, sans port, ne doit pas en hériter.
     */
    public function test_une_adresse_neon_sans_port_ne_prend_pas_le_port_de_l_ancienne_base(): void
    {
        $config = $this->pgsqlResolu([
            'DB_URL' => 'postgresql://neondb_owner:secret@ep-test.eu-central-1.aws.neon.tech/neondb?sslmode=require',
            'DB_PORT' => '21567',
            'DB_HOST' => 'mysql-ancien.aivencloud.com',
        ]);

        $this->assertSame('ep-test.eu-central-1.aws.neon.tech', $config['host']);
        $this->assertSame('5432', (string) $config['port']);
        $this->assertSame('neondb', $config['database']);
        $this->assertSame('require', $config['sslmode']);
    }

    public function test_une_adresse_qui_precise_son_port_le_garde(): void
    {
        $config = $this->pgsqlResolu([
            'DB_URL' => 'postgresql://u:p@db.exemple.sn:6543/qrid',
            'DB_PORT' => '21567',
        ]);

        $this->assertSame('6543', (string) $config['port']);
    }

    // =======================================================================
    // ADRESSES MAL FORMÉES — 404, jamais 500
    // =======================================================================

    public function test_un_identifiant_non_numerique_repond_404(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/clients/abc')
            ->assertNotFound();
    }

    public function test_une_notification_non_numerique_repond_404(): void
    {
        $this->actingAs(User::factory()->create(['email_verified_at' => now()]))
            ->get('/notifications/abc')
            ->assertNotFound();
    }
}
