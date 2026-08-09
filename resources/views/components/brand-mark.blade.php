{{--
  x-brand-mark — le logo de la plateforme, en SVG.

      <x-brand-mark />
      <x-brand-mark class="pvc__logo" />

  PROVISOIRE, et il faut le savoir : ce dessin est une construction — un
  module de QR Code stylisé — et non un logo fourni. Il tient la place jusqu'à
  ce qu'un fichier définitif soit livré. Le jour venu, seul ce fichier change
  et le logo suit partout : carte, PDF, en-têtes.

  Sans texte : le nom de la marque est porté séparément, pour que le logo
  puisse être posé seul là où le nom domine déjà (verso de la carte).
--}}
<svg {{ $attributes->merge(['viewBox' => '0 0 32 32', 'fill' => 'none', 'aria-hidden' => 'true']) }}
     xmlns="http://www.w3.org/2000/svg">
    <rect x="2"  y="2"  width="11" height="11" rx="2.4" stroke="currentColor" stroke-width="2.4"/>
    <rect x="19" y="2"  width="11" height="11" rx="2.4" stroke="currentColor" stroke-width="2.4"/>
    <rect x="2"  y="19" width="11" height="11" rx="2.4" stroke="currentColor" stroke-width="2.4"/>

    <rect x="19" y="19" width="4.6" height="4.6" rx="1.2" fill="currentColor"/>
    <rect x="25.4" y="19" width="4.6" height="4.6" rx="1.2" fill="currentColor"/>
    <rect x="19" y="25.4" width="4.6" height="4.6" rx="1.2" fill="currentColor"/>
    <rect x="25.4" y="25.4" width="4.6" height="4.6" rx="1.2" fill="currentColor"/>
</svg>
