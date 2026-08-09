<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\ConfirmRegistrationController;
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

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
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
