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
    public function __construct(
        private QrCodeService $qr,
        private CardTextureService $texture,
    ) {}

    /**
     * Le PDF en octets. Rendu à la demande, jamais mis en cache.
     *
     * TOUT EST EMBARQUÉ EN base64. DomPDF résout les chemins de fichiers de
     * façon imprévisible selon l'hébergement, et une image manquante ne
     * produit pas d'erreur : elle laisse un rectangle vide. Sur un fichier
     * destiné à l'imprimeur, ce silence est inacceptable — les octets voyagent
     * donc avec le document.
     */
    public function render(Profile $profile): string
    {
        $variante = $profile->variante();

        return Pdf::loadView('profile.printable', [
            'profile' => $profile,
            'variante' => $variante,

            // RECTO — le QR du porteur, aux couleurs de la variante. Le fond
            // est CUIT dans l'image plutôt que laissé transparent, la
            // transparence PNG étant une faiblesse connue de DomPDF.
            'qrRecto' => $this->enBase64($this->qr->cartePng($profile)),

            // VERSO — le QR de la PLATEFORME, en orientation standard. Il est
            // identique sur toutes les cartes : le cache est global.
            'qrVerso' => $this->enBase64($this->qr->plateformePng()),

            // Le fond organique, que DomPDF ne saurait pas peindre lui-même.
            'fondVerso' => $this->texture->dataUri($variante),
        ])
            ->setPaper([0, 0, $this->mm(91.6), $this->mm(60)])
            ->output();
    }

    private function enBase64(string $png): string
    {
        return 'data:image/png;base64,'.base64_encode($png);
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
