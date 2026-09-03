<?php

namespace App\Services;

use App\Models\Profile;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * L'IMAGE QUI S'AFFICHE QUAND ON PARTAGE UNE CARTE.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI ELLE EXISTE — c'est le produit, pas une décoration
 * ═══════════════════════════════════════════════════════════════════════
 * Le geste central de QrID est de coller son lien dans WhatsApp. Sans balise
 * og:image, WhatsApp rend un aperçu MINUSCULE : une ligne de titre grise et
 * rien d'autre. Avec, il rend une grande vignette qu'on remarque dans une
 * conversation.
 *
 * L'écart n'est pas cosmétique : c'est la différence entre un lien qu'on
 * ouvre et un lien qu'on fait défiler. Le produit tout entier repose sur ce
 * partage, et il se faisait jusqu'ici sans image.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI PAS SIMPLEMENT LA PHOTO DU PROFIL
 * ═══════════════════════════════════════════════════════════════════════
 * Trois raisons, chacune suffisante :
 *
 *   · elle est CARRÉE et l'aperçu est en 1,91:1 — WhatsApp recadrerait au
 *     centre, coupant le haut du crâne et le menton ;
 *   · elle est FACULTATIVE : la moitié des cartes n'en ont pas, et ces
 *     partages-là retomberaient sans image ;
 *   · elle ne porte NI LE NOM NI LA MARQUE. Un visage seul dans une
 *     conversation n'apprend rien à celui qui reçoit le lien.
 *
 * L'image générée porte le nom, la fonction, la marque, et la photo quand
 * elle existe. Elle existe TOUJOURS.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LA POLICE VIENT DE DOMPDF
 * ═══════════════════════════════════════════════════════════════════════
 * DejaVu Sans, déjà présente comme dépendance et déjà employée par le PDF de
 * la carte. Trois bénéfices : aucun paquet de plus, une typographie identique
 * entre l'aperçu et l'imprimé, et une couverture complète des accents
 * français — ce que les polices bitmap de GD ne savent pas faire.
 *
 * Si elle venait à manquer, l'image est produite SANS TEXTE plutôt que pas du
 * tout : un aperçu aux couleurs de la marque vaut mieux qu'un lien nu.
 */
class SharePreviewService
{
    /** Format imposé par les réseaux : 1,91:1. WhatsApp, Facebook, LinkedIn. */
    private const LARGEUR = 1200;

    private const HAUTEUR = 630;

    private const MARGE = 80;

    /** Côté de la photo, en pixels. */
    private const PHOTO = 220;

    /**
     * L'adresse ABSOLUE de l'image.
     *
     * Construite avec asset(), et NON avec Storage::disk()->url().
     *
     * Deux raisons, et la seconde est celle qui a fait échouer la première
     * version : la méthode du disque dépend d'une clé `url` dans la
     * configuration, absente dès qu'on remplace le disque — ce que fait tout
     * test un peu sérieux. L'appel levait alors une exception, avalée par le
     * garde-fou de la page publique, et la balise disparaissait EN SILENCE.
     *
     * asset() dérive de APP_URL et rend toujours une adresse absolue, ce
     * qu'exigent les robots des messageries : ils ne résolvent aucun chemin
     * relatif.
     */
    public function url(Profile $profile): string
    {
        return asset('storage/'.$this->chemin($profile));
    }

    /**
     * Le chemin du fichier, avec la même protection que les QR Codes.
     *
     * L'empreinte porte sur ce que l'image AFFICHE — nom, fonction,
     * entreprise, photo. Sans elle, un client qui corrige sa fonction
     * garderait l'ancienne dans tous ses partages, indéfiniment : l'image
     * n'est régénérée que si son nom de fichier change.
     */
    public function chemin(Profile $profile): string
    {
        return 'apercus/'.$profile->slug.'/'.$this->empreinte($profile).'.png';
    }

    /** L'image en octets. Écrite une fois, relue ensuite. */
    public function png(Profile $profile): string
    {
        $chemin = $this->chemin($profile);
        $disque = Storage::disk('public');

        if (! $disque->exists($chemin)) {
            $disque->put($chemin, $this->peindre($profile));
        }

        return (string) $disque->get($chemin);
    }

    /**
     * Efface les aperçus du profil — tous, quelle que soit leur empreinte.
     *
     * deleteDirectory et non une liste de noms : les images produites sous
     * d'anciennes valeurs ont un nom qu'on ne sait pas reconstruire. C'est la
     * même leçon que sur les QR Codes, où lister le dossier commun avait rendu
     * la suppression quadratique.
     */
    public function forget(Profile $profile): void
    {
        Storage::disk('public')->deleteDirectory('apercus/'.$profile->slug);
    }

