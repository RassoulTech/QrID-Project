{{-- ============================================================================
     LA CARTE PUBLIQUE — la page ouverte après un scan de QR Code.
     ============================================================================

     CE FICHIER N'EST PAS CELUI DE L'APERÇU.

         page publique  →  resources/views/public/profile.blade.php   (ici)
         aperçu client  →  resources/views/profile/preview.blade.php

     Deux vues distinctes, aucune mutualisation. L'aperçu affiche
     volontairement un cadre de smartphone pour simuler le rendu au
     propriétaire ; cette page-ci EST le rendu. Elle a un jour appelé
     <x-phone>, le composant de maquette : le visiteur voyait alors un
     téléphone dessiné dans son téléphone, avec les boutons en double.

     RIEN NE DÉBORDE : ni un nom long, ni une adresse sans espace.
     AUCUN JAVASCRIPT : le QR plein écran s'ouvre par :target.

     Props : $profile (avec socialLinks), $apercuUrl, $qrSvg
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
         | La solution de repli écrivait le nom entier : dès que la photo ne
         | chargeait pas, « Mouhamed Dione » débordait du cercle. Invisible en
         | test — la photo est en cache — et bien visible en 3G.
         */
        $initiales = mb_strtoupper(
            mb_substr((string) $profile->first_name, 0, 1).mb_substr((string) $profile->last_name, 0, 1)
        );

        $initiales = $initiales !== ''
            ? $initiales
            : mb_strtoupper(mb_substr($profile->full_name, 0, 1));

        // Les réseaux mis en avant en bas de carte : ceux sur lesquels on
        // ENGAGE une conversation, pas ceux qu'on consulte.
        $conversation = $profile->socialLinks->whereIn('platform', ['linkedin', 'facebook']);
    @endphp

    <main class="pubc">
        <article class="pubc__carte">

            {{-- ═══════════════ BANDEAU ═══════════════ --}}
            <header class="pubc__bandeau">
                @if ($profile->photo_path)
                    {{-- alt VIDE : le nom est écrit juste dessous. Un texte
                         alternatif non vide s'AFFICHE quand l'image ne charge
                         pas, et débordait alors du bandeau. --}}
                    <img src="{{ Storage::url($profile->photo_path) }}" alt=""
                         class="pubc__photo" width="420" height="180"
                         decoding="async" fetchpriority="high">
                @else
                    <span class="pubc__initiales" aria-hidden="true">{{ $initiales }}</span>
                @endif

                @if (! empty($qrSvg))
                    {{-- Pour montrer sa carte à une TROISIÈME personne sans
                         lui faire recopier une adresse. --}}
                    <a href="#qr" class="pubc__qr-bouton" aria-label="Afficher le QR Code">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="3" width="7" height="7" rx="1"/>
                            <rect x="14" y="3" width="7" height="7" rx="1"/>
                            <rect x="3" y="14" width="7" height="7" rx="1"/>
                            <path d="M14 14h3v3h-3zM19 19h2v2h-2z"/>
                        </svg>
                    </a>
                @endif
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

            {{-- ═══════════════ RÉSEAUX ═══════════════
                 On reconnaît un logo avant de lire un mot : c'est le seul
                 rythme compatible avec les quelques secondes dont dispose la
                 page. Seules les plateformes renseignées apparaissent. --}}
            @if ($profile->socialLinks->isNotEmpty())
                <nav class="pubc__reseaux" aria-label="Réseaux sociaux">
                    @foreach ($profile->socialLinks as $lien)
                        <a href="{{ $lien->url }}" class="pubc__reseau"
                           target="_blank" rel="noopener"
                           aria-label="{{ $lien->platform_label }}">
                            <x-social-icon :plateforme="$lien->platform" />
                        </a>
                    @endforeach
                </nav>
            @endif

            {{-- ═══════════════ COORDONNÉES ═══════════════ --}}
            <ul class="pubc__infos">
                @if ($profile->public_email)
                    <li class="pubc__info">
                        <a href="mailto:{{ $profile->public_email }}" class="pubc__info-lien">
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

                @if ($profile->phone)
                    <li class="pubc__info">
                        <a href="{{ $profile->tel_href }}" class="pubc__info-lien">
                            <span class="pubc__info-icone" aria-hidden="true">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.5 2.8.6a2 2 0 0 1 1.7 2z"/>
                                </svg>
                            </span>
                            <span class="pubc__info-texte">
                                <span class="pubc__info-valeur">{{ $profile->formatted_phone }}</span>
                                <span class="pubc__info-etiquette">Mobile</span>
                            </span>
                        </a>
                    </li>
                @endif

                @if ($profile->website)
                    <li class="pubc__info">
                        <a href="{{ $profile->website }}" class="pubc__info-lien"
                           target="_blank" rel="noopener">
                            <span class="pubc__info-icone" aria-hidden="true">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="9"/>
                                    <path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"/>
                                </svg>
                            </span>
                            <span class="pubc__info-texte">
                                <span class="pubc__info-valeur">{{ preg_replace('#^https?://#', '', $profile->website) }}</span>
                                <span class="pubc__info-etiquette">Site web</span>
                            </span>
                        </a>
                    </li>
                @endif

                @if ($profile->address)
                    <li class="pubc__info">
                        <span class="pubc__info-lien">
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
                        </span>
                    </li>
                @endif
            </ul>

            {{-- ═══════════════ ENGAGER LA CONVERSATION ═══════════════
                 Les réseaux ci-dessus se consultent ; ces deux lignes-ci
                 s'ouvrent sur un échange. D'où le libellé explicite. --}}
            @if ($profile->whatsapp_href || $conversation->isNotEmpty())
                <div class="pubc__appels">
                    @foreach ($conversation as $lien)
                        <a href="{{ $lien->url }}" class="pubc__appel"
                           target="_blank" rel="noopener">
                            <x-social-icon :plateforme="$lien->platform" :taille="17" />
                            <span>Me contacter sur {{ $lien->platform_label }}</span>
                        </a>
                    @endforeach

                    @if ($profile->whatsapp_href)
                        <a href="{{ $profile->whatsapp_href }}" class="pubc__appel"
                           target="_blank" rel="noopener">
                            <x-social-icon plateforme="whatsapp" :taille="17" />
                            <span>M'écrire sur WhatsApp</span>
                        </a>
                    @endif
                </div>
            @endif

            {{-- ═══════════════ L'ACTION QUI COMPTE ═══════════════
                 Un LIEN DIRECT vers la route de téléchargement, jamais un
                 appel JavaScript : c'est ce qui casse le plus souvent sur
                 iOS, où Safari refuse d'ouvrir Contacts depuis un blob. --}}
            <a href="{{ route('profile.vcard', $profile->slug) }}"
               class="pubc__enregistrer">Enregistrer le contact</a>

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
