<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Services\QrCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * « Mon profil » — consultation de la carte et de ses informations.
 *
 * L'ÉDITION reste confiée au parcours en trois étapes, via profile.edit.
 * Dupliquer ici un formulaire reprenant les mêmes champs aurait doublé la
 * surface de validation, de normalisation du téléphone, de traitement de la
 * photo et de gestion des réseaux — pour le même résultat. Le parcours SERT
 * déjà de parcours d'édition : il recharge le profil en session et permet de
 * tout reprendre, étape par étape.
 *
 * Cet écran montre donc ce qui existe, et ouvre l'édition là où il faut.
 */
class ProfilePageController extends Controller
{
    public function __invoke(Request $request, QrCodeService $qr): View|RedirectResponse
    {
        $profile = $request->user()->profile()->with(['socialLinks', 'template'])->first();

        if (! $profile) {
            return redirect()->route('profile.create.step1');
        }

        return view('profil.index', [
            'profile' => $profile,
            'qrSvg' => $qr->svg($profile),
            'publicUrl' => $qr->url($profile),
        ]);
    }
}
