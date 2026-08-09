<?php

namespace Tests\Feature;

use Database\Seeders\PlanSeeder;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * HYGIÈNE DES GABARITS — aucun texte technique ne doit atteindre l'écran.
 *
 * Un commentaire Blade imbriqué s'est réellement affiché en clair sur le
 * tableau de bord d'un client : quinze lignes de spécification, marqueur de
 * fermeture compris, au milieu de la page.
 *
 * La cause est un piège du langage, pas une étourderie isolée : Blade
 * N'IMBRIQUE PAS les commentaires. Le premier marqueur de fermeture rencontré
 * ferme le bloc entier, et tout ce qui suit devient du HTML. Rien dans Laravel
 * ne le signale — ni erreur, ni avertissement, ni test en échec. Seul un
 * contrôle comme celui-ci l'attrape.
 */
class BladeHygieneTest extends TestCase
{
    use RefreshDatabase;

    private const OUVERTURE = '{{--';

    private const FERMETURE = '--}}';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TemplateSeeder::class);
        $this->seed(PlanSeeder::class);
    }

    /** @return list<string> */
    private function gabarits(): array
    {
        $chemins = [];

        $fichiers = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(resource_path('views'))
        );

        foreach ($fichiers as $fichier) {
            if ($fichier->isFile() && str_ends_with($fichier->getFilename(), '.blade.php')) {
                $chemins[] = $fichier->getPathname();
            }
        }

        sort($chemins);

        return $chemins;
    }

    public function test_no_blade_comment_is_nested_or_left_open(): void
    {
        $fautifs = [];

        foreach ($this->gabarits() as $chemin) {
            $contenu = (string) file_get_contents($chemin);
            $position = 0;

            while (($debut = strpos($contenu, self::OUVERTURE, $position)) !== false) {
                $fin = strpos($contenu, self::FERMETURE, $debut + 4);

                $relatif = str_replace(resource_path('views').DIRECTORY_SEPARATOR, '', $chemin);

                if ($fin === false) {
                    $fautifs[] = "{$relatif} — commentaire jamais fermé";
                    break;
                }

                // Un second marqueur d'ouverture AVANT la fermeture : le bloc
                // se referme trop tôt et la suite part dans la page.
                if (str_contains(substr($contenu, $debut + 4, $fin - $debut - 4), self::OUVERTURE)) {
                    $fautifs[] = "{$relatif} — commentaire imbriqué";
                }

                $position = $fin + 4;
            }
        }

        $this->assertSame([], $fautifs, "Commentaires Blade fautifs :\n".implode("\n", $fautifs));
    }

    /**
     * Aucune page rendue ne laisse échapper de marqueur de commentaire.
     *
     * Le contrôle statique ci-dessus attrape la cause ; celui-ci attrape le
     * symptôme, quelle que soit son origine — y compris une chaîne mal
     * échappée ou un partiel inclus de travers.
     */
    public function test_no_rendered_page_leaks_a_comment_marker(): void
    {
        $pages = [
            'login' => route('login'),
            'register' => route('register'),
            'mot de passe oublié' => route('password.request'),
            'lien expiré' => route('registration.expired'),
            'accueil' => route('home'),
            'exemple' => route('profile.demo'),
            'conditions' => route('legal.conditions'),
        ];

        $fuites = [];

        foreach ($pages as $nom => $url) {
            /*
             | assertOk() AVANT tout : une page en erreur affiche, en mode
             | débogage, un extrait du code source — donc des marqueurs de
             | commentaire. Sans ce contrôle, le test échouait sur des pages
             | parfaitement saines dont la seule faute était de tomber faute
             | de données.
             */
            $html = $this->get($url)->assertOk()->getContent();

            if (str_contains($html, self::FERMETURE) || str_contains($html, self::OUVERTURE)) {
                $fuites[] = $nom;
            }
        }

        $this->assertSame([], $fuites, 'Marqueur de commentaire visible sur : '.implode(', ', $fuites));
    }
}