    // -----------------------------------------------------------------------

    private function empreinte(Profile $profile): string
    {
        return substr(sha1(implode('|', [
            $profile->full_name,
            $profile->job_title,
            $profile->company,
            $profile->cover_path,
            config('app.name'),
        ])), 0, 8);
    }

    private function peindre(Profile $profile): string
    {
        $image = $this->fond();

        $blanc = imagecolorallocate($image, 255, 255, 255);
        $accent = imagecolorallocate($image, 30, 158, 122);  // #1E9E7A

        $aPhoto = $this->photo($image, $profile);

        // Le texte s'arrête avant la photo quand il y en a une, sinon il
        // occupe toute la largeur utile.
        $largeurTexte = self::LARGEUR - 2 * self::MARGE
            - ($aPhoto ? self::PHOTO + 60 : 0);

        $this->texte($image, $profile, $largeurTexte, $blanc, $accent);

        $this->pied($image, $blanc, $accent);

        ob_start();
        imagepng($image, null, 6);
        $binaire = (string) ob_get_clean();

        imagedestroy($image);

        return $binaire;
    }

    /**
     * LE FOND — vert de la marque et deux halos diffus.
     *
     * ═══════════════════════════════════════════════════════════════════
     * PEINT PETIT, PUIS AGRANDI — et c'est l'agrandissement qui fait tout
     * ═══════════════════════════════════════════════════════════════════
     * La première version peignait les halos directement en 1200 × 630, par
     * empilement d'ellipses d'opacité décroissante. Le résultat portait des
     * ANNEAUX CONCENTRIQUES visibles : chaque ellipse laissait son palier, et
     * l'œil les lisait comme des cercles au lieu d'un halo.
     *
     * Ici les formes sont peintes sur une image minuscule (160 × 84), puis
     * l'image est agrandie par rééchantillonnage bicubique. L'interpolation
     * transforme les paliers en dégradé continu, sans une seule passe de flou.
     *
     * La démarche inverse — peindre grand puis flouter — coûterait cent fois
     * plus et rendrait moins bien, le flou gaussien de GD étant à la fois
     * faible et cher. C'est la même technique que le fond du verso de la
     * carte, pour la même raison.
     */
    private function fond(): \GdImage
    {
        $l = 160;
        $h = 84;   // 160 / 1,91

        $petite = imagecreatetruecolor($l, $h);

        imagefill($petite, 0, 0, imagecolorallocate($petite, 11, 59, 46));  // #0B3B2E

        // Sans mélange alpha, chaque ellipse écraserait la précédente et il ne
        // resterait que la dernière forme dessinée.
        imagealphablending($petite, true);

        foreach ([[0.12, 0.78, 0.42], [0.88, 0.16, 0.34]] as [$x, $y, $r]) {
            $cx = (int) round($x * $l);
            $cy = (int) round($y * $h);
            $rayon = (int) round($r * $l);

            for ($i = 20; $i > 0; $i--) {
                $part = $i / 20;

                // 127 = transparent pour GD, 0 = opaque : l'échelle est
                // inversée par rapport à toutes les autres du projet.
                // La chute au CARRÉ évite l'anneau fantôme que laisse une
                // décroissance linéaire au bord de la forme.
                $alpha = (int) round(127 - 127 * 0.12 * (1 - $part) ** 2);

                $couleur = imagecolorallocatealpha($petite, 30, 158, 122, min(127, max(0, $alpha)));

                imagefilledellipse($petite, $cx, $cy, (int) round($rayon * 2 * $part), (int) round($rayon * 2 * $part), $couleur);
            }
        }

        $grande = imagecreatetruecolor(self::LARGEUR, self::HAUTEUR);

        imagecopyresampled($grande, $petite, 0, 0, 0, 0, self::LARGEUR, self::HAUTEUR, $l, $h);

        imagedestroy($petite);

        return $grande;
    }

