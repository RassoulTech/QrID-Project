<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\View;

/**
 * Accueil de l'administration.
 *
 * /admin est une URL que l'on tape de mémoire : elle doit aboutir. Elle
 * renvoie sur le premier écran du menu qui EXISTE RÉELLEMENT.
 *
 * Une redirection plutôt qu'un rendu direct : l'écran d'accueil garde une
 * seule adresse canonique. Deux URL affichant la même page dédoubleraient
 * l'entrée active du menu.
 *
 * LA VÉRIFICATION D'EXISTENCE N'EST PAS UNE PRÉCAUTION DÉCORATIVE. Cette
 * redirection est la destination de connexion de tout compte administrateur
 * (voir App\Support\HomeRedirect) : pointer une vue absente ne donnerait pas
 * une page vide, mais une erreur 500 dès l'identification, sans aucun moyen
 * d'atteindre le reste du produit. L'état système, lui, existe depuis
 * toujours. Le repli disparaîtra avec la construction de la vue d'ensemble.
 */
class AdminHomeController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        return View::exists('admin.overview')
            ? redirect()->route('admin.overview')
            : redirect()->route('admin.system.health');
    }
}
