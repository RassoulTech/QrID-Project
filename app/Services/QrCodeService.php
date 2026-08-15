<?php

namespace App\Services;

use App\Models\Profile;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use Illuminate\Support\Facades\Storage;
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

    /**
     * Encre des fichiers TÉLÉCHARGEABLES — le vert de la marque, toujours.
     *
     * Ces fichiers-là partent chez un imprimeur ou dans une signature de
     * courriel : ils doivent être lisibles partout, indépendamment de la
     * variante choisie pour la carte. Les colorer avec la couleur de fond de
     * la variante produirait, pour la variante blanche, un code blanc sur
     * blanc — invisible, et découvert trop tard.
     */
    private const ENCRE_MARQUE = '#0B3B2E';

    /** Le QR encode TOUJOURS l'URL publique complète, jamais le slug seul. */
    public function url(Profile $profile): string
    {
        return route('profile.public', $profile->slug);
    }

    // =======================================================================
    // QR DE LA PLATEFORME — le verso, identique sur toutes les cartes
    // =======================================================================

    /**
     * L'adresse encodée au VERSO : la plateforme, pas le porteur.
     *
     * ═══════════════════════════════════════════════════════════════════
     * DEUX CODES, DEUX DESTINATIONS — ce n'est pas une erreur
     * ═══════════════════════════════════════════════════════════════════
     * Le RECTO mène à la carte de son porteur : c'est ce qu'il donne. Le
     * VERSO mène à la plateforme : c'est ce que découvre celui qui reçoit la
     * carte. Chaque carte distribuée devient ainsi un canal d'acquisition,
     * sans rien coûter à son porteur.
     *
     * LE PARAMÈTRE DE PROVENANCE est ce qui rend l'opération mesurable. Sans
     * lui, les inscriptions venues des cartes seraient indiscernables du
     * trafic direct, et l'on ne saurait jamais si l'idée fonctionne.
     */
    public function urlPlateforme(): string
    {
        $base = rtrim((string) config('app.url'), '/');
        $source = (string) config('landing.brand.card_source', 'carte');

        return $base.'/?'.http_build_query(['src' => $source]);
    }

    /**
     * QR de la plateforme, en SVG. MIS EN CACHE GLOBALEMENT.
     *
     * Il ne dépend d'aucun profil : le produire par carte serait le même
     * calcul répété autant de fois qu'il y a de clients, pour un fichier
     * rigoureusement identique.
     *
     * ORIENTATION STANDARD — modules sombres sur fond clair, conforme à
     * ISO/IEC 18004. Ce code est celui que scannera un inconnu, une fois,
     * peut-être dans une lumière médiocre : il n'y a aucune raison de lui
     * faire courir le risque d'un code inversé.
     */
    public function plateformeSvg(): string
    {
        return $this->cachePlateforme('svg', fn () => (string) (new Generator)
            ->format('svg')
            ->errorCorrection(self::CORRECTION)
            ->margin(self::MARGE)
            ->size(self::TAILLE_SVG)
            ->backgroundColor(255, 255, 255)
            ->color(...$this->rgb(self::ENCRE_MARQUE))
            ->generate($this->urlPlateforme())
        );
    }

    /** QR de la plateforme, en PNG — pour le PDF de l'imprimeur. */
    public function plateformePng(): string
    {
        return $this->cachePlateforme('png', fn () => $this->rasteriserUrl(
            $this->urlPlateforme(),
            self::ENCRE_MARQUE,
            '#FFFFFF'
        ));
    }

    /**
     * Cache global, indexé par une empreinte de l'ADRESSE encodée.
     *
     * L'empreinte n'est pas une précaution théorique : APP_URL change au
     * premier déploiement, et un fichier au nom fixe survivrait au
     * changement. Des cartes partiraient à l'impression avec l'ancienne
     * adresse — un défaut qui ne se corrige pas par un déploiement.
     *
     * @param  callable(): string  $produire
     */
    private function cachePlateforme(string $format, callable $produire): string
    {
        $chemin = $this->cheminPlateforme($format);
        $disque = Storage::disk('public');

        if (! $disque->exists($chemin)) {
            $disque->put($chemin, $produire());
        }

        return (string) $disque->get($chemin);
    }

    public function cheminPlateforme(string $format): string
    {
        $empreinte = substr(sha1($this->urlPlateforme()), 0, 8);

        return "qr/plateforme-{$empreinte}.{$format}";
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
     * LE QR TEL QU'IL EST IMPRIMÉ SUR LE RECTO — couleurs de la variante.
     *
     * Fond TRANSPARENT : c'est la carte elle-même qui remplit la zone de
     * silence, et donc elle qui assure le contraste. Poser un fond opaque
     * dessinerait un rectangle visible au milieu de la carte.
     *
     * ═══════════════════════════════════════════════════════════════════
     * AVERTISSEMENT — LA VARIANTE VERTE PRODUIT UN CODE INVERSÉ
     * ═══════════════════════════════════════════════════════════════════
     * ISO/IEC 18004 décrit un code SOMBRE sur fond CLAIR. La variante blanche
     * s'y conforme ; la verte l'inverse. Les lecteurs modernes gèrent
     * l'inversion, d'autres non, et leur échec est SILENCIEUX — le porteur
     * croit simplement que sa carte est mauvaise.
     *
     * Deux garde-fous demeurent dans les deux cas : correction d'erreur H
     * (30 %) et zone de silence de 4 modules. Et le fichier TÉLÉCHARGEABLE,
     * lui, reste toujours au format standard : c'est celui qu'on confie à un
     * imprimeur ou qu'on partage, il doit fonctionner partout.
     */
    public function carteSvg(Profile $profile): string
    {
        $variante = $profile->variante();

        return $this->cache($profile, 'carte-'.$variante->name.'.svg', function () use ($profile, $variante) {
            $svg = (string) (new Generator)
                ->format('svg')
                ->errorCorrection(self::CORRECTION)
                ->margin(self::MARGE)
                ->size(self::TAILLE_SVG)
                ->color(...$this->rgb($variante->encre()))
                ->generate($this->url($profile));

            // bacon pose toujours un rectangle de fond opaque. On le retire :
            // c'est la carte qui doit apparaître à travers.
            return preg_replace('#<rect\b[^>]*/>#', '', $svg, 1) ?? $svg;
        });
    }

    /**
     * Le même, en PNG — réservé au PDF de l'imprimeur.
     *
     * DomPDF ne rend qu'imparfaitement le SVG, et une carte partie à
     * l'impression avec un code approximatif ne se corrige pas par un
     * déploiement. Le fond de la variante est CUIT dans l'image plutôt que
     * laissé transparent, la transparence PNG étant l'autre faiblesse connue
     * de ce moteur.
     */
    public function cartePng(Profile $profile): string
    {
        $variante = $profile->variante();

        return $this->cache(
            $profile,
            'carte-'.$variante->name.'.png',
            fn () => $this->rasteriserUrl($this->url($profile), $variante->encre(), $variante->fond())
        );
    }

    /**
     * Chemins relatifs des fichiers, sur le disque public.
     *
     * ═══════════════════════════════════════════════════════════════════
     * LE NOM PORTE UNE EMPREINTE DE L'ADRESSE ENCODÉE
     * ═══════════════════════════════════════════════════════════════════
     * Il valait « qr/{slug}.svg », sans aucune trace de l'URL contenue dans
     * le code. Cette omission était une bombe à retardement :
     *
     * Un QR n'est régénéré qu'au changement de slug ou de variante. Si APP_URL
     * change — correction d'une faute, achat du domaine, migration
     * d'hébergeur — les fichiers en cache CONSERVENT L'ANCIENNE ADRESSE.
     * Ils continuent d'être servis, téléchargés, intégrés au PDF, et
     * IMPRIMÉS SUR DES CARTES PVC. Rien ne signale l'écart : le code est
     * valide, il mène simplement au mauvais endroit.
     *
     * Le défaut ne se serait pas vu à l'écran, ni dans un test, ni à la
     * relecture. Il se serait constaté sur cinq cents cartes déjà livrées.
     *
     * Avec l'empreinte, un changement d'adresse produit un nom de fichier
     * différent : le code est régénéré au premier accès, sans intervention et
     * sans qu'il faille y penser. C'est la même protection que celle du QR de
     * la plateforme — elle manquait ici.
     */
    public function path(Profile $profile, string $format): string
    {
        return $this->dossier($profile)."/{$this->empreinteAdresse()}.{$format}";
    }

    /**
     * UN DOSSIER PAR PROFIL — et ce n'est pas du rangement.
     *
     * Les fichiers vivaient à plat dans « qr/ ». Pour supprimer ceux d'un
     * profil sans connaître leur empreinte d'adresse, il fallait LISTER TOUT
     * LE DOSSIER puis filtrer sur le préfixe.
     *
     * Le coût est passé inaperçu sur une base neuve et a explosé ensuite : le
     * dossier local contenait 20 508 fichiers, et `forget()` est appelé à
     * CHAQUE création de profil par l'observateur. Créer soixante profils
     * revenait donc à parcourir un million de noms de fichiers. La suite de
     * tests est passée de 250 à 588 secondes — un comportement quadratique
     * introduit par une ligne qui semblait anodine.
     *
     * Avec un dossier par profil, la suppression est UNE opération, quel que
     * soit le nombre de clients. Le collage entre slugs disparaît par la même
     * occasion : « awa » et « awa-2 » sont deux dossiers, plus deux préfixes
     * qu'il fallait distinguer à la main.
     */
    private function dossier(Profile $profile): string
    {
        return 'qr/'.$profile->slug;
    }

    /**
     * Huit caractères tirés de l'adresse de base du site.
     *
     * On empreinte APP_URL et non l'URL complète du profil : le slug figure
     * déjà dans le nom, et deux profils du même site doivent partager la même
     * empreinte pour que le nettoyage reste lisible.
     */
    private function empreinteAdresse(): string
    {
        return substr(sha1(rtrim((string) config('app.url'), '/')), 0, 8);
    }

    /** URL publique du fichier, pour un <img> ou un téléchargement. */
    public function fileUrl(Profile $profile, string $format): string
    {
        return Storage::disk('public')->url($this->path($profile, $format));
    }

    /**
     * Régénère tous les fichiers du profil. Appelé à la création et à chaque
     * changement de slug ou de variante — les deux seuls moments où le
     * contenu ou l'apparence du QR change réellement.
     */
    public function refresh(Profile $profile): void
    {
        $this->forget($profile);

        $this->svg($profile);
        $this->png($profile);
        $this->carteSvg($profile);
        $this->cartePng($profile);
    }

    /**
     * Efface les fichiers du profil, POUR LES DEUX VARIANTES.
     *
     * On ne se contente pas de la variante courante : cette méthode est
     * appelée quand elle vient de changer, et le fichier à supprimer est
     * justement celui de l'ancienne. Ne nettoyer que la nouvelle laisserait
     * l'ancienne s'accumuler à chaque bascule.
     */
    /**
     * Efface TOUS les fichiers du profil, quelle que soit leur empreinte
     * d'adresse ou leur variante — en une seule opération.
     *
     * Énumérer les formats connus ne suffirait pas : depuis que le nom porte
     * une empreinte de APP_URL, les fichiers produits sous une ancienne
     * adresse ont un nom qu'on ne sait pas reconstruire, et resteraient
     * indéfiniment sur le disque.
     *
     * Lister le dossier pour filtrer sur un préfixe ne convenait pas non plus :
     * voir dossier(), c'est ce qui a fait passer la suite de tests de 250 à
     * 588 secondes.
     */
    public function forget(Profile $profile): void
    {
        Storage::disk('public')->deleteDirectory($this->dossier($profile));
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

    /**
     * Le générateur des fichiers TÉLÉCHARGEABLES — toujours standard.
     *
     * Encre de marque sur fond blanc, quelle que soit la variante de la
     * carte. Ces fichiers partent chez un imprimeur ou dans une signature de
     * courriel ; les colorer selon la variante donnerait, pour la blanche,
     * un code blanc sur blanc.
     */
    private function generator(Profile $profile): Generator
    {
        return (new Generator)
            ->errorCorrection(self::CORRECTION)
            ->margin(self::MARGE)
            // Fond blanc explicite : un QR sur fond transparent devient
            // illisible dès qu'on le pose sur un support coloré.
            ->backgroundColor(255, 255, 255)
            ->color(...$this->rgb(self::ENCRE_MARQUE));
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
    private function rasteriser(Profile $profile): string
    {
        return $this->rasteriserUrl($this->url($profile), self::ENCRE_MARQUE, '#FFFFFF');
    }

    /**
     * Le même rendu, pour une adresse quelconque et un couple de couleurs.
     *
     * Extrait de rasteriser() le 13 août pour servir aussi le QR de la
     * PLATEFORME, qui n'appartient à aucun profil. Une seconde
     * implémentation aurait fini par diverger sur un paramètre — et une
     * divergence de rendu QR ne se constate que sur des cartes imprimées.
     */
    private function rasteriserUrl(string $url, string $encreHex, string $fondHex): string
    {
        $qr = Encoder::encode($url, ErrorCorrectionLevel::H(), 'ISO-8859-1');

        $matrice = $qr->getMatrix();
        $modules = $matrice->getWidth();
        $total = $modules + self::MARGE * 2;

        $echelle = max(1, (int) floor(self::TAILLE_PNG / $total));
        $cote = $total * $echelle;

        $image = imagecreatetruecolor($cote, $cote);

        // La zone de silence prend la couleur du FOND : c'est elle qui permet
        // au lecteur de délimiter le code, elle doit donc contraster avec les
        // modules, pas avec la page.
        imagefill($image, 0, 0, imagecolorallocate($image, ...$this->rgb($fondHex)));
        $encre = imagecolorallocate($image, ...$this->rgb($encreHex));

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
