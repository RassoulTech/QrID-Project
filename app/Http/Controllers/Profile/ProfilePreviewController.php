<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Services\QrCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Écran d'aperçu avant paiement — le plus important du produit.
 * L'utilisateur voit son profil tel que ses contacts le verront, puis décide.
 */
class ProfilePreviewController extends Controller
{
    public function __construct(private QrCodeService $qr) {}

    public function show(Request $request): View|RedirectResponse
    {
        $profile = $request->user()->profile;

        if (! $profile) {
            return redirect()->route('profile.create.step1');
        }

        $this->authorize('view', $profile);

        // Déjà actif : l'aperçu n'a plus d'objet.
        if ($profile->isPubliclyVisible()) {
            return redirect()->route('dashboard');
        }

        $profile->load('socialLinks');

        return view('profile.preview', [
            'profile' => $profile,

            // Le QR est intégré en SVG dans la page : vectoriel, net à toute
            // taille, et aucune requête HTTP de plus sur l'écran qui décide
            // de l'achat.
            'qrSvg' => $this->qr->svg($profile),
        ]);
    }
}
