<?php

use App\Http\Controllers\Admin\AdminHomeController;
use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\ClientBlockController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\OverviewController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PaymentVerificationController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\ProfileDeactivationController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\StatisticsController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\SubscriptionExtensionController;
use App\Http\Controllers\Admin\SystemHealthController;
use App\Http\Controllers\Admin\TemplateController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ESPACE ADMINISTRATEUR
|--------------------------------------------------------------------------
| TOUT est sous /admin et sous le middleware `admin`. Le groupe est déclaré
| une seule fois ici : il n'existe aucun moyen d'ajouter une route admin en
| oubliant la protection, puisque ce fichier n'a pas d'autre niveau racine.
|
| `admin` renvoie 403, jamais 404 ni un écran partiel. Un compte authentifié
| a le droit de savoir que la page existe et ne lui est pas destinée ; la
| route est publique dans le code de toute façon.
|
| AUCUNE CLOSURE. Chaque route pointe une classe, ce qui la rend testable,
| et surtout cachable par route:cache — une closure ferait échouer la mise
| en cache en production.
|
| VERBES : GET pour lire, POST pour créer un fait, PATCH pour modifier un
| état, DELETE pour l'annuler. Les actions destructrices ne sont jamais
| atteignables en GET : aucun lien, aucun préchargement de navigateur, aucun
| robot ne peut bloquer un compte.
*/

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // /admin redirige vers la vue d'ensemble. Une URL courte que l'on
        // tape de mémoire doit aboutir, pas afficher une page vide.
        Route::get('/', AdminHomeController::class)->name('home');

        /*
         |---------------------------------------------------------------------
         | 1. VUE D'ENSEMBLE
         |---------------------------------------------------------------------
         | La période est un paramètre de requête (?periode=30j), pas un
         | segment d'URL : c'est un filtre, il doit se combiner librement et
         | survivre à un partage de lien.
         */
        Route::get('/vue-ensemble', [OverviewController::class, 'index'])->name('overview');
        Route::get('/vue-ensemble/export', [OverviewController::class, 'export'])->name('overview.export');

        /*
         |---------------------------------------------------------------------
         | STATISTIQUES — l'usage réel des cartes
         |---------------------------------------------------------------------
         | Distinct de la vue d'ensemble, et la distinction est le seul motif
         | d'avoir deux entrées de menu : la vue d'ensemble dit où en est
         | l'entreprise (comptes, abonnements, recettes), celui-ci dit si les
         | cartes servent (vues, scans, enregistrements).
         |
         | Un produit peut vendre beaucoup et n'être jamais utilisé. C'est ce
         | que cet écran rend visible, et que le premier masquerait.
         */
        Route::get('/statistiques', [StatisticsController::class, 'index'])->name('statistics');
        Route::get('/statistiques/export', [StatisticsController::class, 'export'])->name('statistics.export');

        /*
         |---------------------------------------------------------------------
         | 2 & 3. CLIENTS — liste et fiche
         |---------------------------------------------------------------------
         | « clients » et non « utilisateurs » : c'est le mot de la maquette et
         | celui du métier. Les administrateurs sont des utilisateurs aussi,
         | la liste ne montre que les comptes clients.
         |
         | L'export est déclaré AVANT /{user} : sans cela, « export » serait
         | capté comme un identifiant de client.
         */
        Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
        Route::get('/clients/export', [ClientController::class, 'export'])->name('clients.export');
        Route::get('/clients/{user}', [ClientController::class, 'show'])->name('clients.show');

        // Blocage d'un compte. POST pour poser le fait, DELETE pour le lever.
        // Motif obligatoire à la pose, journalisé, sessions du client tuées.
        Route::post('/clients/{user}/blocage', [ClientBlockController::class, 'store'])->name('clients.block');
        Route::delete('/clients/{user}/blocage', [ClientBlockController::class, 'destroy'])->name('clients.unblock');

        // Prolongation manuelle d'abonnement. Motif obligatoire, tracée.
        Route::post('/clients/{user}/abonnement/prolongation', [SubscriptionExtensionController::class, 'store'])
            ->name('clients.subscription.extend');

        /*
         |---------------------------------------------------------------------
         | 4. PAIEMENTS
         |---------------------------------------------------------------------
         | La vérification manuelle interroge la passerelle. Limitée en débit :
         | c'est un appel réseau sortant, déclenché à la main, sur une liste où
         | l'on peut cliquer vite.
         */
        Route::get('/paiements', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('/paiements/export', [PaymentController::class, 'export'])->name('payments.export');

        Route::post('/paiements/{payment}/verification', [PaymentVerificationController::class, 'store'])
            ->middleware('throttle:30,1')
            ->name('payments.verify');

        /*
         |---------------------------------------------------------------------
         | ABONNEMENTS
         |---------------------------------------------------------------------
         | Lecture seule. Les abonnements ne se créent pas à la main : ils
         | naissent d'un encaissement. La seule écriture possible est la
         | prolongation, portée par la fiche client, avec motif et journal.
         */
        Route::get('/abonnements', [SubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::get('/abonnements/export', [SubscriptionController::class, 'export'])->name('subscriptions.export');

        /*
         |---------------------------------------------------------------------
         | 5. PROFILS
         |---------------------------------------------------------------------
         | L'administration NE MODIFIE JAMAIS le contenu d'un profil. Il n'y a
         | donc ni route d'édition ni route de mise à jour ici, et ce n'est pas
         | un oubli : la seule prise sur un profil est de le couper, avec motif.
         */
        Route::get('/profils', [AdminProfileController::class, 'index'])->name('profiles.index');
        Route::get('/profils/export', [AdminProfileController::class, 'export'])->name('profiles.export');

        Route::post('/profils/{profile}/desactivation', [ProfileDeactivationController::class, 'store'])
            ->name('profiles.deactivate');
        Route::delete('/profils/{profile}/desactivation', [ProfileDeactivationController::class, 'destroy'])
            ->name('profiles.reactivate');

        /*
         |---------------------------------------------------------------------
         | 6. MODÈLES DE CARTE
         |---------------------------------------------------------------------
         | PATCH et non POST pour l'interrupteur et le défaut : on modifie
         | l'état d'une ressource existante, on n'en crée aucune.
         */
        Route::get('/modeles', [TemplateController::class, 'index'])->name('templates.index');

        Route::patch('/modeles/{template}/activation', [TemplateController::class, 'toggle'])
            ->name('templates.toggle');
        Route::patch('/modeles/{template}/defaut', [TemplateController::class, 'makeDefault'])
            ->name('templates.default');
        Route::post('/modeles/{template}/duplication', [TemplateController::class, 'duplicate'])
            ->name('templates.duplicate');

        /*
         |---------------------------------------------------------------------
         | 7. PARAMÈTRES
         |---------------------------------------------------------------------
         | Un seul écran, trois onglets. L'onglet est un paramètre de requête
         | et le plan sélectionné un segment : le lien vers un plan précis doit
         | pouvoir être copié et rouvert tel quel.
         */
        Route::get('/parametres', [SettingsController::class, 'index'])->name('settings');
        Route::get('/parametres/plans/{plan}', [SettingsController::class, 'plan'])->name('settings.plan');

        Route::post('/parametres/plans', [PlanController::class, 'store'])->name('settings.plan.store');
        Route::patch('/parametres/plans/{plan}', [PlanController::class, 'update'])->name('settings.plan.update');

        /*
         |---------------------------------------------------------------------
         | 8. JOURNAL D'AUDIT
         |---------------------------------------------------------------------
         | Lecture seule, et il n'existe volontairement AUCUNE route d'écriture
         | ni de suppression. Un journal que l'administration peut effacer ne
         | prouve rien.
         */
        Route::get('/journal', [AuditController::class, 'index'])->name('audit.index');
        Route::get('/journal/export', [AuditController::class, 'export'])->name('audit.export');

        // État système — écran préexistant, conservé.
        Route::get('/etat-systeme', [SystemHealthController::class, 'index'])->name('system.health');
    });
