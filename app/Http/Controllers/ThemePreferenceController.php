<?php

namespace App\Http\Controllers;

use App\Support\Theme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Bascule clair / sombre — accessible à TOUT LE MONDE.
 *
 * Hors du groupe « auth », et c'est le point : un visiteur de la landing ou
 * du formulaire de connexion doit pouvoir basculer. Réserver le thème sombre
 * aux clients connectés reviendrait à l'imposer en clair précisément aux
 * écrans que l'on découvre en premier.
 *
 * EN POST, et la préférence est écrite côté serveur. Trois conséquences :
 *
 * · le serveur pose la classe sur <html> au premier octet de la réponse.
 *   Aucun clignotement au chargement d'une page sombre, contrairement à une
 *   bascule appliquée par JavaScript après coup ;
 * · pour un compte, la préférence suit la PERSONNE, pas le navigateur ;
 * · pour un invité, un cookie d'un an prend le relais.
 *
 * Aucun JavaScript n'intervient : le formulaire recharge la page et le serveur
 * repose la classe. Un fichier JS qui ne charge pas ne laisse donc personne
 * bloqué sur un écran clair.
 */
class ThemePreferenceController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $valide = $request->validate([
            'theme' => ['required', Rule::in([Theme::CLAIR, Theme::SOMBRE])],
        ]);

        $request->user()?->forceFill(['theme' => $valide['theme']])->save();

        /*
         | Le cookie est posé même pour un compte : c'est lui qui rend le
         | TOUT PREMIER rendu correct après une reconnexion, avant que la
         | session ne soit lue.
         */
        // Retour exactement là où l'utilisateur était : une bascule de thème
        // ne doit jamais lui faire perdre sa place.
        return back()->withCookie(
            cookie(Theme::nomDuCookie(), $valide['theme'], Theme::DUREE_COOKIE)
        );
    }
}
