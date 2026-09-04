{{--
  x-phone — un téléphone qui montre LA PAGE PUBLIQUE, à l'échelle.

  <x-phone :profile="$p" />                grande taille (hero, 280px)
  <x-phone :profile="$p" size="sm" />      section sombre (240px)
  <x-phone :profile="$p" :animate="false" />

  ═══════════════════════════════════════════════════════════════════════════
  IL NE REDESSINE PLUS LA CARTE : IL LA RÉDUIT
  ═══════════════════════════════════════════════════════════════════════════
  Ce fichier portait une COPIE À LA MAIN de la page publique — sa propre
  série de classes `.phc`, ses propres balises, ses propres proportions. Son
  commentaire affirmait suivre `public/profile.blade.php` « bloc pour bloc ».

  Il ne le suivait plus. La page publique a supprimé le médaillon rond et posé
  l'identité DANS une image unique en pleine largeur ; la maquette montrait
  encore un portrait rond sur un bandeau vert, avec le nom à côté. Deux
  compositions opposées, et c'est celle qui ne existe pas que voyait le
  visiteur avant de s'inscrire.

  Une copie ne se maintient pas. Elle diverge à la première correction faite
  d'un seul côté, sans erreur, sans test rouge — et c'est toujours la vitrine
  qui reste en arrière, parce qu'on corrige la page qu'on utilise.

  LA MAQUETTE REND DONC `x-carte-publique`, LE COMPOSANT LUI-MÊME.

  Le même que sert /p/{slug}. Le même que montre /design-system. Réduit par
  une transformation d'échelle, ce qui préserve EXACTEMENT les proportions au
  lieu de les réinterpréter en plus petit. Ce qui change sur la page publique
  change ici, sans que personne ait à y penser.

  ═══════════════════════════════════════════════════════════════════════════
  POURQUOI UNE ÉCHELLE ET NON DES TAILLES RÉDUITES
  ═══════════════════════════════════════════════════════════════════════════
  Redéfinir les tailles en plus petit, c'est réécrire la mise en page — donc
  reproduire, en CSS cette fois, la copie qu'on vient de supprimer.

  La carte est rendue à la largeur d'un vrai téléphone (375px), puis réduite.
  Une seule valeur gouverne le rapport, et rien d'autre n'est réinterprété :
  la carte de la vitrine EST la carte du produit, vue de plus loin.

  ═══════════════════════════════════════════════════════════════════════════
  ELLE OCCUPE TOUT L'ÉCRAN
  ═══════════════════════════════════════════════════════════════════════════
  L'ancienne maquette posait une carte détachée sur une page grise, avec une
  ligne de texte sous elle pour combler le tiers d'écran resté vide. La page
  publique, elle, occupe l'appareil entier. La maquette aussi, désormais.

  Props :
    profile     le porteur
    couverture  l'adresse de l'image de couverture, ou null
    size        lg | sm
    animate     l'entrée animée
--}}
@props([
    'profile',
    'couverture' => null,
    'size' => 'lg',
    'animate' => true,
])

@php
    /*
     | LA COUVERTURE PAR DÉFAUT EST CELLE DU PORTEUR.
     |
     | C'est ce qu'attend l'aperçu client (/profil/apercu), qui montre à
     | quelqu'un SA carte : elle doit porter SON image, pas une illustration.
     |
     | Le profil de démonstration de la vitrine, lui, n'existe pas en base et
     | n'a donc pas de couverture — les sections de la landing lui en passent
     | une, dessinée. Voir public/images/couverture-demo.svg.
     */
    $couvertureUrl = $couverture
        ?? (filled($profile->cover_path) ? \Illuminate\Support\Facades\Storage::url($profile->cover_path) : null);
@endphp

<div {{ $attributes->merge([
        'class' => 'phone phone--'.$size.($animate ? ' phone--enter' : ''),
    ]) }}>

    <span class="phone__vol phone__vol--up" aria-hidden="true"></span>
    <span class="phone__vol phone__vol--down" aria-hidden="true"></span>
    <span class="phone__side" aria-hidden="true"></span>
    <span class="phone__gloss" aria-hidden="true"></span>

    <div class="phone__screen">
        <span class="phone__notch" aria-hidden="true"></span>

        {{-- Barre d'état --}}
        <div class="phone__status" aria-hidden="true">
            <span class="phone__time">9:41</span>
            <span class="phone__icons">
                <svg width="12" height="9" viewBox="0 0 16 12" fill="currentColor">
                    <rect x="0" y="8" width="3" height="4" rx="1"/>
                    <rect x="4" y="5" width="3" height="7" rx="1"/>
                    <rect x="8" y="2" width="3" height="10" rx="1"/>
                    <rect x="12" y="0" width="3" height="12" rx="1" opacity=".4"/>
                </svg>
                <svg width="11" height="9" viewBox="0 0 16 12" fill="currentColor">
                    <path d="M8 10.5a1 1 0 1 0 0-2 1 1 0 0 0 0 2M5.5 7.2a3.5 3.5 0 0 1 5 0l-.9.9a2.2 2.2 0 0 0-3.2 0zM3 4.6a7 7 0 0 1 10 0l-.9.9a5.7 5.7 0 0 0-8.2 0z"/>
                </svg>
                <svg width="16" height="9" viewBox="0 0 22 12" fill="none">
                    <rect x=".5" y=".5" width="17" height="11" rx="3" stroke="currentColor" opacity=".5"/>
                    <rect x="2" y="2" width="12" height="8" rx="1.5" fill="currentColor"/>
                    <path d="M19 4v4a2 2 0 0 0 0-4" fill="currentColor" opacity=".5"/>
                </svg>
            </span>
        </div>

        {{-- ═══════════════ LA PAGE PUBLIQUE, RÉDUITE ═══════════════

             `inert` retire tout ce bloc du parcours de tabulation et du
             pointeur. C'est une IMAGE de la carte, pas la carte : sans lui, la
             page d'accueil offrirait au clavier une douzaine de liens
             invisibles à l'œil, et un visiteur qui tabule se retrouverait
             piégé dans une maquette.

             `aria-hidden` va avec : un lecteur d'écran annoncerait sinon un
             deuxième « Awa Ndiaye, Architecte » sans rien qui explique
             pourquoi la page contient deux fois la même personne. La landing
             décrit la maquette dans son propre texte. --}}
        <div class="phone__vue" inert aria-hidden="true">
            <div class="phone__page">
                <x-carte-publique
                    :profile="$profile"
                    :couverture-url="$couvertureUrl"
                    apercu />
            </div>
        </div>
    </div>
</div>
