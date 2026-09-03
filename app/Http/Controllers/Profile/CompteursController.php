<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Services\StatistiquesLecture;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * LES COMPTEURS DU TABLEAU DE BORD, RELUS SANS RECHARGER LA PAGE.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * POURQUOI PAS DE WEBSOCKET, ET POURQUOI PAS DE SSE
 * ═══════════════════════════════════════════════════════════════════════════
 * Une diffusion en direct suppose un processus qui tient la connexion
 * ouverte. Ce produit tourne sur un conteneur de 512 Mo avec TROIS enfants
 * php-fpm : une seule connexion SSE maintenue en occuperait un tiers, et
 * trois clients connectés simultanément bloqueraient le site pour tout le
 * monde. Un serveur WebSocket, lui, demande un service permanent — donc un
 * plan payant que ce produit a choisi de ne pas prendre.
 *
 * Ce ne sont pas des mécanismes « moins modernes » : ce sont des mécanismes
 * que cette infrastructure ne peut pas porter, et les employer quand même
 * produirait une panne à trois utilisateurs.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * NI POLLING EN BOUCLE — LE MOMENT UTILE EST LE RETOUR SUR L'ONGLET
 * ═══════════════════════════════════════════════════════════════════════════
 * Un `setInterval` de trente secondes interroge le serveur toute la journée,
 * y compris devant un écran verrouillé, pour un chiffre que personne ne
 * regarde.
 *
 * Or le cas réel est précis : le client publie sa carte, prend son téléphone,
 * scanne son propre QR Code pour vérifier — puis revient sur son ordinateur.
 * C'est à cet instant, et à aucun autre, que le compteur doit avoir changé.
 *
 * Le navigateur sait dire quand un onglet redevient visible. Zéro requête
 * pendant qu'on ne regarde pas, un chiffre juste au moment où l'on revient.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * CE QU'ELLE NE REND PAS
 * ═══════════════════════════════════════════════════════════════════════════
 * Quatre entiers, et rien d'autre. Pas le profil, pas l'abonnement, aucune
 * donnée nominative : une réponse JSON traîne dans le cache du navigateur et
 * dans les outils de développement bien plus longtemps qu'une page.
 */
class CompteursController extends Controller
{
    public function __construct(private readonly StatistiquesLecture $lecture) {}

    public function __invoke(Request $request): JsonResponse
    {
        $profile = $request->user()->profile;

        if (! $profile) {
            // Pas de carte, pas de compteurs. Un 200 avec des zéros
            // laisserait croire à une carte sans audience ; un objet vide
            // dit qu'il n'y a rien à afficher.
            return response()->json(['compteurs' => null]);
        }

        $cumules = $this->lecture->totauxCumules($profile->id);

        return response()->json(['compteurs' => [
            'views' => $cumules['vues'],
            'scans' => $cumules['scans'],
            'saves' => $cumules['saves'],
        ]]);
    }
}
