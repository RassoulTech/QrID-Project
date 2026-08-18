{{-- ============================================================================
     LA CARTE PUBLIQUE — la page ouverte après un scan de QR Code.
     ============================================================================

     ELLE N'EST PLUS DANS UN CADRE DE TÉLÉPHONE.

     Cette page appelait <x-phone>, le composant de maquette du site vitrine.
     Sur un téléphone, le visiteur voyait donc un téléphone DESSINÉ dans son
     téléphone — avec, en dessous, une seconde série des mêmes boutons, parce
     que ceux du cadre ne sont pas cliquables. Le commentaire d'origine
     l'assumait : « le téléphone est une mise en scène, ces liens-ci sont ceux
     qu'on touche. » C'était l'erreur de conception, pas un défaut de CSS.

     Le cadre reste là où il a un sens — la landing, le design-system, et
     l'aperçu du tableau de bord, où il simule ce rendu pour le propriétaire.

     RIEN NE DÉBORDE : ni un nom long, ni une adresse e-mail sans espace.
     AUCUN JAVASCRIPT : le QR plein écran s'ouvre par :target.

     Props : $profile (chargé avec socialLinks), $apercuUrl, $qrSvg
============================================================================ --}}
<x-public-profile-layout
    :title="$profile->full_name"
    :description="$profile->job_title.($profile->company ? ' · '.$profile->company : '')"
    :apercu-url="$apercuUrl ?? null"
>
    @php
        /*
         | LES INITIALES, ET JAMAIS LE NOM COMPLET.
         |
         | La solution de repli écrivait le nom entier dans le cercle : dès que
         | la photo ne chargeait pas, « Mouhamed Dione » débordait de tous les
         | côtés. Invisible en test — la photo est en cache — et bien visible
         | en 3G, c'est-à-dire chez une grande partie des visiteurs.
         */
        $initiales = mb_strtoupper(
            mb_substr((string) $profile->first_name, 0, 1).mb_substr((string) $profile->last_name, 0, 1)
        );

        $initiales = $initiales !== ''
            ? $initiales
            : mb_strtoupper(mb_substr($profile->full_name, 0, 1));
    @endphp

    <div class="carte">
        <div class="carte__inner">

            {{-- ═══════════════════ EN-TÊTE ═══════════════════ --}}
            <header class="carte__tete">
                @if ($profile->photo_path)
                    <img src="{{ Storage::url($profile->photo_path) }}"
                         alt="{{ $profile->full_name }}"
                         class="carte__photo" width="112" height="112"
                         decoding="async">
                @else
                    <span class="carte__initiales" aria-hidden="true">{{ $initiales }}</span>
                @endif

                <h1 class="carte__nom">{{ $profile->full_name }}</h1>

                @if ($profile->job_title)
                    <p class="carte__role">{{ $profile->job_title }}</p>
                @endif

                @if ($profile->company)
                    <p class="carte__entreprise">{{ $profile->company }}</p>
                @endif
            </header>

            {{-- ═══════════════ ACTIONS RAPIDES ═══════════════
                 Une seule série, et seulement ce qui existe : un bouton
                 « Appeler » sans numéro derrière est une déception à
                 retardement. --}}
            <nav class="carte__actions" aria-label="Actions rapides">
                @if ($profile->phone)
                    <a href="{{ $profile->tel_href }}" class="carte__action">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.5 2.8.6a2 2 0 0 1 1.7 2z"/>
                        </svg>
                        <span>Appeler</span>
                    </a>
                @endif

                @if ($profile->whatsapp_href)
                    <a href="{{ $profile->whatsapp_href }}" class="carte__action"
                       target="_blank" rel="noopener">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 11.5a8.4 8.4 0 0 1-9 8.4 8.5 8.5 0 0 1-3.8-.9L3 21l1.9-5.1A8.4 8.4 0 0 1 12 3a8.4 8.4 0 0 1 9 8.5z"/>
                        </svg>
                        <span>WhatsApp</span>
                    </a>
                @endif

                @if ($profile->public_email)
                    <a href="mailto:{{ $profile->public_email }}" class="carte__action">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="2" y="4" width="20" height="16" rx="2"/>
                            <path d="m2 7 10 6 10-6"/>
                        </svg>
                        <span>E-mail</span>
                    </a>
                @endif

                @if ($profile->website)
                    <a href="{{ $profile->website }}" class="carte__action"
                       target="_blank" rel="noopener">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"/>
                            <path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"/>
                        </svg>
                        <span>Site</span>
                    </a>
                @endif
            </nav>

            {{-- ═══════════════ COORDONNÉES ═══════════════ --}}
            <ul class="carte__infos">
                @if ($profile->phone)
                    <li>
                        <a href="{{ $profile->tel_href }}" class="carte__info">
                            <span class="carte__info-icone" aria-hidden="true">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.5 2.8.6a2 2 0 0 1 1.7 2z"/>
                                </svg>
                            </span>
                            <span>{{ $profile->formatted_phone }}</span>
                        </a>
                    </li>
                @endif

                @if ($profile->public_email)
                    <li>
                        <a href="mailto:{{ $profile->public_email }}" class="carte__info">
                            <span class="carte__info-icone" aria-hidden="true">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="4" width="20" height="16" rx="2"/>
                                    <path d="m2 7 10 6 10-6"/>
                                </svg>
                            </span>
                            <span>{{ $profile->public_email }}</span>
                        </a>
                    </li>
                @endif

                @if ($profile->address)
                    <li>
                        <span class="carte__info">
                            <span class="carte__info-icone" aria-hidden="true">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/>
                                    <circle cx="12" cy="10" r="3"/>
                                </svg>
                            </span>
                            <span>{{ $profile->address }}</span>
                        </span>
                    </li>
                @endif
            </ul>

            {{-- ═══════════════ RÉSEAUX ═══════════════ --}}
            @if ($profile->socialLinks->isNotEmpty())
                <div class="carte__reseaux">
                    @foreach ($profile->socialLinks as $lien)
                        <a href="{{ $lien->url }}" class="carte__reseau"
                           target="_blank" rel="noopener">{{ $lien->platform_label }}</a>
                    @endforeach
                </div>
            @endif

            {{-- ═══════════════ QR DE LA PAGE ═══════════════
                 Pour montrer sa carte à une TROISIÈME personne, sans lui
                 demander de recopier une adresse. Le SVG est posé en ligne :
                 aucune requête de plus sur un réseau lent. --}}
            @if (! empty($qrSvg))
                <a href="#qr" class="carte__qr-lien">Afficher le QR Code de cette carte</a>

                <div id="qr" class="qr-plein" role="dialog" aria-label="QR Code">
                    <p class="qr-plein__nom">{{ $profile->full_name }}</p>
                    {!! $qrSvg !!}
                    <a href="#carte-haut" class="qr-plein__fermer">Fermer</a>
                </div>
            @endif

            <p class="carte__pied">
                Carte de visite numérique créée avec
                <a href="{{ route('home') }}">{{ config('app.name') }}</a>
            </p>
        </div>

        {{-- ═══════════════ L'ACTION QUI COMPTE ═══════════════
             Fixée en bas : au fil du contenu, elle n'était visible qu'après
             défilement, donc facultative. C'est pourtant tout l'objet du scan
             — on regarde, puis on GARDE. --}}
        <div class="carte__barre">
            <a href="{{ route('profile.vcard', $profile->slug) }}"
               class="carte__enregistrer">Enregistrer le contact</a>
        </div>
    </div>
</x-public-profile-layout>
