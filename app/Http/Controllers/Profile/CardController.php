<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Services\PrintableCardService;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Téléchargements de la carte : QR Code et fichier pour l'imprimeur.
 *
 * Toutes les méthodes passent par la ProfilePolicy. Le profil vient du compte
 * connecté, jamais d'un identifiant en URL : il n'y a donc aucun moyen de
 * demander la carte de quelqu'un d'autre, même en devinant un slug.
 */
class CardController extends Controller
{
    public function __construct(
        private QrCodeService $qr,
        private PrintableCardService $carte,
    ) {}

    /** QR Code en PNG haute définition (984 px), pour un usage courant. */
    public function qrPng(Request $request): Response
    {
        $profile = $this->carteDe($request);

        $this->authorize('downloadQr', $profile);

        return response($this->qr->png($profile), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="qr-'.$profile->slug.'.png"',
        ]);
    }

    /** QR Code en SVG — vectoriel, c'est le format à donner à un imprimeur. */
    public function qrSvg(Request $request): Response
    {
        $profile = $this->carteDe($request);

        $this->authorize('downloadQr', $profile);

        return response($this->qr->svg($profile), 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="qr-'.$profile->slug.'.svg"',
        ]);
    }

    /**
     * Carte prête pour l'impression — PDF, recto et verso.
     *
     * Le rendu vit dans PrintableCardService : la confirmation de paiement
     * joint EXACTEMENT ce fichier, et deux générations distinctes auraient
     * fini par diverger sur des cartes déjà imprimées.
     */
    public function printable(Request $request): Response
    {
        $profile = $this->carteDe($request);

        $this->authorize('downloadPrintable', $profile);

        return response($this->carte->render($profile), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->carte->filename($profile).'"',
        ]);
    }

    // -----------------------------------------------------------------------

    /** La carte du compte connecté, ou 404. Jamais celle d'un autre. */
    private function carteDe(Request $request): Profile
    {
        return $request->user()->profile ?? abort(404);
    }
}
