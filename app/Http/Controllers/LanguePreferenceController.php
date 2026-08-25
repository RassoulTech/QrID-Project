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
 * LES TROIS MÉMOIRES SONT ÉCRITES D'UN SEUL COUP
 * ═══════════════════════════════════════════════════════════════════════
 * Elles ne font pas double emploi — chacune couvre ce que les deux autres
 * ne savent pas faire :
 *
 *   · LA BASE suit la personne d'un appareil à l'autre, et c'est la seule
 *     que liront les E-MAILS : un rappel d'échéance part hors session, sans
 *     aucun cookie à consulter ;
 *   · LA SESSION répond sans toucher au disque, et sert au visiteur qui n'a
 *     pas de compte ;
 *   · LE COOKIE survit à l'expiration de la session. Sans lui, revenir le
 *     lendemain suffirait à retomber en français — précisément ce qu'on
 *     cherche à ne plus jamais imposer.
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

        $choix = $valide['langue'];

        $request->user()?->forceFill(['locale' => $choix])->save();

        Langue::memoriserEnSession($choix);

        return back()->withCookie(
            cookie(Langue::nomDuCookie(), $choix, Langue::DUREE_COOKIE)
        );
    }
}
