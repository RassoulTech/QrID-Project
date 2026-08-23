{{--
  LES TROIS PARTIS PRIS DE LA CARTE PUBLIQUE — A, B et C, côte à côte.

  ═══════════════════════════════════════════════════════════════════════
  CE QUI DIFFÈRE, ET CE QUI NE DIFFÈRE PAS
  ═══════════════════════════════════════════════════════════════════════
  SEULE la texture du corps de la carte change. La structure — bandeau,
  médaillon, identité, bloc de coordonnées, grille de six, barre d'actions —
  est identique dans les trois, et elle est validée : elle ne bouge pas.

  Chaque proposition est rendue DEUX FOIS, en thème clair et en thème
  sombre, à la largeur de référence de 375px. Le thème est appliqué par une
  classe posée sur le conteneur : les jetons étant déclarés sur .theme-dark,
  ils se redéclarent pour ce sous-arbre sans toucher au reste de la page.

  Props : profile
--}}
@props(['profile'])

@php
    $propositions = [
        'a' => [
            'titre' => 'A — Trame de modules',
            'texte' => "L'écho du QR Code. Des carrés de deux tailles sur deux grilles de pas différents : "
                ."le décalage empêche l'œil de lire une grille régulière. Un masque radial creuse le centre, "
                ."là où se lisent le nom et les coordonnées, et laisse le motif aux angles.",
        ],
        'b' => [
            'titre' => 'B — Réseau de connexions',
            'texte' => "Le graphe professionnel. Deux compositions distinctes — une en haut à droite, une en bas "
                ."à gauche — plutôt qu'un motif répété : un graphe qui se répète cesse d'être un graphe. "
                ."Tracé en SVG encodé, moins de 800 octets pour les deux.",
        ],
        'c' => [
            'titre' => 'C — Formes organiques',
            'texte' => "La lumière diffuse. Trois masses très larges débordant des bords, qui se comportent comme "
                ."un éclairage derrière le contenu plutôt que comme un motif posé dessus. Aucun filter:blur() — "
                ."un dégradé radial est déjà flou, et le flou logiciel coûte une couche de composition à chaque défilement.",
        ],
    ];
@endphp

<h2 class="section-title" style="margin-top:96px">Carte publique — trois partis pris</h2>

<p class="section-sub" style="max-width:760px;margin-inline:auto">
    Seule la texture du corps change. Structure, couleurs, composants et
    interactions sont identiques dans les trois. Chaque proposition est rendue
    à 375px, en thème clair puis en thème sombre.
</p>

<div class="ds-cartes">
    @foreach ($propositions as $cle => $proposition)
        <section class="ds-carte">
            <header class="ds-carte__entete">
                <h3 class="ds-carte__titre">{{ $proposition['titre'] }}</h3>
                <p class="ds-carte__texte">{{ $proposition['texte'] }}</p>
            </header>

            <div class="ds-carte__paire">
                @foreach (['clair' => '', 'sombre' => 'theme-dark'] as $nomTheme => $classeTheme)
                    <figure class="ds-carte__vue {{ $classeTheme }}">
                        <figcaption class="ds-carte__legende">{{ ucfirst($nomTheme) }} · 375px</figcaption>

                        {{-- Le cadre fixe la largeur de référence : sans lui,
                             la carte s'étirerait à la colonne et on jugerait
                             un rendu que personne ne verra jamais. --}}
                        <div class="ds-carte__cadre">
                            <x-carte-publique :profile="$profile" :variante="$cle" :apercu="true" />
                        </div>
                    </figure>
                @endforeach
            </div>
        </section>
    @endforeach
</div>
