{{--
  x-carte-publique — LA CARTE elle-même, sans la page qui la porte.

  <x-carte-publique :profile="$p" />
  <x-carte-publique :profile="$p" variante="b" :apercu="true" />

  ═══════════════════════════════════════════════════════════════════════
  POURQUOI ELLE EST SORTIE DE LA VUE
  ═══════════════════════════════════════════════════════════════════════
  Elle vivait dans public/profile.blade.php, et elle n'était donc visible
  que là. Comparer trois partis pris visuels côte à côte demandait de la
  recopier trois fois — c'est-à-dire de comparer trois copies au lieu de la
  chose elle-même, et de les voir diverger dès la première correction.

  La page publique et /design-system rendent maintenant EXACTEMENT le même
  composant. Ce qu'on juge sur la planche est ce qui sera servi.

  ═══════════════════════════════════════════════════════════════════════
  LA STRUCTURE EST VALIDÉE ET NE BOUGE PAS
  ═══════════════════════════════════════════════════════════════════════
  Bandeau, médaillon, identité, bloc de coordonnées, grille de six boutons,
  barre d'actions. Dans cet ordre.

  ═══════════════════════════════════════════════════════════════════════
  IL N'Y A PLUS DE MÉDAILLON, ET L'IDENTITÉ EST DANS L'IMAGE
  ═══════════════════════════════════════════════════════════════════════
  Un portrait rond posé sur une bannière obligeait à deux images : une
  photo de visage ET une couverture. Le porteur en fournissait rarement
  deux, et la page montrait donc le plus souvent un médaillon d'initiales
  sur un dégradé — deux replis empilés, c'est-à-dire un vide décoré.

  Une seule image désormais, qui occupe toute la largeur, et le nom posé
  DESSUS. Le visiteur reçoit un visuel plein écran et un nom, pas une
  vignette et un bandeau.

  Props :
    profile       le porteur
    couvertureUrl l'adresse de l'image, ou null (repli : le décor de marque)
    qrSvg         le QR en plein écran, ou null
    apercu        true sur /design-system : les liens ne mènent nulle part
--}}
@props([
    'profile',
    'couvertureUrl' => null,
    'qrSvg' => null,
    'apercu' => false,
])

@php
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
        $actions[] = ['telephone', $profile->tel_href, __('card.actions.appeler'), false];
    }

    if ($lienCarte = $profile->lienCarte()) {
        $actions[] = ['localisation', $lienCarte, __('card.actions.localisation'), true];
    }

    foreach ($profile->socialLinks as $lien) {
        $actions[] = [$lien->platform, $lien->url, $lien->platform_label, true];
    }
@endphp

<div class="pubc{{ $apercu ? ' pubc--apercu' : '' }}">
    <article class="pubc__carte">

        {{-- ═══════════════ COUVERTURE ═══════════════
             Elle porte l'identité. Le nom n'est donc PAS répété en dessous :
             le répéter donnerait deux fois la même information à quelques
             pixels d'écart, et repousserait les coordonnées d'un écran. --}}
        <x-couverture :profile="$profile" :url="$couvertureUrl ?? null" />

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
                                <span class="pubc__info-etiquette">{{ __('card.publique.site_web') }}</span>
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
                                <span class="pubc__info-etiquette">{{ __('card.publique.localisation') }}</span>
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
            <nav class="pubc__grille" aria-label="{{ __('card.publique.grille_aria') }}">
                @foreach ($actions as [$plateforme, $url, $libelle, $externe])
                    {{-- LA COULEUR EST POSÉE SUR LA TUILE, pas sur l'icône.
                         C'est elle qui teinte le fond, la bordure et le
                         survol : une variable posée sur le <svg> ne
                         remonterait pas jusqu'ici. --}}
                    <a href="{{ $url }}" class="pubc__tuile"
                       style="--marque:{{ \App\Support\CouleursPlateformes::pour($plateforme) }}"
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
                <a href="{{ $apercu ? '#' : '#qr' }}" class="pubc__action pubc__action--clair">
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

            {{-- LES DONNÉES SONT PORTÉES PAR LE LIEN, en data-*.
                 Le module enregistrer-contact.js les relit pour composer
                 l'intention Android — sans aller-retour serveur au moment
                 précis où le visiteur a décidé d'agir, et sans dupliquer
                 côté client des règles de formatage qui vivent déjà dans
                 VCardService.

                 SANS JAVASCRIPT, ce lien reste la vCard, qui fonctionne
                 partout : sur iOS elle ouvre Contacts d'elle-même grâce à
                 l'en-tête « inline ». --}}
            <a href="{{ $apercu ? '#' : route('profile.vcard', $profile->slug) }}"
               class="pubc__action pubc__action--plein"
               data-enregistrer-contact
               data-nom="{{ $profile->full_name }}"
               data-telephone="{{ $profile->phone }}"
               data-email="{{ $profile->public_email }}"
               data-entreprise="{{ $profile->company }}"
               data-fonction="{{ $profile->job_title }}"
               data-adresse="{{ $profile->address }}"
               data-notes="{{ $profile->website }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/>
                </svg>
                {{ __('card.publique.enregistrer') }}
            </a>
        </div>

        <p class="pubc__pied">
            {{ __('card.publique.pied') }} <a href="{{ route('home') }}">{{ config('app.name') }}</a>
        </p>
    </article>
</div>
