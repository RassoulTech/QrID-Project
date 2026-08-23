{{-- ============================================================================
     LA CARTE PUBLIQUE — la page ouverte après un scan de QR Code.
     ============================================================================

     CE FICHIER N'EST PAS CELUI DE L'APERÇU.

         page publique  →  resources/views/public/profile.blade.php   (ici)
         aperçu client  →  resources/views/profile/preview.blade.php

     Deux vues distinctes, aucune mutualisation. L'aperçu affiche volontairement
     un cadre de smartphone pour simuler le rendu au propriétaire ; cette
     page-ci EST le rendu, et occupe tout l'écran.

     TOUS LES RÉSEAUX SONT DANS LA GRILLE. Aucune icône isolée ailleurs : la
     page en portait une sous l'identité et un lien WhatsApp séparé plus bas,
     ce qui obligeait à chercher à deux endroits ce qui est une seule chose.

     Props : $profile (avec socialLinks), $apercuUrl, $qrSvg, $photoUrl
============================================================================ --}}
<x-public-profile-layout
    :title="$profile->full_name"
    :description="$profile->job_title.($profile->company ? ' · '.$profile->company : '')"
    :apercu-url="$apercuUrl ?? null"
>

    <main class="pubc-page">
        {{-- La bascule de thème flotte hors de la carte : elle appartient au
             confort du visiteur, pas à l'identité du porteur. --}}
        <div class="pubc__theme"><x-theme-toggle /></div>

        {{-- ?texture=b permet de juger un parti pris sur la VRAIE page, et
             pas seulement sur la planche. Strictement local : en production,
             la texture vient de la configuration. --}}
        <x-carte-publique
            :variante="app()->environment('local') ? request()->query('texture') : null"
            :profile="$profile"
            :photo-url="$photoUrl"
            :couverture-url="$couvertureUrl ?? null"
            :qr-svg="$qrSvg ?? null" />

        @if (! empty($qrSvg))
            <div id="qr" class="pubc__qr-plein" role="dialog" aria-label="QR Code">
                <p class="pubc__qr-nom">{{ $profile->full_name }}</p>
                {!! $qrSvg !!}
                <a href="#" class="pubc__qr-fermer">Fermer</a>
            </div>
        @endif
    </main>
</x-public-profile-layout>
