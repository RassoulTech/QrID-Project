<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Services\QrCodeService;
use Barryvdh\DomPDF\Facade\Pdf;
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
    public function __construct(private QrCodeService $qr) {}

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
     * Format exact 85,6 × 54 mm plus 3 mm de fonds perdus sur chaque bord,
     * soit 91,6 × 60 mm de page. Ces fonds perdus ne sont pas décoratifs :
     * sans eux, la moindre dérive de massicot laisse un liseré blanc sur le
     * bord d'une carte à fond vert.
     */
    public function printable(Request $request): Response
    {
        $profile = $this->carteDe($request);

        $this->authorize('downloadPrintable', $profile);

        $pdf = Pdf::loadView('profile.printable', [
            'profile' => $profile,
            // Version INVERSÉE : modules blancs sur le vert de la carte, comme
            // à l'écran. Le vert est cuit dans l'image, la transparence PNG
            // étant l'autre faiblesse connue de DomPDF.
            'qrPng' => 'data:image/png;base64,'.base64_encode($this->qr->invertedPng($profile)),
        ])->setPaper([0, 0, $this->mm(91.6), $this->mm(60)]);

        return $pdf->download('carte-'.$profile->slug.'.pdf');
    }

    // -----------------------------------------------------------------------

    /** La carte du compte connecté, ou 404. Jamais celle d'un autre. */
    private function carteDe(Request $request): Profile
    {
        return $request->user()->profile ?? abort(404);
    }

    /** Millimètres → points PostScript (1 pt = 1/72 pouce). */
    private function mm(float $mm): float
    {
        return $mm * 72 / 25.4;
    }
}
