<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Services\QrCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * « Mon QR Code » — le code en grand, le lien, les téléchargements.
 *
 * L'entrée de menu existait mais ne menait nulle part : c'était un <span>
 * inerte. Un menu dont une entrée sur trois ne répond pas laisse croire que
 * le produit est en panne.
 */
class QrCodePageController extends Controller
{
    public function __invoke(Request $request, QrCodeService $qr): View|RedirectResponse
    {
        $profile = $request->user()->profile;

        if (! $profile) {
            return redirect()->route('profile.create.step1')
                ->with('info', __('profile.flash.carte_avant_qr'));
        }

        return view('carte.qr', [
            'profile' => $profile,
            'qrSvg' => $qr->svg($profile),
            'publicUrl' => $qr->url($profile),
        ]);
    }
}
