{{--
  LA CARTE PUBLIQUE — la référence, dans les deux thèmes.

  ═══════════════════════════════════════════════════════════════════════
  CETTE PLANCHE A SERVI À CHOISIR, ELLE SERT MAINTENANT À VÉRIFIER
  ═══════════════════════════════════════════════════════════════════════
  Elle portait trois propositions de texture côte à côte. Le choix est fait :
  elle ne montre plus qu'une carte, la vraie, à la largeur de référence de
  375px et dans les deux thèmes.

  C'est EXACTEMENT le composant servi sur /p/{slug} — pas une copie. Ce
  qu'on juge ici est ce que le visiteur reçoit.

  Props : profile
--}}
@props(['profile'])

<h2 class="section-title" style="margin-top:96px">Carte publique</h2>

<p class="section-sub" style="max-width:680px;margin-inline:auto">
    Le composant tel qu'il est servi après un scan, à 375px. Le profil est
    rendu sans photo ni bannière : ce sont les replis qu'il faut pouvoir
    juger, puisque ce sont eux que verra tout client qui n'a rien téléversé.
</p>

<div class="ds-carte__paire" style="justify-content:center;margin-top:40px">
    @foreach (['clair' => '', 'sombre' => 'theme-dark'] as $nomTheme => $classeTheme)
        <figure class="ds-carte__vue {{ $classeTheme }}">
            <figcaption class="ds-carte__legende">{{ ucfirst($nomTheme) }} · 375px</figcaption>

            {{-- Le cadre fixe la largeur de référence : sans lui, la carte
                 s'étirerait à la page et l'on jugerait un rendu que personne
                 ne verra jamais. --}}
            <div class="ds-carte__cadre">
                <x-carte-publique :profile="$profile" :apercu="true" />
            </div>
        </figure>
    @endforeach
</div>
