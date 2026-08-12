{{--
  ICÔNES DU PRODUIT — déclarations communes à TOUS les gabarits.

  Ce partial existe parce que quatre gabarits écrivent leur propre <head> :
  la coque connectée, la landing, le profil public et les pages d'erreur.
  Recopier six balises dans chacun garantissait qu'une page finirait sans
  icône — et ce serait précisément la landing ou le profil public, les deux
  pages qu'un visiteur découvre en premier.

  L'ORDRE COMPTE. Le navigateur retient la PREMIÈRE déclaration qu'il sait
  lire. Le SVG passe donc devant : il reste net sur un écran à haute densité
  comme dans un onglet épinglé, et pèse moins qu'un PNG de 32 pixels. Ceux
  qui l'ignorent tombent sur le .ico juste après.

  Les chemins passent par asset() plutôt qu'écrits en dur : ils suivent ainsi
  le schéma réel de la requête — ce qui, derrière le proxy de Render, n'allait
  pas de soi.
--}}
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
<link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
<link rel="icon" type="image/png" href="{{ asset('favicon-32.png') }}" sizes="32x32">
<link rel="icon" type="image/png" href="{{ asset('favicon-16.png') }}" sizes="16x16">

{{-- iOS n'utilise ni le SVG ni le .ico pour « Ajouter à l'écran d'accueil ».
     Il lui faut ce PNG précis, qu'il recadre lui-même en y appliquant son
     propre arrondi — d'où une image à fond plein, sans coins arrondis. --}}
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
