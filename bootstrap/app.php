<?php

use App\Exceptions\ExpiredPageRedirector;
use App\Http\Middleware\EnsureAccountIsUsable;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsNotBlocked;
use App\Http\Middleware\EnsureWizardStepIsAvailable;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Support\Theme;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        /*
         |---------------------------------------------------------------------
         | DERRIÈRE UN PROXY — Render, et tout hébergeur qui termine le TLS
         |---------------------------------------------------------------------
         | Render déchiffre le HTTPS sur son proxy et transmet la requête EN
         | CLAIR au conteneur, en signalant l'origine par l'en-tête
         | X-Forwarded-Proto: https.
         |
         | Sans cette ligne, Laravel ne lit pas cet en-tête : il se croit en
         | HTTP et fabrique TOUTES ses adresses en http://. La page, elle, est
         | servie en https. Le navigateur bloque alors le contenu mixte — sans
         | le moindre message visible.
         |
         | C'est ce qui a produit, au premier déploiement réussi, une page
         | complète et entièrement dépouillée de son style : le fichier CSS
         | existait bien (200, 316 Ko), seule son adresse était en http.
         |
         | ET LE CSS N'ÉTAIT QUE LA PARTIE VISIBLE. La même erreur touchait :
         |
         |   · les QR Codes, qui auraient encodé une adresse en http —
         |     imprimée sur des cartes PVC, donc irrattrapable ;
         |   · les liens des e-mails de confirmation d'inscription ;
         |   · le cookie de session marqué « secure », qu'un navigateur refuse
         |     de poser sur ce qu'il croit être une connexion non chiffrée.
         |
         | POURQUOI « * » ET NON UNE LISTE D'ADRESSES : Render ne publie pas
         | les adresses de ses proxys, et elles changent. Sur une plateforme de
         | ce type, seul le proxy peut atteindre le conteneur — l'application
         | n'est pas joignable directement depuis Internet. Restreindre à une
         | liste qu'on ne peut pas tenir à jour reviendrait à casser le site au
         | premier changement d'infrastructure.
         */
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'wizard.step' => EnsureWizardStepIsAvailable::class,
            'admin' => EnsureUserIsAdmin::class,

            // Remplace le « verified » du framework, qui chercherait une route
            // verification.notice inexistante dans cette architecture.
            'verified' => EnsureAccountIsUsable::class,

            // Notre version envoie l'administrateur vers son accueil, pas vers
            // le tableau de bord client.
            //
            // C'est bien un alias qu'il faut redéfinir, PAS un replace() :
            // withMiddleware()->replace() n'agit que sur la pile GLOBALE, alors
            // que routes/auth.php référence ce middleware par son alias
            // « guest ». Un replace() ici serait silencieusement sans effet.
            'guest' => RedirectIfAuthenticated::class,
        ]);

        /*
         | Le thème n'est pas chiffré. Ce cookie ne contient rien de secret —
         | la chaîne « light » ou « dark » — et le chiffrer coûterait deux
         | inconvénients pour aucun gain : le cookie deviendrait illisible en
         | débogage, et surtout un cookie déposé par un ancien déploiement
         | serait rejeté après rotation de APP_KEY, renvoyant silencieusement
         | des visiteurs en thème clair.
         */
        $middleware->encryptCookies(except: [Theme::nomDuCookie()]);

        // Un compte suspendu est éjecté dès la requête suivante, sans attendre
        // l'expiration de sa session. Placé sur le groupe web : il doit
        // s'appliquer à TOUTE page, y compris celles ajoutées plus tard.
        $middleware->appendToGroup('web', EnsureUserIsNotBlocked::class);

        // Un invité intercepté sur une page protégée arrive sur la connexion,
        // et y revient après s'être identifié (intended()).
        $middleware->redirectGuestsTo(fn () => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        /*
         |----------------------------------------------------------------------
         | Jeton CSRF expiré (419) — jamais d'impasse, jamais de saisie perdue
         |----------------------------------------------------------------------
         | On ramène TOUJOURS l'utilisateur sur le formulaire d'où il vient,
         | saisies repositionnées. La page 419 ne sert plus que de dernier
         | recours, quand aucune origine n'est identifiable.
         |
         | La requête n'est jamais exécutée : rediriger n'affaiblit en rien la
         | protection CSRF, cela demande simplement de valider à nouveau.
         |
         | ATTENTION AU TYPE ÉCOUTÉ. Ce callback ne peut PAS être déclaré sur
         | TokenMismatchException : le handler du framework passe l'exception
         | par prepareException() — qui la remplace par un HttpException(419) —
         | AVANT de consulter les callbacks. Un callback typé sur
         | TokenMismatchException n'est donc jamais appelé, et tout ce
         | traitement reste lettre morte (la page 419 brute s'affiche, saisies
         | perdues). On écoute donc le 419 lui-même.
         */
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }

            if ($request->expectsJson()) {
                return response()->json(
                    ['message' => 'Votre session a expiré. Rechargez la page.'],
                    419
                );
            }

            $retour = ExpiredPageRedirector::for($request);

            if ($retour === null) {
                return null; // page 419 stylée
            }

            return redirect()->to($retour)
                // Le mot de passe n'est JAMAIS repositionné, même en session.
                ->withInput($request->except([
                    'password', 'password_confirmation', 'current_password', '_token',
                ]))
                ->with('warning', 'Votre page est restée ouverte trop longtemps. '
                    .'Vos informations sont conservées : validez à nouveau pour continuer.');
        });

        /*
         |----------------------------------------------------------------------
         | Envoi trop volumineux — le « 419 déguisé »
         |----------------------------------------------------------------------
         | Au-delà de post_max_size, PHP vide $_POST EN ENTIER, jeton CSRF
         | compris. Sans ce traitement, une photo trop lourde ressort en
         | « Page expirée » : un message faux, sur lequel personne ne peut agir.
         */
        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Fichier trop volumineux.'], 413);
            }

            return redirect()->to(url()->previous() ?: url('/'))
                ->withInput($request->except(['photo', 'password', '_token']))
                ->withErrors(['photo' => 'Votre photo est trop lourde pour être envoyée. '
                    .'Choisissez une image de moins de 2 Mo.']);
        });
    })->create();
