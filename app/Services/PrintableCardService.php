<?php

namespace App\Services;

use App\Enums\VarianteCarte;
use App\Models\Profile;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Cache;

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

    /**
     * LA VERSION DU GABARIT, inscrite dans la clé de cache.
     *
     * Le PDF est gardé trente jours, sous une clé qui ne dépendait que des
     * champs imprimés. Sans cette version, une refonte de la carte resterait
     * invisible un mois durant pour tous les profils déjà téléchargés — et
     * l'e-mail de paiement joindrait l'ANCIEN fichier, celui qui part chez
     * l'imprimeur. À incrémenter à chaque changement de la vue
     * profile.printable ou des fonds de CardTextureService.
     */
    private const GABARIT = 'teranga-1';

    /**
     * LE PDF EST MIS EN CACHE, ET LA CLÉ PORTE L'EMPREINTE DU PROFIL.
     *
     * ═══════════════════════════════════════════════════════════════════
     * POURQUOI IL NE PEUT PAS RESTER DANS LE CYCLE DE LA REQUÊTE
     * ═══════════════════════════════════════════════════════════════════
     * Composer un PDF de deux pages — deux QR Codes vectoriels, une police
     * embarquée, des tracés au millimètre — occupe un processus PHP pendant
     * tout son calcul. Le plan d'hébergement n'en fait tourner que quelques-
     * uns : trois clients qui téléchargent leur carte en même temps
     * immobilisent la moitié de la capacité du site.
     *
     * Or ce fichier ne change QUE si le profil change. Le recalculer à
     * chaque téléchargement, c'est refaire exactement le même travail.
     *
     * L'EMPREINTE PORTE LES CHAMPS IMPRIMÉS, plus la date de modification :
     * un profil retouché produit une clé différente, donc un PDF neuf, sans
     * qu'aucune purge n'ait à être pensée. Deux cartes déjà imprimées ne
     * peuvent pas diverger d'un fichier servi depuis le cache, puisque le
     * cache est invalidé par le contenu lui-même.
     */
    public function render(Profile $profile): string
    {
        return Cache::remember(
            $this->cleDeCache($profile),
            now()->addDays(30),
            fn () => $this->composer($profile)
        );
    }

    /**
     * La clé : le slug, la variante, et l'empreinte des champs imprimés.
     *
     * On n'utilise PAS updated_at seul. Il bouge à chaque écriture, y
     * compris pour un champ qui ne figure pas sur la carte — un compteur de
     * vues, une préférence — et régénérerait le PDF sans raison.
     */
    private function cleDeCache(Profile $profile): string
    {
        $empreinte = md5(implode('|', [
            self::GABARIT,
            $profile->first_name,
            $profile->last_name,
            $profile->job_title,
            $profile->company,
            $profile->slug,
            $profile->primary_color,
        ]));

        return "carte-pdf:{$profile->id}:{$empreinte}";
    }

    private function composer(Profile $profile): string
    {
        $variante = $profile->variante();

        /*
         | LE NOM N'EST PLUS MESURÉ ICI.
         |
         | La direction A l'écrit à taille FIXE (1,42 em de carte, comme la
         | maquette), en haut à gauche, et le laisse passer à la ligne dans sa
         | colonne : c'est DomPDF, qui connaît les vraies métriques de la
         | police, qui coupe. L'ancien calcul (NomSurCarte) servait à faire
         | tenir un nom en capitales sur toute la largeur — une composition
         | abandonnée.
         */
        return Pdf::loadView('profile.printable', [
            'profile' => $profile,
            'variante' => $variante,
            'marge' => self::MARGE_MM,

            /*
             | RECTO — le QR du porteur, TOUJOURS SOMBRE SUR CLAIR.
             |
             | png() et non plus cartePng(). cartePng() teintait les modules
             | selon la variante : sur la carte VERTE, il les rendait BLANCS sur
             | vert — un code inversé, hors de la norme ISO/IEC 18004, que
             | certains lecteurs ignorent sans rien dire. png() encode la MÊME
             | adresse (urlEncodee), en encre de marque sur fond blanc, et la
             | vue le pose dans un panneau blanc sur les deux variantes —
             | exactement ce que montre l'écran, qui utilise svg().
             */
            'qrRecto' => $this->enBase64($this->qr->png($profile)),

            // VERSO — le QR de la PLATEFORME, en orientation standard. Il est
            // identique sur toutes les cartes : le cache est global.
            'qrVerso' => $this->enBase64($this->qr->plateformePng()),

            /*
             | Les fonds, que DomPDF ne saurait pas peindre lui-même : claustra
             | et lisière. Le recto prend celui de sa variante ; le verso, face
             | de la plateforme, est TOUJOURS baobab — le fond de la verte.
             */
            'fondRecto' => $this->texture->dataUri($variante),
            'fondVerso' => $this->texture->dataUri(VarianteCarte::Verte),
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
