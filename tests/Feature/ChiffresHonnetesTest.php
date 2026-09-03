<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Profile;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * AUCUN CHIFFRE AFFICHÉ N'EST INVENTÉ.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * CE QUE COÛTE UN NOMBRE FAUX SUR UNE PAGE D'ACCUEIL
 * ═══════════════════════════════════════════════════════════════════════════
 * La page annonçait « Plus de 500 professionnels ». Rien ne mesurait ce
 * nombre : il était écrit dans un fichier de langue.
 *
 * Un visiteur qui découvre ensuite que le produit compte trois clients ne se
 * dit pas « ils ont arrondi ». Il se dit qu'on lui a menti — et il cesse de
 * croire le reste de la page, tarifs et promesses compris. Une preuve
 * sociale fausse ne coûte pas la preuve : elle coûte la crédibilité de tout
 * ce qui l'entoure.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * LE SEUIL N'EST PAS UNE FAÇON DE CACHER LE CHIFFRE
 * ═══════════════════════════════════════════════════════════════════════════
 * « +7 » dessert autant qu'un mensonge, pour la raison inverse : il dit que
 * personne ne s'en sert. En dessous du seuil, la page affirme donc la même
 * chose SANS nombre — ce qui n'engage rien de faux. Au-dessus, elle dit le
 * chiffre réel, qui est une bien meilleure preuve qu'un arrondi.
 */
class ChiffresHonnetesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Le compte est mis en cache une heure en production : sans purge,
        // le second scénario lirait le résultat du premier.
        Cache::forget('landing.cartes_en_ligne');
    }

    /** Des cartes réellement en ligne : publiées ET portées par un abonnement. */
    private function cartesEnLigne(int $combien): void
    {
        $plan = Plan::factory()->create(['slug' => 'standard', 'duration_days' => 30]);

        for ($i = 0; $i < $combien; $i++) {
            $client = User::factory()->create();

            Subscription::factory()->create([
                'user_id' => $client->id,
                'plan_id' => $plan->id,
                'status' => Subscription::STATUS_ACTIVE,
                'ends_at' => now()->addDays(20),
            ]);

            Profile::factory()->create(['user_id' => $client->id, 'is_active' => true]);
        }
    }

    /**
     * LE CONTENU DE LA PASTILLE, ou null si elle est absente.
     *
     * `assertSee('+4')` serait fragile : un « +221 » d'indicatif, un chemin
     * SVG ou une classe utilitaire contiennent la même suite de caractères.
     * On vise donc la pastille elle-même, qui est le seul endroit où ce
     * nombre est une affirmation.
     */
    private function pastille(string $html): ?string
    {
        return preg_match('/avatars__more[^>]*>([^<]*)</', $html, $trouve)
            ? trim($trouve[1])
            : null;
    }

    /**
     * AU LANCEMENT, AUCUN NOMBRE.
     *
     * C'est l'état réel du produit aujourd'hui, et celui de n'importe quel
     * produit à ses débuts.
     */
    public function test_below_the_threshold_no_figure_is_shown(): void
    {
        config(['landing.seuil_vitrine' => 50]);

        $this->cartesEnLigne(3);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('+500', $html,
            'La page annonce encore un nombre que rien ne mesure.');
        $this->assertNull($this->pastille($html),
            'La pastille affiche un nombre alors que le produit est sous le seuil.');
    }

    /**
     * AU-DELÀ DU SEUIL, LE CHIFFRE RÉEL.
     *
     * Il devient vrai tout seul le jour où le produit le mérite : personne
     * n'a à penser à changer une chaîne de caractères.
     */
    public function test_above_the_threshold_the_real_figure_appears(): void
    {
        config(['landing.seuil_vitrine' => 3]);

        $this->cartesEnLigne(4);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame('+4', $this->pastille($html),
            'Au-delà du seuil, la pastille doit porter le compte réel.');
    }

    /**
     * UNE CARTE DONT L'ABONNEMENT A EXPIRÉ NE COMPTE PAS.
     *
     * Elle n'est plus consultable : la compter gonflerait le chiffre avec
     * des pages qui répondent « carte indisponible ».
     */
    public function test_an_expired_card_is_not_counted(): void
    {
        config(['landing.seuil_vitrine' => 1]);

        $plan = Plan::factory()->create(['slug' => 'standard', 'duration_days' => 30]);
        $client = User::factory()->create();

        Subscription::factory()->create([
            'user_id' => $client->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'ends_at' => now()->subDay(),   // hier
        ]);

        Profile::factory()->create(['user_id' => $client->id, 'is_active' => true]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertNull($this->pastille($html),
            'Une carte dont l\'abonnement a expire a ete comptee : le chiffre '.
            'inclurait des pages qui répondent « carte indisponible ».');
    }

    /**
     * ET AUCUN CHIFFRE EN DUR NE SUBSISTE DANS LES FICHIERS DE LANGUE.
     *
     * C'est le garde-fou qui survit : un « rejoignez nos 1 200 clients »
     * ajouté demain dans une traduction tomberait ici.
     */
    public function test_no_hardcoded_headcount_remains_in_the_translations(): void
    {
        $suspects = [];

        foreach (glob(lang_path('*/landing.php')) ?: [] as $fichier) {
            $contenu = (string) file_get_contents($fichier);

            if (preg_match_all('/\b\d{3,}\s*(professionnels|professionals|clients|utilisateurs|users|cartes|cards)/i', $contenu, $trouves)) {
                foreach ($trouves[0] as $trouve) {
                    $suspects[] = basename(dirname($fichier)).'/landing.php : « '.$trouve.' »';
                }
            }
        }

        $this->assertSame([], $suspects,
            "Ces chaînes annoncent un nombre d'utilisateurs que rien ne mesure. ".
            "Comptez-le, ou dites la même chose sans nombre :\n  - ".implode("\n  - ", $suspects));
    }
}
