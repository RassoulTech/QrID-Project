<?php

namespace App\Listeners;

use App\Support\Langue;
use Illuminate\Auth\Events\Login;

/**
 * LE CHOIX FAIT AVANT LA CONNEXION SUIT LA PERSONNE APRÈS.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI UN LISTENER, ET PAS UNE LIGNE DANS LE CONTRÔLEUR DE CONNEXION
 * ═══════════════════════════════════════════════════════════════════════
 * On entre dans un compte par CINQ chemins : le formulaire de connexion,
 * Google, la confirmation d'inscription, la réinitialisation de mot de passe,
 * et le « se souvenir de moi » qui reconnecte tout seul au retour.
 *
 * Écrire le report dans AuthenticatedSessionController le ferait fonctionner
 * sur un chemin et échouer silencieusement sur les quatre autres — et ce
 * genre d'oubli ne casse aucun test : la page s'affiche, simplement dans la
 * mauvaise langue.
 *
 * L'événement Login est émis par le garde lui-même, quel que soit le chemin.
 * Il n'y en a qu'un, et il ne peut pas être contourné.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CE QUI EST REPORTÉ EST TOUJOURS UN CHOIX, JAMAIS UNE SUPPOSITION
 * ═══════════════════════════════════════════════════════════════════════
 * La clé de session n'est écrite que par le sélecteur. La négociation sur
 * Accept-Language, elle, ne mémorise rien. Trouver une valeur ici, c'est donc
 * toujours trouver un clic délibéré, fait dans ce navigateur quelques minutes
 * plus tôt.
 */
class ReporterLangueALaConnexion
{
    public function handle(Login $event): void
    {
        Langue::reporterSurLeCompte($event->user);
    }
}
