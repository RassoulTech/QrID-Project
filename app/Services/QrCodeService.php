<?php

namespace App\Services;

use App\Models\Profile;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use Illuminate\Support\Facades\Storage;
use Picqer\Barcode\Renderers\SvgRenderer;
use Picqer\Barcode\Types\TypeCode128;
use SimpleSoftwareIO\QrCode\Generator;

/**
 * Génération du QR Code d'une carte.
 *
 * Ce QR finit IMPRIMÉ sur une carte PVC qui vivra dans une poche, un
 * portefeuille, un tiroir. Il sera rayé, plié, photographié de travers, sous
 * une lumière quelconque. Tous les réglages ci-dessous découlent de cette
 * seule contrainte — un QR qui ne scanne pas rend la carte inutile, et le
 * client ne peut rien y faire une fois les cartes imprimées.
 */
class QrCodeService
{
    /**
     * Correction d'erreur HAUTE (30 %).
     *
     * Le QR reste lisible même si près d'un tiers du motif est illisible :
     * rayure, salissure, coin corné. Le niveau « M » (15 %), courant sur le
     * web, ne tient pas sur un support physique. Le coût est une densité de
     * modules plus élevée, sans conséquence à la taille où l'on imprime.
     */
    private const CORRECTION = 'H';

    /**
     * Marge, en modules. La norme en exige 4 : c'est cette zone de silence
     * qui permet au lecteur de délimiter le code. En dessous, les scans
     * échouent de façon intermittente — le pire des défauts, car il passe
     * les tests et rate en clientèle.
     */
    private const MARGE = 4;

    /** Côté du PNG, en pixels. 1024 px ≈ 300 dpi sur 8,7 cm. */
    private const TAILLE_PNG = 1024;

    /** Côté du SVG affiché à l'écran et repris à l'impression. */
    private const TAILLE_SVG = 512;

    /** Le QR encode TOUJOURS l'URL publique complète, jamais le slug seul. */
    public function url(Profile $profile): string
    {
        return route('profile.public', $profile->slug);
    }

    /**
     * SVG — affichage web et impression.
     *
     * Vectoriel, donc net à toute taille : c'est la sortie de référence pour
     * l'imprimeur. Aucune extension requise, bacon le produit en PHP pur.
     */
    public function svg(Profile $profile): string
    {
        return $this->cache($profile, 'svg', fn () => (string) $this->generator($profile)
            ->format('svg')
            ->size(self::TAILLE_SVG)
            ->generate($this->url($profile))
        );
    }

    /**
     * PNG haute définition — téléchargement et intégration au PDF.
     *
     * simple-qrcode produit du PNG via imagick, absent ici. On rend donc le
     * SVG puis on le rastérise nous-mêmes en GD, à partir de la matrice :
     * même source, aucune dépendance de plus, et un rendu net car les modules
     * tombent sur des pixels entiers.
     */
    public function png(Profile $profile): string
    {
        return $this->cache($profile, 'png', fn () => $this->rasteriser($profile));
    }

    /**
     * SVG INVERSÉ — modules BLANCS, fond transparent.
     *
     * Destiné à la carte : le code se pose directement sur le vert, sans
     * cadre blanc autour.
     *
     * AVERTISSEMENT TECHNIQUE. La norme ISO/IEC 18004 décrit un code SOMBRE
     * sur fond CLAIR. Un code inversé sort de cette description : les lecteurs
     * modernes (appareil photo iOS, Google Lens) le gèrent, d'autres non, et
     * l'échec est silencieux — l'utilisateur croit que la carte est mauvaise.
     *
     * Deux garde-fous conservés : correction d'erreur H (30 %) et zone de
     * silence de 4 modules, ici remplie par le vert de la carte. Le fichier
     * TÉLÉCHARGEABLE, lui, reste au format standard : c'est celui qu'on
     * confie à un imprimeur ou qu'on partage, et il doit fonctionner partout.
     */
    public function invertedSvg(Profile $profile): string
    {
        return $this->cache($profile, 'inverse.svg', function () use ($profile) {
            $svg = (string) (new Generator)
                ->format('svg')
                ->errorCorrection(self::CORRECTION)
                ->margin(self::MARGE)
                ->size(self::TAILLE_SVG)
                ->color(255, 255, 255)
                ->generate($this->url($profile));

            // bacon pose toujours un rectangle de fond opaque. On le retire :
            // c'est le vert de la carte qui doit apparaître à travers.
            return preg_replace('#<rect\b[^>]*/>#', '', $svg, 1) ?? $svg;
        });
    }

