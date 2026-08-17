<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Automation\ScheduleRunController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DesignSystemController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Profile\CardController;
use App\Http\Controllers\Profile\PaymentController;
use App\Http\Controllers\Profile\ProfileActivationController;
use App\Http\Controllers\Profile\ProfilePageController;
use App\Http\Controllers\Profile\ProfilePreviewController;
use App\Http\Controllers\Profile\ProfileWizardController;
use App\Http\Controllers\Profile\QrCodePageController;
use App\Http\Controllers\Profile\StatisticsController;
use App\Http\Controllers\PublicProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ThemePreferenceController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('home');

/*
 | FORMULAIRE DE CONTACT — public, donc limité en cadence.
 |
 | Cinq envois par heure et par adresse IP. Le formulaire est ouvert à
 | n'importe qui, sans compte : sans limite, il devient un relais d'envoi vers
 | notre propre boîte, et la réputation de l'expéditeur en pâtit — celle-là
 | même dont dépendent les liens de réinitialisation de mot de passe.
 |
 | Cinq est large pour un humain, étroit pour un robot. Le piège à robots du
 | ContactRequest arrête l'essentiel en amont ; ceci est la seconde barrière.
 */
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:5,60')
    ->name('contact.store');

/*
|--------------------------------------------------------------------------
| DÉCLENCHEUR DES TÂCHES PLANIFIÉES — appelé par Make, chaque minute
|--------------------------------------------------------------------------
| Cinq tâches sont programmées dans routes/console.php et aucune ne
| s'exécute : un service web ne regarde jamais l'heure. Cette route donne à
| un appelant extérieur le moyen de lancer `schedule:run`.
|
| ELLE EST HORS DU GROUPE « web », et c'est délibéré : un appel de machine à
| machine n'a ni session, ni cookie, ni jeton CSRF à présenter. La placer
| dans « web » l'aurait fait rejeter en 419 à chaque appel.
|
| LA LIMITE DE CADENCE EST BASSE. L'usage légitime est d'UN appel par minute ;
| dix laissent la place à un rattrapage ou à un test, et arrêtent net qui
| voudrait marteler l'adresse pour multiplier les envois.
|
| Le jeton, lui, est vérifié dans le contrôleur — et son absence rend 404.
*/
Route::match(['get', 'post'], '/automation/schedule', ScheduleRunController::class)
    ->middleware(['throttle:10,1'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('automation.schedule');

// Pages légales — obligatoires avant toute vente.
Route::get('/conditions-generales', [LegalController::class, 'conditions'])->name('legal.conditions');
Route::get('/confidentialite', [LegalController::class, 'confidentialite'])->name('legal.confidentialite');
Route::get('/mentions-legales', [LegalController::class, 'mentions'])->name('legal.mentions');

// Profil public de démonstration, ciblé par « Voir un exemple ».
Route::get('/exemple', [PublicProfileController::class, 'demo'])->name('profile.demo');

// Référence visuelle des composants. Strictement locale (404 ailleurs).
Route::get('/design-system', DesignSystemController::class)->name('design.system');

/*
|--------------------------------------------------------------------------
| BASCULE DE THÈME — hors du groupe « auth », volontairement
|--------------------------------------------------------------------------
| Un visiteur de la landing ou du formulaire de connexion doit pouvoir
| basculer en sombre. Réserver ce réglage aux comptes reviendrait à imposer
| le thème clair précisément aux écrans qu'on découvre en premier.
|
| Pour un compte, la préférence est écrite en base ; pour un invité, dans un
| cookie d'un an. Voir App\Support\Theme.
*/
Route::post('/preferences/theme', [ThemePreferenceController::class, 'update'])
    ->name('preferences.theme');

/*
|--------------------------------------------------------------------------
| ADMINISTRATION
|--------------------------------------------------------------------------
| Fichier séparé. Toutes les routes admin y sont déclarées dans un groupe
| unique préfixé /admin et protégé par le middleware `admin` : on ne peut pas
| en ajouter une en oubliant la protection.
*/
require __DIR__.'/admin.php';

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    /*
     |-------------------------------------------------------------------------
     | BARRE SUPÉRIEURE — recherche, notifications, thème
     |-------------------------------------------------------------------------
     | La recherche est un GET : partageable, rechargeable, sans JavaScript.
     | La bascule de thème est un POST : elle écrit une préférence en base.
     */
    Route::get('/recherche', SearchController::class)->name('recherche');

    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');
    Route::get('/notifications/{notification}', [NotificationController::class, 'open'])
        ->name('notifications.open');
    Route::post('/notifications/tout-lu', [NotificationController::class, 'readAll'])
        ->name('notifications.read-all');

    /*
     |-------------------------------------------------------------------------
     | Création du profil — trois étapes, persistance en session
     |-------------------------------------------------------------------------
     | Multi-pages. La navigation entre étapes est TOUJOURS serveur : chaque
     | « Continuer » est un POST suivi d'une redirection (POST-Redirect-Get).
     | Le middleware wizard.step interdit de sauter une étape.
     */
    // Modifier un profil existant : recharge la session puis renvoie en étape 1.
    Route::get('/profil/modifier', [ProfileWizardController::class, 'edit'])
        ->name('profile.edit');

    Route::prefix('profil/creation')->group(function () {
        Route::get('/etape-1', [ProfileWizardController::class, 'stepOne'])
            ->name('profile.create.step1');
        Route::post('/etape-1', [ProfileWizardController::class, 'storeStepOne'])
            ->name('profile.store.step1');

        Route::get('/etape-2', [ProfileWizardController::class, 'stepTwo'])
            ->middleware('wizard.step:2')->name('profile.create.step2');
        Route::post('/etape-2', [ProfileWizardController::class, 'storeStepTwo'])
            ->middleware('wizard.step:2')->name('profile.store.step2');

        Route::get('/etape-3', [ProfileWizardController::class, 'stepThree'])
            ->middleware('wizard.step:3')->name('profile.create.step3');
        Route::post('/etape-3', [ProfileWizardController::class, 'storeStepThree'])
            ->middleware('wizard.step:3')->name('profile.store.step3');
    });

    // Aperçu avant activation : l'écran décisif.
    Route::get('/profil/apercu', [ProfilePreviewController::class, 'show'])
        ->name('profile.preview');

    /*
     |-------------------------------------------------------------------------
     | TÉLÉCHARGEMENTS DE LA CARTE
     |-------------------------------------------------------------------------
     | Aucun identifiant en URL : la carte vient toujours du compte connecté.
     | Il n'existe donc aucun moyen de demander celle d'un autre, même en
     | devinant un slug. La ProfilePolicy tranche en plus le cas du PDF, qui
     | exige une carte réellement publiée.
     */
    /*
     |-------------------------------------------------------------------------
     | PAGES DU MENU
     |-------------------------------------------------------------------------
     | Ces trois entrées étaient des <span> inertes : le menu ne menait nulle
     | part sur deux entrées de cinq. Elles ont désormais leur page.
     */
    Route::get('/profil', ProfilePageController::class)->name('profil.index');
    Route::get('/carte/qr', QrCodePageController::class)->name('carte.qr');
    Route::get('/statistiques', StatisticsController::class)->name('statistiques');

    Route::get('/carte/qr.png', [CardController::class, 'qrPng'])->name('carte.qr.png');
    Route::get('/carte/qr.svg', [CardController::class, 'qrSvg'])->name('carte.qr.svg');
    Route::get('/carte/imprimable.pdf', [CardController::class, 'printable'])->name('carte.imprimable');

    // Activation. Immédiate pendant l'essai gratuit, sinon passage au paiement.
    Route::post('/abonnement/activer', [ProfileActivationController::class, 'store'])
        ->name('abonnement.checkout');

    /*
     |-------------------------------------------------------------------------
     | PAIEMENT
     |-------------------------------------------------------------------------
     | Le Payment naît en « pending » AVANT tout départ vers l'opérateur :
     | une réponse perdue en route laisse quand même une trace vérifiable.
     | Rien n'est accordé tant que le retour n'est pas confirmé.
     */
    Route::get('/abonnement/paiement', [PaymentController::class, 'show'])
        ->name('abonnement.paiement');

    Route::post('/abonnement/paiement', [PaymentController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('abonnement.paiement.store');

    // Tient la place de la page opérateur. Locale uniquement (404 ailleurs).
    Route::get('/abonnement/simulation/{payment}', [PaymentController::class, 'simulate'])
        ->name('abonnement.simulation');

    Route::get('/abonnement/retour/{payment}', [PaymentController::class, 'callback'])
        ->name('abonnement.retour');

    // Ce que le client vient d'acheter : sa carte, son lien, ses fichiers.
    Route::get('/abonnement/confirmation', [PaymentController::class, 'confirmation'])
        ->name('abonnement.confirmation');

    /*
     |-------------------------------------------------------------------------
     | LE COMPTE (users) — identifiants d'accès
     |-------------------------------------------------------------------------
     | À ne pas confondre avec le PROFIL professionnel (profiles), géré par
     | les routes profile.create.* ci-dessus.
     */
    Route::get('/compte', [AccountController::class, 'edit'])->name('compte.edit');
    Route::patch('/compte', [AccountController::class, 'update'])->name('compte.update');
    Route::delete('/compte', [AccountController::class, 'destroy'])->name('compte.destroy');
});

require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| PROFIL PUBLIC — TOUJOURS EN DERNIER
|--------------------------------------------------------------------------
| Cette route capture un segment libre. Déclarée plus haut, elle avalerait
| « /dashboard », « /compte » et le reste. Sa place est ici, après tout.
*/
Route::get('/p/{slug}', [PublicProfileController::class, 'show'])
    ->name('profile.public');
