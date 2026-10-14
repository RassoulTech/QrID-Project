<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * <x-nuancier theme="clair" /> — la palette d'un thème, lue dans les jetons.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LES VALEURS NE SONT PAS RECOPIÉES : ELLES SONT LUES
 * ═══════════════════════════════════════════════════════════════════════
 * La planche /design-system affiche chaque couleur avec son code. Écrits à
 * la main dans la vue, ces codes divergeraient de `_tokens.scss` au premier
 * ajustement — et une planche qui ment est pire qu'aucune planche. Le
 * composant lit donc la SOURCE, résout les alias (`$c-page: $sable-100;`),
 * et la pastille elle-même est peinte par la propriété CSS du rôle : ce
 * qu'on voit est ce que le produit sert.
 *
 * Page locale seulement (le contrôleur refuse toute autre environnement).
 */
class Nuancier extends Component
{
    /**
     * Les rôles de chaque thème : [nom, rôle, classe de pastille, variable SCSS].
     *
     * @var array<string, list<array{0:string, 1:string, 2:string, 3:string}>>
     */
    private const ROLES = [
        'clair' => [
            ['Vert baobab', 'Marque, principal', 'marque', 'vert-fonce'],
            ['Vert feuille', 'Liens, accents', 'lien', 'c-lien'],
            ['Menthe', 'Remplissage seul', 'accent', 'vert-accent'],
            ['Latérite', 'Alerte, focus', 'terre', 'c-terre'],
            ['Or', 'Premium (texte : or-700)', 'or', 'c-or'],
            ['Encre', 'Texte', 'texte', 'c-texte'],
            ['Sable', 'Fond de page', 'page', 'c-page'],
            ['Ivoire', 'Surfaces', 'carte', 'c-carte'],
            ['Grès', 'Surface 2, bandes', 'surelev', 'c-surelev'],
            ['Trait', 'Bordures', 'bordure', 'c-bordure'],
            ['Encre douce', 'Texte secondaire', 'texte-2', 'c-texte-2'],
            ['Contour', 'Champs (3:1)', 'champ', 'c-champ'],
        ],
        'sombre' => [
            ['Nuit', 'Fond de page', 'page', 's-page'],
            ['Surface', 'Cartes', 'carte', 's-carte'],
            ['Surface 2', 'Bandes, pistes', 'surelev', 's-surelev'],
            ['Couverture', 'Vert de marque', 'couverture', 's-couverture'],
            ['Menthe claire', 'Principal, liens', 'marque', 'vert-accent-sombre'],
            ['Encre profonde', 'Texte sur menthe', 'sur-marque', 's-texte-inv'],
            ['Brume', 'Texte', 'texte', 's-texte'],
            ['Brume douce', 'Texte secondaire', 'texte-2', 's-texte-2'],
            ['Latérite claire', 'Alerte', 'terre', 's-terre'],
            ['Or clair', 'Premium, focus', 'or', 's-or'],
            ['Trait', 'Bordures', 'bordure', 's-bordure'],
            ['Contour', 'Champs (3:1)', 'champ', 's-champ'],
        ],
    ];

    /** @var list<array{nom:string, role:string, classe:string, hex:string}> */
    public array $nuances = [];

    public function __construct(public string $theme = 'clair')
    {
        $jetons = self::lireJetons();

        foreach (self::ROLES[$theme] ?? [] as [$nom, $role, $classe, $variable]) {
            $this->nuances[] = [
                'nom' => $nom,
                'role' => $role,
                'classe' => $classe,
                'hex' => $jetons[$variable] ?? '—',
            ];
        }
    }

    /**
     * Les variables de couleur de `_tokens.scss`, alias résolus.
     *
     * @return array<string, string>
     */
    public static function lireJetons(): array
    {
        $chemin = resource_path('sass/_tokens.scss');

        if (! is_file($chemin)) {
            return [];
        }

        $source = (string) preg_replace('#//.*$#m', '', (string) file_get_contents($chemin));

        preg_match_all('/^\s*\$([\w-]+)\s*:\s*(#[0-9A-Fa-f]{3,8}|\$[\w-]+)\s*;/m', $source, $m, PREG_SET_ORDER);

        $brut = [];
        foreach ($m as [, $nom, $valeur]) {
            $brut[$nom] = $valeur;
        }

        $resoudre = function (string $valeur, int $profondeur = 0) use (&$resoudre, $brut): ?string {
            if (str_starts_with($valeur, '#')) {
                return strtoupper($valeur);
            }

            $cible = substr($valeur, 1);

            return $profondeur < 8 && isset($brut[$cible])
                ? $resoudre($brut[$cible], $profondeur + 1)
                : null;
        };

        $jetons = [];
        foreach ($brut as $nom => $valeur) {
            if (($hex = $resoudre($valeur)) !== null) {
                $jetons[$nom] = $hex;
            }
        }

        return $jetons;
    }

    public function render(): View
    {
        return view('design-system.nuancier');
    }
}
