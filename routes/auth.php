<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\ConfirmRegistrationController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    // Nommée : ExpiredPageRedirector reconnaît le formulaire d'où vient un
    // envoi au jeton expiré par le NOM de sa route. Sans nom, un jeton périmé
    // à l'inscription ne ramenait pas sur le formulaire.
    Route::post('register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('register.store');

    // Page de confirmation (e-mail masqué, marche à suivre, renvoi, support).
    Route::get('inscription/confirmation', [ConfirmRegistrationController::class, 'pending'])
        ->name('registration.pending');

    Route::post('inscription/renvoyer', [ConfirmRegistrationController::class, 'resend'])
        ->middleware('throttle:10,1')
        ->name('registration.resend');

    // Abandonne la demande en cours (vide la session) et repart du formulaire.
    Route::get('inscription/recommencer', [ConfirmRegistrationController::class, 'abandon'])
        ->name('registration.abandon');

    /*
     | Lien de confirmation expiré — page à part entière, avec son URL.
     |
     | Elle était rendue directement depuis confirm(), sans route : impossible à
     | recharger, à mettre en favori ou à atteindre autrement qu'en cliquant un
     | lien déjà mort. L'adresse à relancer transite par la session, jamais par
     | l'URL (elle n'a rien à faire dans un historique de navigation).
     */
    Route::get('inscription/lien-expire', [ConfirmRegistrationController::class, 'expired'])
        ->name('registration.expired');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    // Nommée pour la même raison que register.store ci-dessus.
    Route::post('login', [AuthenticatedSessionController::class, 'store'])
        ->name('login.store');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    /*
     |--------------------------------------------------------------------------
     | DEMANDE DE RÉINITIALISATION — LIMITÉE, et ce n'était pas le cas
     |--------------------------------------------------------------------------
     | Cette route ENVOIE UN E-MAIL à une adresse fournie par l'appelant, sans
     | authentification. Sans limite, elle devient trois choses à la fois :
     |
     |   · un outil pour inonder la boîte de n'importe qui, à raison d'un
     |     message par requête ;
     |   · un moyen d'épuiser le quota quotidien du fournisseur — après quoi
     |     PLUS AUCUN e-mail du produit ne part, confirmations d'inscription
     |     comprises ;
     |   · une charge inutile sur une opération synchrone, puisque l'envoi se
     |     fait dans la requête faute de worker.
     |
     | Le framework applique déjà un délai de soixante secondes PAR ADRESSE.
     | Il ne protège de rien ici : il suffit de changer d'adresse à chaque
     | appel. La limite ci-dessous porte sur l'IP, et c'est elle qui manquait.
     |
     | Cinq par heure : très large pour quelqu'un qui a réellement oublié son
     | mot de passe, étroit pour un envoi automatisé.
     */
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:5,60')
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    /*
     | Le jeton fait 64 caractères : le deviner est hors de portée. La limite
     | ne protège donc pas d'une attaque par force brute — elle empêche qu'un
     | script mal réglé n'écrive en boucle sur un compte, et borne le coût de
     | l'appel de hachage qui suit chaque tentative.
     */
    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:10,60')
        ->name('password.store');

    /*
     |--------------------------------------------------------------------------
     | CONNEXION GOOGLE
     |--------------------------------------------------------------------------
     | Deux routes, toutes deux en GET : c'est le protocole OAuth qui l'impose,
     | le retour étant une navigation du navigateur et non une soumission.
     |
     | ELLES SONT SOUS « guest », comme les autres. Quelqu'un déjà connecté qui
     | y reviendrait — signet, bouton précédent — serait renvoyé vers son
     | espace plutôt que de rouvrir une session par-dessus la sienne.
     |
     | LE DÉPART EST LIMITÉ EN CADENCE. Chaque appel ouvre une session OAuth et
     | écrit un jeton d'état ; sans limite, une boucle de rechargement remplirait
     | le stockage de sessions. Le RETOUR, lui, ne l'est pas : il est déclenché
     | par Google, pas par l'utilisateur, et le brider reviendrait à casser des
     | connexions légitimes lors d'une rafale d'inscriptions.
     */
    Route::get('auth/google', [GoogleController::class, 'redirect'])
        ->middleware('throttle:20,1')
        ->name('auth.google');

    Route::get('auth/google/retour', [GoogleController::class, 'callback'])
        ->name('auth.google.callback');
});

// Confirmation du compte : accessible connecté ou non (la logique est gérée
// dans le contrôleur). C'est le SEUL endroit qui crée réellement un utilisateur.
Route::get('inscription/confirmer/{token}', [ConfirmRegistrationController::class, 'confirm'])
    ->middleware('throttle:20,1')
    ->name('registration.confirm');

Route::middleware('auth')->group(function () {
    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    // Nommée : la vue ciblait route('password.confirm'), c'est-à-dire la route
    // GET. Cela ne fonctionnait que parce que les deux partagent l'URI.
    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store'])
        ->name('password.confirm.store');

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
