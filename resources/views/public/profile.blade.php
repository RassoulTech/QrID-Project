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

     Props : $profile (avec socialLinks), $apercuUrl, $qrSvg, $couvertureUrl
============================================================================ --}}
<x-public-profile-layout
    :title="$profile->full_name"
    :description="$profile->job_title.($profile->company ? ' · '.$profile->company : '')"
    :apercu-url="$apercuUrl ?? null"
>

    <main class="pubc-page">
        {{-- LES DEUX PRÉFÉRENCES DU VISITEUR, ET RIEN D'AUTRE.

             Langue et thème appartiennent au confort de qui LIT la carte, pas
             à l'identité de qui la porte. Elles flottent donc au-dessus de la
             couverture, ensemble : un visiteur qui cherche à changer de langue
             regarde là où il a déjà vu la bascule de thème.

             La page publique suit la langue du VISITEUR, jamais celle du
             propriétaire du profil. Aucun code n'est nécessaire pour cela —
             Langue::courante() ne consulte que le visiteur. Le contenu saisi
             (nom, fonction, entreprise) n'est évidemment jamais traduit. --}}
        <div class="pubc__prefs">
            <x-language-toggle />
            <x-theme-toggle />
        </div>

        <x-carte-publique
            :profile="$profile"
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
