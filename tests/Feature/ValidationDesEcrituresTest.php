<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;
use ReflectionMethod;
use Tests\TestCase;
use Throwable;

/**
 * TOUTE ROUTE QUI ÉCRIT VALIDE CE QU'ELLE REÇOIT.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * POURQUOI CE TEST NE LISTE AUCUNE ROUTE À LA MAIN
 * ═══════════════════════════════════════════════════════════════════════════
 * Trente-six routes écrivent en base. Les relire une par une est un travail
 * qu'il faut refaire à chaque ajout — donc un travail qu'on cesse de faire.
 *
 * Ce fichier parcourt la table de routage RÉELLE et exige, pour chaque route
 * en POST, PUT, PATCH ou DELETE, l'une de ces trois choses :
 *
 *   · un FormRequest en paramètre ;
 *   · un appel à validate() ou validateWithBag() dans le corps ;
 *   · une inscription EXPLICITE sur la liste ci-dessous, avec sa raison.
 *
 * La troisième branche est le cœur du dispositif. Une exception qu'on doit
 * écrire et justifier est une décision ; une exception qu'on obtient en ne
 * faisant rien est un oubli. Une route ajoutée demain sans validation tombe
 * ici, et son auteur doit soit valider, soit dire pourquoi il n'a pas à le
 * faire.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * CE QUE CE TEST NE PRÉTEND PAS COUVRIR
 * ═══════════════════════════════════════════════════════════════════════════
 * Il vérifie qu'une validation EXISTE, pas qu'elle est bonne. Les règles
 * elles-mêmes sont éprouvées par les tests de chaque formulaire. Ce
 * garde-fou attrape l'oubli complet, qui est la panne la plus fréquente et
 * la plus coûteuse.
 */
class ValidationDesEcrituresTest extends TestCase
{
    /**
     * LES ROUTES QUI N'ONT RIEN À VALIDER, ET POURQUOI.
     *
     * Chaque entrée est une décision, vérifiée au moment de l'écrire.
     *
     * @return array<string, string>
     */
    private function exemptions(): array
    {
        return [
            // Aucun corps : le modèle vient de l'URL, et tout le groupe est
            // derrière ['auth','verified','admin'].
            'admin.templates.toggle' => 'bascule sans corps, modèle lié par la route',
            'admin.templates.default' => 'bascule sans corps, modèle lié par la route',
            'admin.templates.duplicate' => 'duplication sans corps, modèle lié par la route',

            // Aucun corps : l'action porte sur l'utilisateur connecté.
            'notifications.read-all' => 'aucun corps, agit sur les alertes du compte connecté',
            'logout' => 'aucun corps',

            // Aucun corps : l'état vient de la base, jamais de la requête.
            'abonnement.checkout' => 'aucun corps, lit le profil du compte connecté',

            // L'adresse vient de la SESSION et non de la requête — ce qui
            // interdit au passage d'énumérer les comptes existants.
            'registration.resend' => 'aucun corps, adresse lue en session',

            // Jeton en en-tête, comparé en temps constant, 404 si absent.
            'automation.schedule' => 'jeton d\'automatisation vérifié dans le contrôleur',

            // Paquet tiers : Resend signe ses appels et vérifie la signature.
            'resend.webhook' => 'signature vérifiée par le paquet Resend',

            // Route du framework, scellée par une signature dérivée de
            // APP_KEY : ReceiveFile exige hasValidRelativeSignature() avant
            // d'écrire quoi que ce soit. Sa jumelle en lecture n'est pas
            // listée ici — elle ne figure pas parmi les routes d'écriture.
            'storage.local.upload' => 'route du framework, URL signée',
        ];
    }

    /**
     * @return list<array{string, string}> [nom de route, action]
     */
    private function routesQuiEcrivent(): array
    {
        $trouvees = [];

        foreach (Route::getRoutes() as $route) {
            if (! array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                continue;
            }

            $trouvees[] = [$route->getName() ?? $route->uri(), $route->getActionName()];
        }

        return $trouvees;
    }

    /** La méthode valide-t-elle ce qu'elle reçoit ? */
    private function valide(string $action): bool
    {
        // Une route en closure ne peut pas être inspectée : elle doit passer
        // par la liste d'exemptions, où sa raison sera écrite.
        if ($action === 'Closure') {
            return false;
        }

        [$classe, $methode] = str_contains($action, '@')
            ? explode('@', $action)
            : [$action, '__invoke'];

        if (! class_exists($classe) || ! method_exists($classe, $methode)) {
            return false;
        }

        try {
            $reflexion = new ReflectionMethod($classe, $methode);
        } catch (Throwable) {
            return false;
        }

        foreach ($reflexion->getParameters() as $parametre) {
            $type = $parametre->getType();

            if ($type instanceof \ReflectionNamedType
                && ! $type->isBuiltin()
                && is_subclass_of($type->getName(), FormRequest::class)) {
                return true;
            }
        }

        $fichier = $reflexion->getFileName();

        if ($fichier === false) {
            return false;
        }

        $corps = implode('', array_slice(
            file($fichier),
            $reflexion->getStartLine() - 1,
            $reflexion->getEndLine() - $reflexion->getStartLine() + 1,
        ));

        // `validateWithBag` compte autant que `validate` : c'est la même
        // validation, avec un sac d'erreurs nommé pour un écran qui porte
        // plusieurs formulaires. L'oublier ici produirait de faux positifs,
        // et de faux positifs finissent par faire ignorer le test.
        return str_contains($corps, '->validate(')
            || str_contains($corps, '->validateWithBag(')
            || str_contains($corps, 'Validator::');
    }

    public function test_every_writing_route_validates_or_says_why_it_need_not(): void
    {
        $exemptions = $this->exemptions();
        $manquantes = [];

        foreach ($this->routesQuiEcrivent() as [$nom, $action]) {
            if (isset($exemptions[$nom]) || $this->valide($action)) {
                continue;
            }

            $manquantes[] = $nom.'  ('.class_basename($action).')';
        }

        $this->assertSame([], $manquantes,
            'Ces routes écrivent sans valider leur entrée. Ajoutez un FormRequest, '.
            "un appel à validate(), ou une exemption justifiée dans exemptions() :\n  - ".
            implode("\n  - ", $manquantes)
        );
    }

    /**
     * LA LISTE D'EXEMPTIONS NE DOIT PAS POURRIR.
     *
     * Une route supprimée laisse son exemption derrière elle. Trois ans plus
     * tard, la liste décrit une application qui n'existe plus, et personne
     * n'ose y toucher faute de savoir ce qui est encore vrai.
     */
    public function test_no_exemption_outlives_its_route(): void
    {
        $existantes = array_column($this->routesQuiEcrivent(), 0);
        $fantomes = array_diff(array_keys($this->exemptions()), $existantes);

        $this->assertSame([], array_values($fantomes),
            'Ces exemptions ne correspondent à aucune route : supprimez-les.');
    }

    /**
     * ET UNE EXEMPTION NE DOIT PAS COUVRIR UNE ROUTE QUI VALIDE DÉJÀ.
     *
     * Sinon la liste grossit d'entrées inutiles, et l'on finit par exempter
     * par réflexe une route qu'il aurait fallu corriger.
     */
    public function test_no_exemption_covers_a_route_that_already_validates(): void
    {
        $superflues = [];

        foreach ($this->routesQuiEcrivent() as [$nom, $action]) {
            if (isset($this->exemptions()[$nom]) && $this->valide($action)) {
                $superflues[] = $nom;
            }
        }

        $this->assertSame([], $superflues,
            'Ces routes valident déjà : leur exemption ne sert à rien.');
    }
}
