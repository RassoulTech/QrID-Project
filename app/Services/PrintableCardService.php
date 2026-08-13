<?php

namespace App\Services;

use App\Models\Profile;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Le PDF de la carte, prêt pour l'imprimeur.
 *
 * Extrait de CardController le 13 août : l'e-mail de confirmation de paiement
 * doit joindre exactement le même fichier que celui qu'on télécharge. Deux
 * générations distinctes auraient fini par diverger — et la divergence se
 * serait constatée sur des cartes déjà imprimées, c'est-à-dire trop tard.
 *
 * FORMAT — 85,6 × 54 mm, plus 3 mm de fonds perdus sur chaque bord, soit
 * 91,6 × 60 mm de page. Ces fonds perdus ne sont pas décoratifs : sans eux,
 * la moindre dérive de massicot laisse un liseré blanc sur le bord d'une
 * carte à fond vert.
 */
class PrintableCardService
{
    public function __construct(private QrCodeService $qr) {}

    /** Le PDF en octets. Rendu à la demande, jamais mis en cache. */
    public function render(Profile $profile): string
    {
        return Pdf::loadView('profile.printable', [
            'profile' => $profile,
            // Version INVERSÉE : modules blancs sur le vert de la carte, comme
            // à l'écran. Le vert est cuit dans l'image, la transparence PNG
            // étant l'autre faiblesse connue de DomPDF.
            'qrPng' => 'data:image/png;base64,'.base64_encode($this->qr->invertedPng($profile)),
        ])
            ->setPaper([0, 0, $this->mm(91.6), $this->mm(60)])
            ->output();
    }

    /** Nom de fichier — identique au téléchargement et à la pièce jointe. */
    public function filename(Profile $profile): string
    {
        return 'carte-'.$profile->slug.'.pdf';
    }

    /** Millimètres → points PostScript (1 pt = 1/72 pouce). */
    private function mm(float $mm): float
    {
        return $mm * 72 / 25.4;
    }
}
