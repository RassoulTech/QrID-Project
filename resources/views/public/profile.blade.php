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
    @php
        $initiales = mb_strtoupper(
            mb_substr((string) $profile->first_name, 0, 1).mb_substr((string) $profile->last_name, 0, 1)
        );

        $initiales = $initiales !== ''
            ? $initiales
            : mb_strtoupper(mb_substr($profile->full_name, 0, 1));

        /*
         | LA GRILLE D'ACTIONS — réseaux ET moyens de contact, ensemble.
         |
         | Le visiteur ne distingue pas « un réseau social » d'« un numéro de
         | téléphone » : il cherche par quel moyen joindre cette personne. Les
         | séparer en deux zones l'oblige à balayer la page deux fois.
         |
         | Seules les entrées renseignées entrent : un bouton mort est une
         | déception à retardement.
         */
        $actions = [];

        if ($profile->whatsapp_href) {
            $actions[] = ['whatsapp', $profile->whatsapp_href, 'WhatsApp', true];
        }

        if ($profile->phone) {
            $actions[] = ['telephone', $profile->tel_href, 'Appeler', false];
        }

        if ($lienCarte = $profile->lienCarte()) {
            $actions[] = ['localisation', $lienCarte, 'Localisation', true];
        }

        foreach ($profile->socialLinks as $lien) {
            $actions[] = [$lien->platform, $lien->url, $lien->platform_label, true];
        }
    @endphp

    <main class="pubc">
        {{-- La bascule de thème flotte hors de la carte : elle appartient au
             confort du visiteur, pas à l'identité du porteur. --}}
        <div class="pubc__theme"><x-theme-toggle /></div>

        <article class="pubc__carte">

            {{-- ═══════════════ COUVERTURE ═══════════════ --}}
            <header class="pubc__couverture">
                {{-- Aucune image de couverture définie : un dégradé de marque
                     plutôt qu'un vide. Un bandeau gris ferait croire à un
                     chargement qui n'aboutit pas. --}}
                <span class="pubc__couverture-fond" aria-hidden="true"></span>

                {{-- ═══════════════ MÉDAILLON ═══════════════
                     LA VÉRIFICATION PORTE SUR LE FICHIER, PAS SUR LE CHAMP.
                     photo_path renseigné ne veut pas dire fichier présent :
                     sur un disque éphémère, chaque déploiement efface les
                     photos et laissait un bandeau vide avec une icône
                     d'image cassée. Voir PublicProfileController::photo(). --}}
                <div class="pubc__medaillon">
                    @if ($photoUrl)
                        <img src="{{ $photoUrl }}" alt=""
                             class="pubc__photo" width="120" height="120"
                             decoding="async" fetchpriority="high">
                    @else
                        <span class="pubc__initiales" aria-hidden="true">{{ $initiales }}</span>
                    @endif
                </div>
            </header>

            {{-- ═══════════════ IDENTITÉ ═══════════════ --}}
            <div class="pubc__identite">
                <h1 class="pubc__nom">{{ $profile->full_name }}</h1>

                @if ($profile->job_title)
                    <p class="pubc__role">{{ $profile->job_title }}</p>
                @endif

                @if ($profile->company)
                    <p class="pubc__entreprise">{{ $profile->company }}</p>
                @endif
            </div>

            {{-- ═══════════════ COORDONNÉES ═══════════════
                 Encadrées et teintées : elles forment un bloc qu'on lit d'un
                 seul regard, distinct des actions qu'on touche. --}}
            @if ($profile->public_email || $profile->website || $profile->address)
                <ul class="pubc__infos">
                    @if ($profile->public_email)
                        <li>
                            <a href="mailto:{{ $profile->public_email }}" class="pubc__info">
                                <span class="pubc__info-icone" aria-hidden="true">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 7 10 6 10-6"/>
                                    </svg>
                                </span>
                                <span class="pubc__info-texte">
                                    <span class="pubc__info-valeur">{{ $profile->public_email }}</span>
                                    <span class="pubc__info-etiquette">E-mail</span>
                                </span>
                            </a>
                        </li>
                    @endif

                    @if ($profile->website)
                        <li>
                            <a href="{{ $profile->website }}" class="pubc__info" target="_blank" rel="noopener">
                                <span class="pubc__info-icone" aria-hidden="true">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="9"/>
                                        <path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"/>
                                    </svg>
                                </span>
                                <span class="pubc__info-texte">
                                    <span class="pubc__info-valeur">{{ preg_replace('#^https?://(www\.)?#', '', $profile->website) }}</span>
                                    <span class="pubc__info-etiquette">Site web</span>
                                </span>
                            </a>
                        </li>
                    @endif

                    @if ($profile->address)
                        <li>
                            {{-- CLIQUABLE : elle ouvre la position réelle, le
                                 lien exact du porteur s'il en a collé un, une
                                 recherche cartographique sinon. --}}
                            <a href="{{ $profile->lienCarte() }}" class="pubc__info"
                               target="_blank" rel="noopener">
                                <span class="pubc__info-icone" aria-hidden="true">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>
                                    </svg>
                                </span>
                                <span class="pubc__info-texte">
                                    <span class="pubc__info-valeur">{{ $profile->address }}</span>
                                    <span class="pubc__info-etiquette">Localisation</span>
                                </span>
                            </a>
                        </li>
                    @endif
                </ul>
            @endif

            {{-- ═══════════════ LA GRILLE — l'élément central ═══════════════
                 Trois par rangée. Elle se réorganise seule s'il y a moins de
                 six entrées : une grille à trous se lirait comme un défaut. --}}
            @if (! empty($actions))
                <nav class="pubc__grille" aria-label="Contacter et suivre">
                    @foreach ($actions as [$plateforme, $url, $libelle, $externe])
                        <a href="{{ $url }}" class="pubc__tuile"
                           @if ($externe) target="_blank" rel="noopener" @endif
                           aria-label="{{ $libelle }}">
                            <x-social-icon :plateforme="$plateforme" :taille="22" />
                            <span class="pubc__tuile-nom">{{ $libelle }}</span>
                        </a>
                    @endforeach
                </nav>
            @endif

            {{-- ═══════════════ BARRE D'ACTIONS ═══════════════
                 PARTAGER ouvre le QR en plein écran, sans JavaScript, par
                 :target. ENREGISTRER est un LIEN DIRECT vers la route de
                 téléchargement — jamais un appel JavaScript, c'est ce qui
                 casse sur iOS où Safari refuse d'ouvrir Contacts depuis un
                 blob. --}}
            <div class="pubc__barre">
                @if (! empty($qrSvg))
                    <a href="#qr" class="pubc__action pubc__action--clair">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="3" width="7" height="7" rx="1"/>
                            <rect x="14" y="3" width="7" height="7" rx="1"/>
                            <rect x="3" y="14" width="7" height="7" rx="1"/>
                            <path d="M14 14h3v3h-3zM19 19h2v2h-2z"/>
                        </svg>
                        Partager
                    </a>
                @endif

                <a href="{{ route('profile.vcard', $profile->slug) }}" class="pubc__action pubc__action--plein">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/>
                    </svg>
                    Enregistrer
                </a>
            </div>

            <p class="pubc__pied">
                Carte créée avec <a href="{{ route('home') }}">{{ config('app.name') }}</a>
            </p>
        </article>

        @if (! empty($qrSvg))
            <div id="qr" class="pubc__qr-plein" role="dialog" aria-label="QR Code">
                <p class="pubc__qr-nom">{{ $profile->full_name }}</p>
                {!! $qrSvg !!}
                <a href="#" class="pubc__qr-fermer">Fermer</a>
            </div>
        @endif
    </main>
</x-public-profile-layout>
