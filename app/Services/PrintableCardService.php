<?php

namespace App\Services;

use App\Models\Profile;
use App\Support\NomSurCarte;
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
    /**
     * Marge intérieure, en millimètres depuis le BORD DE PAGE.
     *
     * 6 mm = 3 mm de fonds perdus + 3 mm de zone de sécurité. Le texte
     * commence donc à exactement 3 mm du trait de coupe, soit 3,5 % de la
     * largeur de la carte — la même valeur qu'à l'écran.
     *
     * C'est un PLANCHER imposé par l'impression, pas un choix esthétique : en
     * dessous, une dérive de massicot d'un demi-millimètre entame le texte.
     */
    private const MARGE_MM = 6.0;

    /** Largeur de page, fonds perdus compris. */
    private const PAGE_L = 91.6;

    /**
     * Largeur de la carte APRÈS COUPE — ISO/IEC 7810 ID-1.
     *
     * C'est elle, et non la page, qui donne l'échelle des bornes de taille du
     * nom : l'écran montre la carte coupée, et les deux doivent aboutir à la
     * même proportion. Passer la largeur de page ici rendait le plafond et le
     * plancher 7 % trop généreux.
     */
    private const CARTE_L = 85.6;

    public function render(Profile $profile): string
    {
        $variante = $profile->variante();

        $nom = mb_strtoupper($profile->full_name);

        /*
         | LA TAILLE DU NOM VIENT DU MÊME CALCUL QU'À L'ÉCRAN.
         |
         | NomSurCarte est sans unité : on lui passe des millimètres, il rend
         | des millimètres. La conversion en points PostScript se fait ensuite,
         | une seule fois. Reproduire ici le coefficient d'avance aurait garanti
         | une divergence entre l'aperçu et le tirage.
         */
        $utile = self::PAGE_L - 2 * self::MARGE_MM;
        $tailleMm = NomSurCarte::taille($nom, $utile, self::CARTE_L);

        return Pdf::loadView('profile.printable', [
            'profile' => $profile,
            'variante' => $variante,
            'nom' => $nom,
            'marge' => self::MARGE_MM,
            'utile' => $utile,
            'tailleNom' => round($tailleMm * 2.8346, 1),   // mm → points
            'surUneLigne' => NomSurCarte::surUneLigne($tailleMm, self::CARTE_L),

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