    /**
     * CODE-BARRES Code 128 — repli linéaire du QR Code.
     *
     * AVERTISSEMENT DE FABRICATION, mesuré et non supposé.
     *
     * Un Code 128 encodant l'URL complète produit 453 modules. Sur la moitié
     * de la zone visuelle d'une carte ID-1, soit ≈28 mm, chaque module ferait
     * 0,062 mm — pour un seuil de lecture fiable situé autour de 0,19 mm.
     * Il faudrait 86 mm de large, davantage que la carte entière.
     *
     * Le contenu est donc réglable : BARCODE_CONTENT=slug ramène le code à
     * 189 modules (0,15 mm), déjà bien plus proche du lisible. Par défaut on
     * encode l'URL, conformément à la demande — mais à cette taille le code
     * est décoratif, pas fonctionnel.
     *
     * SVG : vectoriel, donc net à l'impression et sans dépendance d'image.
     */
    public function barcodeSvg(Profile $profile): string
    {
        return $this->cache($profile, 'code128.svg', function () use ($profile) {
            $contenu = config('landing.brand.barcode_content') === 'slug'
                ? $profile->slug
                : $this->url($profile);

            $barres = (new TypeCode128)->getBarcode($contenu);

            // Barres BLANCHES, fond transparent : le code se pose sur le vert
            // de la carte, comme le QR du recto. Le SVG est rendu en ligne,
            // sans en-tête XML, pour être intégré directement dans la page.
            return (new SvgRenderer)
                ->setForegroundColor([255, 255, 255])
                ->setBackgroundColor(null)
                ->setSvgType(SvgRenderer::TYPE_SVG_INLINE)
                ->render($barres, $barres->getWidth() * 2, 90);
        });
    }

    /** Chemins relatifs des fichiers, sur le disque public. */
    public function path(Profile $profile, string $format): string
    {
        return "qr/{$profile->slug}.{$format}";
    }

    /** URL publique du fichier, pour un <img> ou un téléchargement. */
    public function fileUrl(Profile $profile, string $format): string
    {
        return Storage::disk('public')->url($this->path($profile, $format));
    }

    /**
     * Régénère les deux formats. Appelé à la création du profil et à chaque
     * changement de slug ou de couleur — c'est-à-dire aux deux seuls moments
     * où le contenu ou l'apparence du QR change réellement.
     */
    public function refresh(Profile $profile): void
    {
        $this->forget($profile);

        $this->svg($profile);
        $this->png($profile);
        $this->invertedSvg($profile);
        $this->invertedPng($profile);
        $this->barcodeSvg($profile);
    }

    public function forget(Profile $profile): void
    {
        foreach (['svg', 'png', 'inverse.svg', 'inverse.png', 'code128.svg'] as $format) {
            Storage::disk('public')->delete($this->path($profile, $format));
        }
    }

    /**
     * PNG INVERSÉ — modules blancs sur le vert de la carte.
     *
     * Réservé au PDF de l'imprimeur : DomPDF ne rend qu'imparfaitement le SVG,
     * et une carte partie à l'impression avec un code approximatif ne se
     * corrige pas par un déploiement. Le vert est cuit dans l'image plutôt que
     * laissé transparent, la transparence PNG étant l'autre faiblesse connue
     * de ce moteur.
     */
    public function invertedPng(Profile $profile): string
    {
        return $this->cache($profile, 'inverse.png', fn () => $this->rasteriser($profile, true));
    }

