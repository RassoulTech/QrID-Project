<?php

namespace App\Exceptions;

use App\Services\ProfileWizardService;
use Illuminate\Http\Request;

/**
 * Où renvoyer l'utilisateur dont le jeton CSRF a expiré.
 *
 * Un 419 n'est pas une faute : c'est une page restée ouverte. Le renvoyer sur
 * un écran d'erreur lui fait perdre son travail, ce qui est inacceptable en
 * plein parcours payant. On le ramène donc sur le formulaire d'où il vient.
 */
class ExpiredPageRedirector
{
    /** @return string|null L'URL de retour, ou null pour afficher la page 419. */
    public static function for(Request $request): ?string
    {
        // --- Parcours de création : l'étape réellement en cours -------------
        // La page précédente du navigateur n'est pas fiable ici (le parcours
        // est en POST-Redirect-Get) ; la session, elle, sait où on en est.
        if ($request->routeIs('profile.store.*', 'profile.create.*')) {
            return route('profile.create.step'.app(ProfileWizardService::class)->nextStep());
        }

        // --- Authentification ------------------------------------------------
        // Les envois de formulaire portent les noms « *.store » : ce sont EUX
        // qu'on voit ici, jamais la route GET du formulaire.
        $auth = match (true) {
            $request->routeIs('login', 'login.store') => route('login'),
            $request->routeIs('register', 'register.store') => route('register'),
            $request->routeIs('password.*') => route('password.request'),
            $request->routeIs('registration.*') => route('register'),
            // Une déconnexion au jeton périmé ne doit pas bloquer :
            // l'utilisateur voulait justement partir.
            $request->routeIs('logout') => route('login'),
            default => null,
        };

        if ($auth !== null) {
            return $auth;
        }

        // --- Tout le reste : la page d'où venait le formulaire ----------------
        $precedente = url()->previous();

        if ($precedente === '') {
            return null; // sans origine fiable, la page 419 reste le recours
        }

        // On ne compare PAS $precedente à l'URL courante. Ce contrôle-ci
        // existait pour éviter une « boucle », mais il n'y en a jamais :
        // ce code ne s'exécute que sur une écriture (POST/PATCH/DELETE — la
        // vérification CSRF ignore les lectures) et la redirection produite est
        // un GET. Sur les routes RESTful où le formulaire et l'envoi partagent
        // la même URL (GET /compte et PATCH /compte), il renvoyait null : la
        // page 419 brute s'affichait et les saisies étaient perdues, exactement
        // ce que cette classe est censée empêcher.

        // Redirection ouverte : on ne sort jamais du site. On compare l'hôte
        // réel de la requête, pas APP_URL — sinon un accès par « localhost »
        // alors qu'APP_URL vaut « 127.0.0.1 » retomberait sur la page d'erreur.
        if (! str_starts_with($precedente, $request->getSchemeAndHttpHost())) {
            return null;
        }

        return $precedente;
    }
}
