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

        {{-- ═══════════════ FEUILLE DE PARTAGE ═══════════════
             Elle ne contenait QUE le QR Code. Or faire scanner un code suppose
             que l'autre personne soit en face de vous : c'est le cas d'usage
             le plus étroit. Le geste le plus fréquent est d'envoyer le lien
             dans une conversation.

             Trois moyens, du plus direct au plus spécifique :

               1. LE PARTAGE NATIF — la feuille du système, avec toutes les
                  applications installées. Révélé par partage.js seulement si
                  `navigator.share` existe : un bouton qui ne ferait rien sur
                  un navigateur de bureau serait pire que pas de bouton.
               2. COPIER LE LIEN — pour tout le reste. L'adresse est écrite en
                  clair juste en dessous : sans JavaScript, elle reste
                  sélectionnable à la main.
               3. LE QR CODE — inchangé, pour le face-à-face.

             LE MÉCANISME RESTE `:target`. La feuille s'ouvre sans une ligne de
             JavaScript, comme avant. Le script n'ajoute que le bouton natif et
             la copie en un geste. --}}
        <div id="partage" class="pubc__partage" role="dialog"
             aria-label="{{ __('card.publique.partage_titre') }}">

            <div class="pubc__partage-panneau">
                <p class="pubc__partage-titre">{{ __('card.publique.partage_titre') }}</p>

                {{-- Révélé par partage.js quand la feuille système existe. --}}
                <button type="button" class="pubc__partage-action" data-partage-natif hidden
                        data-titre="{{ $profile->full_name }}"
                        data-url="{{ url()->current() }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/>
                        <path d="M16 6l-4-4-4 4M12 2v14"/>
                    </svg>
                    <span>
                        <span class="pubc__partage-nom">{{ __('card.publique.partage_natif') }}</span>
                        <span class="pubc__partage-aide">{{ __('card.publique.partage_natif_aide') }}</span>
                    </span>
                </button>

                <button type="button" class="pubc__partage-action" data-partage-copier
                        data-url="{{ url()->current() }}"
                        data-fait="{{ __('card.publique.lien_copie') }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="9" y="9" width="13" height="13" rx="2"/>
                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                    </svg>
                    <span>
                        <span class="pubc__partage-nom" data-partage-libelle>{{ __('card.publique.copier_lien') }}</span>
                        {{-- L'ADRESSE EN CLAIR : c'est elle qui rend la copie
                             possible à la main quand le script est absent. --}}
                        <span class="pubc__partage-url">{{ url()->current() }}</span>
                    </span>
                </button>

                @if (! empty($qrSvg))
                    <div class="pubc__partage-qr">
                        <p class="pubc__partage-aide">{{ __('card.publique.qr_aide') }}</p>
                        {!! $qrSvg !!}
                    </div>
                @endif

                {{-- LE LIEN DE FERMETURE NE VAUT PLUS « # ».
                     `#` était le seul lien mort du produit — il a l'air
                     cliquable et ne mène nulle part. Renvoyer vers la carte
                     ferme la feuille ET remet la page où elle était. --}}
                <a href="#carte" class="pubc__partage-fermer">{{ __('card.publique.fermer') }}</a>
            </div>
        </div>
    </main>
</x-public-profile-layout>
