<?php

namespace App\Http\Controllers;

use App\Support\Langue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * BASCULE DE LANGUE — un formulaire POST, pas un lien.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI POST
 * ═══════════════════════════════════════════════════════════════════════
 * Changer de langue MODIFIE un état côté serveur. Un GET serait suivi par les
 * robots d'indexation et les préchargeurs de navigateur, qui basculeraient la
 * langue de visiteurs qui n'ont rien demandé — et pollueraient les pages en
 * cache.
 *
 * Le formulaire fonctionne sans JavaScript : c'est la règle du projet.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ON REVIENT D'OÙ L'ON VENAIT
 * ═══════════════════════════════════════════════════════════════════════
 * Basculer la langue depuis le milieu d'un parcours ne doit pas ramener à
 * l'accueil : on relit la même page, dans l'autre langue.
 */
class LanguePreferenceController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $valide = $request->validate([
            'langue' => ['required', Rule::in(Langue::disponibles())],
        ]);

        // Pour un compte, la préférence suit la personne — et sert aussi aux
        // e-mails, qui partent hors session et n'ont aucun cookie à lire.
        $request->user()?->forceFill(['locale' => $valide['langue']])->save();

        /*
         | LE COOKIE EST POSÉ MÊME POUR UN COMPTE.
         |
         | C'est lui qui rend le tout premier rendu après connexion déjà dans
         | la bonne langue, avant que la session ne soit lue.
         */
        return back()->withCookie(
            cookie(Langue::nomDuCookie(), $valide['langue'], Langue::DUREE_COOKIE)
        );
    }
}