    // -----------------------------------------------------------------------

    /**
     * Écrit une fois, relit ensuite.
     *
     * Un QR ne dépend que du slug et de la couleur : le régénérer à chaque
     * affichage du tableau de bord serait du calcul pur perdu, sur la page la
     * plus consultée de l'espace client.
     */
    private function cache(Profile $profile, string $format, callable $produire): string
    {
        $chemin = $this->path($profile, $format);
        $disque = Storage::disk('public');

        if (! $disque->exists($chemin)) {
            $disque->put($chemin, $produire());
        }

        return (string) $disque->get($chemin);
    }

    private function generator(Profile $profile): Generator
    {
        [$r, $v, $b] = $this->rgb($profile->primary_color ?: '#0B3B2E');

        return (new Generator)
            ->errorCorrection(self::CORRECTION)
            ->margin(self::MARGE)
            // Fond blanc explicite : un QR sur fond transparent devient
            // illisible dès qu'on le pose sur une carte colorée.
            ->backgroundColor(255, 255, 255)
            ->color($r, $v, $b);
    }

    /**
     * PNG rendu en GD depuis la MATRICE du QR.
     *
     * On ne rastérise pas le SVG : bacon y écrit un unique <path> en
     * « evenodd », dont l'analyse serait aussi fragile qu'inutile. On demande
     * donc la matrice à l'encodeur, avec EXACTEMENT les mêmes paramètres que
     * ceux du SVG — même contenu, même niveau de correction, même encodage
     * d'octets. Les deux formats portent ainsi le même QR, au module près
     * (33 × 33 ici, vérifié).
     *
     * ENCODAGE : ISO-8859-1, celui du paquet. Passer « UTF-8 » ajouterait un
     * en-tête ECI, ferait grossir le code d'une version (37 modules au lieu de
     * 33) et produirait deux QR différents pour la même carte.
     *
     * L'échelle est un ENTIER : chaque module tombe sur un nombre entier de
     * pixels, sans interpolation. Un module à 12,4 px produirait des bords
     * flous, et un bord flou est un scan qui échoue une fois sur trois.
     */
    private function rasteriser(Profile $profile, bool $inverse = false): string
    {
        $qr = Encoder::encode($this->url($profile), ErrorCorrectionLevel::H(), 'ISO-8859-1');

        $matrice = $qr->getMatrix();
        $modules = $matrice->getWidth();
        $total = $modules + self::MARGE * 2;

        $echelle = max(1, (int) floor(self::TAILLE_PNG / $total));
        $cote = $total * $echelle;

        $image = imagecreatetruecolor($cote, $cote);

        [$r, $v, $b] = $this->rgb($profile->primary_color ?: '#0B3B2E');

        // La zone de silence prend la couleur du FOND : c'est elle qui permet
        // au lecteur de délimiter le code, elle doit donc contraster avec les
        // modules, pas avec la page.
        if ($inverse) {
            imagefill($image, 0, 0, imagecolorallocate($image, $r, $v, $b));
            $encre = imagecolorallocate($image, 255, 255, 255);
        } else {
            imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
            $encre = imagecolorallocate($image, $r, $v, $b);
        }

        for ($y = 0; $y < $modules; $y++) {
            for ($x = 0; $x < $modules; $x++) {
                if ($matrice->get($x, $y) !== 1) {
                    continue;
                }

                $px = ($x + self::MARGE) * $echelle;
                $py = ($y + self::MARGE) * $echelle;

                imagefilledrectangle($image, $px, $py, $px + $echelle - 1, $py + $echelle - 1, $encre);
            }
        }

        ob_start();
        imagepng($image, null, 9);
        $binaire = (string) ob_get_clean();

        imagedestroy($image);

        return $binaire;
    }

    /** « #0B3B2E » → [11, 59, 46]. */
    private function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) !== 6) {
            $hex = '0B3B2E';
        }

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }
}