    /** La photo, en rond, à droite. Rend false s'il n'y en a pas. */
    private function photo(\GdImage $image, Profile $profile): bool
    {
        // couvertureBinaire() : le disque s'il l'a, la base sinon. Le produit
        // n'a qu'UNE image, et c'est elle. Voir Profile.
        $octets = $profile->couvertureBinaire();

        if ($octets === null) {
            return false;
        }

        try {
            $source = @imagecreatefromstring($octets);
        } catch (Throwable) {
            return false;
        }

        if ($source === false) {
            return false;
        }

        $x = self::LARGEUR - self::MARGE - self::PHOTO;
        $y = (int) ((self::HAUTEUR - self::PHOTO) / 2);

        // Disque blanc légèrement plus grand : il fait office de liseré et
        // détache la photo du fond vert.
        imagefilledellipse(
            $image,
            $x + self::PHOTO / 2,
            $y + self::PHOTO / 2,
            self::PHOTO + 10,
            self::PHOTO + 10,
            imagecolorallocate($image, 255, 255, 255)
        );

        /*
         | LE MASQUE ROND EST FAIT À LA MAIN, pixel par pixel.
         |
         | GD ne sait pas découper une image en cercle. On copie donc la photo
         | redimensionnée dans un calque, puis on ne reporte que les pixels
         | situés dans le disque. C'est cher en apparence — 220 × 220 = 48 400
         | tests — mais l'opération n'a lieu qu'une fois par profil, à la
         | génération, jamais à l'affichage.
         */
        $carre = imagecreatetruecolor(self::PHOTO, self::PHOTO);

        imagecopyresampled(
            $carre, $source,
            0, 0, 0, 0,
            self::PHOTO, self::PHOTO,
            imagesx($source), imagesy($source)
        );

        $rayon = self::PHOTO / 2;

        for ($py = 0; $py < self::PHOTO; $py++) {
            for ($px = 0; $px < self::PHOTO; $px++) {
                $dx = $px - $rayon;
                $dy = $py - $rayon;

                if ($dx * $dx + $dy * $dy > $rayon * $rayon) {
                    continue;
                }

                imagesetpixel($image, $x + $px, $y + $py, imagecolorat($carre, $px, $py));
            }
        }

        imagedestroy($carre);
        imagedestroy($source);

        return true;
    }

    /** Nom, fonction, entreprise — alignés à gauche, centrés verticalement. */
    private function texte(\GdImage $image, Profile $profile, int $largeur, int $blanc, int $accent): void
    {
        $police = $this->police();
        $policeGrasse = $this->police(true);

        if ($police === null || $policeGrasse === null) {
            return;   // sans police, l'image reste : voir l'en-tête de classe
        }

        $nom = mb_strtoupper($profile->full_name);

        /*
         | La taille du nom suit sa longueur, comme sur la carte. Un nom court
         | perdu au milieu d'une image, ou un nom long qui déborde, sont les
         | deux défauts qu'une taille fixe garantit.
         */
        $taille = (int) round(min(72, max(34, $largeur / (mb_strlen($nom) * 0.58))));

        $y = 250;

        imagettftext($image, $taille, 0, self::MARGE, $y, $blanc, $policeGrasse, $nom);

        if ($profile->job_title) {
            $y += 62;
            imagettftext($image, 30, 0, self::MARGE, $y, $accent, $police, $profile->job_title);
        }

        if ($profile->company) {
            $y += 46;
            imagettftext($image, 25, 0, self::MARGE, $y, $blanc, $police, $profile->company);
        }
    }

    /** La marque en bas à gauche, l'invitation à droite. */
    private function pied(\GdImage $image, int $blanc, int $accent): void
    {
        $police = $this->police(true);

        if ($police === null) {
            return;
        }

        $y = self::HAUTEUR - self::MARGE + 10;

        imagettftext($image, 26, 0, self::MARGE, $y, $blanc, $police, config('app.name'));

        $mention = mb_strtoupper((string) config('landing.brand.website'));

        // Mesuré puis positionné : une largeur devinée décalerait le texte à
        // chaque changement de nom de domaine.
        $boite = imagettfbbox(20, 0, $police, $mention);
        $largeurMention = abs($boite[4] - $boite[0]);

        imagettftext(
            $image, 20, 0,
            self::LARGEUR - self::MARGE - $largeurMention, $y,
            $accent, $police, $mention
        );
    }

    /**
     * Le fichier de police, ou null.
     *
     * DejaVu Sans vient de DomPDF, déjà dans les dépendances et déjà employée
     * par le PDF de la carte. On ne suppose pas sa présence : une mise à jour
     * du paquet pourrait la déplacer, et une image sans texte vaut mieux
     * qu'une exception au milieu d'une page publique.
     */
    private function police(bool $grasse = false): ?string
    {
        $nom = $grasse ? 'DejaVuSans-Bold.ttf' : 'DejaVuSans.ttf';

        $chemin = base_path('vendor/dompdf/dompdf/lib/fonts/'.$nom);

        return is_file($chemin) && function_exists('imagettftext') ? $chemin : null;
    }
}
