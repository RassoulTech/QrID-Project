{{--
  x-phone — cadre de smartphone complet, réutilisable.

  <x-phone :profile="$p" />                grande taille (hero, 280px)
  <x-phone :profile="$p" size="sm" />      section sombre (240px)
  <x-phone :profile="$p" :animate="false" />

  Un seul composant pour tous les emplacements : hero, section sombre,
  page de démonstration, aperçu avant paiement.

  Tout en HTML/CSS, icônes en SVG inline. Aucune image, aucune police
  d'icônes, aucune requête réseau.
--}}
@props([
    'profile',
    'size' => 'lg',
    'animate' => true,
])

@php
    $initials = mb_strtoupper(
        mb_substr($profile->first_name, 0, 1).mb_substr($profile->last_name, 0, 1)
    );
@endphp

{{-- Les réseaux ne sont affichés « pour de vrai » que si la relation a été
     chargée en amont : aucune requête déclenchée depuis une vue, donc aucun
     N+1 possible. Sur la landing, les pastilles restent décoratives. --}}
@php
    $liens = $profile->relationLoaded('socialLinks') ? $profile->socialLinks : collect();

    /*
     | L'ACCENT DE LA PAGE PUBLIQUE NE SUIT PLUS LA CARTE.
     |
     | Il valait autrefois primary_color, c'est-à-dire la teinte libre choisie
     | par le client. Avec les variantes, cette valeur ne signifie plus « une
     | couleur d'accent » mais « le fond de la carte » — et la reprendre ici
     | donnerait, pour la variante blanche, un entête blanc sur fond blanc et
     | un bouton invisible.
     |
     | La page publique porte donc le vert de la marque, toujours. C'est aussi
     | ce que demande la cohérence : cette page est vue par des inconnus, elle
     | doit dire QrID avant de dire son porteur.
     */
    $teinte = \App\Enums\VarianteCarte::Verte->value;
@endphp

<div {{ $attributes->merge([
        'class' => 'phone phone--'.$size.($animate ? ' phone--enter' : ''),
        'style' => '--phone-accent:'.$teinte,
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

        {{-- En-tête vert foncé --}}
        <div class="phone__header">
            <span class="phone__avatar">
                <span class="phone__halo" aria-hidden="true"></span>
                @if ($profile->photo_path)
                    <img src="{{ Storage::url($profile->photo_path) }}"
                         alt="{{ $profile->full_name }}" width="72" height="72">
                @else
                    {{ $initials }}
                @endif
            </span>

            <span class="phone__name">{{ $profile->full_name }}</span>
            <span class="phone__role">{{ $profile->job_title }}</span>
            @if ($profile->company)
                <span class="phone__company">{{ $profile->company }}</span>
            @endif
        </div>

        {{-- Quatre actions, remontant sur l'en-tête --}}
        <div class="phone__actions">
            <span class="phone__action">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                    <path d="M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.6 17.6 0 0 0 4.168 6.608 17.6 17.6 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.68.68 0 0 0-.58-.122l-2.19.547a1.75 1.75 0 0 1-1.657-.459L5.482 8.062a1.75 1.75 0 0 1-.46-1.657l.548-2.19a.68.68 0 0 0-.122-.58z"/>
                </svg>
                <span>Appeler</span>
            </span>

            <span class="phone__action">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                    <path d="M13.6 2.3A7.9 7.9 0 0 0 8 0a7.9 7.9 0 0 0-6.8 11.9L0 16l4.2-1.1A7.9 7.9 0 0 0 8 15.9 7.9 7.9 0 0 0 13.6 2.3M8 14.6c-1.2 0-2.4-.3-3.4-.9l-.2-.1-2.5.7.7-2.4-.2-.3A6.6 6.6 0 1 1 8 14.6m3.6-4.9c-.2-.1-1.2-.6-1.3-.6s-.3-.1-.4.1l-.6.7c-.1.1-.2.1-.4 0a5.4 5.4 0 0 1-2.6-2.3c-.2-.3.2-.3.5-1 0-.1 0-.2-.1-.3L6 4.9c-.1-.3-.3-.3-.4-.3h-.4c-.1 0-.4.1-.6.3-.2.2-.7.7-.7 1.8s.8 2.1.9 2.2 1.6 2.4 3.8 3.4c1.4.6 2 .6 2.7.5.4-.1 1.2-.5 1.4-1s.2-.9.1-1z"/>
                </svg>
                <span>WhatsApp</span>
            </span>

            <span class="phone__action">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                    <path d="M.05 3.555A2 2 0 0 1 2 2h12a2 2 0 0 1 1.95 1.555L8 8.414zM0 4.697v7.104l5.803-3.558zM6.761 8.83l-6.57 4.027A2 2 0 0 0 2 14h12a2 2 0 0 0 1.808-1.144l-6.57-4.027L8 9.586zm3.436-.586L16 11.801V4.697z"/>
                </svg>
                <span>E-mail</span>
            </span>

            <span class="phone__action">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                    <path d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m7.5-6.923c-.67.204-1.335.82-1.887 1.855A8 8 0 0 0 5.145 4H7.5zM4.09 4a9.3 9.3 0 0 1 .64-1.539 7 7 0 0 1 .597-.933A7.03 7.03 0 0 0 2.255 4zm-.582 3.5c.03-.877.138-1.718.312-2.5H1.674a7 7 0 0 0-.656 2.5zM4.847 5a12.5 12.5 0 0 0-.338 2.5H7.5V5zM8.5 5v2.5h2.99A12.5 12.5 0 0 0 11.153 5zm-.001 3.5V11h2.849c.174-.782.282-1.623.312-2.5zM4.51 8.5a12.5 12.5 0 0 0 .337 2.5H7.5V8.5zm5.145 5.401c.552-1.035.898-2.348 1.048-3.901H8.5v2.923c.67-.204 1.335-.82 1.887-1.855z"/>
                </svg>
                <span>Site</span>
            </span>
        </div>

        {{-- Coordonnées --}}
        <div class="phone__list">
            @if ($profile->phone)
                <span class="phone__row">
                    <span class="phone__row-icon" aria-hidden="true">
                        <svg width="11" height="11" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.6 17.6 0 0 0 4.168 6.608 17.6 17.6 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.68.68 0 0 0-.58-.122l-2.19.547a1.75 1.75 0 0 1-1.657-.459L5.482 8.062a1.75 1.75 0 0 1-.46-1.657l.548-2.19a.68.68 0 0 0-.122-.58z"/>
                        </svg>
                    </span>
                    <span class="phone__row-text">{{ $profile->formatted_phone }}</span>
                </span>
            @endif

            @if ($profile->public_email)
                <span class="phone__row">
                    <span class="phone__row-icon" aria-hidden="true">
                        <svg width="11" height="11" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M.05 3.555A2 2 0 0 1 2 2h12a2 2 0 0 1 1.95 1.555L8 8.414zM0 4.697v7.104l5.803-3.558zM6.761 8.83l-6.57 4.027A2 2 0 0 0 2 14h12a2 2 0 0 0 1.808-1.144l-6.57-4.027L8 9.586zm3.436-.586L16 11.801V4.697z"/>
                        </svg>
                    </span>
                    <span class="phone__row-text">{{ $profile->public_email }}</span>
                </span>
            @endif

            @if ($profile->address)
                <span class="phone__row">
                    <span class="phone__row-icon" aria-hidden="true">
                        <svg width="11" height="11" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10m0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6"/>
                        </svg>
                    </span>
                    <span class="phone__row-text">{{ $profile->address }}</span>
                </span>
            @endif
        </div>

        {{-- Réseaux sociaux --}}
        @if ($liens->isNotEmpty())
            <div class="phone__socials">
                @foreach ($liens as $lien)
                    <span class="phone__social" title="{{ \App\Services\ProfileWizardService::PLATFORMS[$lien->platform] ?? $lien->platform }}">
                        {{ mb_strtoupper(mb_substr($lien->platform, 0, 1)) }}
                    </span>
                @endforeach
            </div>
        @elseif (! $profile->relationLoaded('socialLinks'))
            <div class="phone__socials" aria-hidden="true">
                <span class="phone__social"></span>
                <span class="phone__social"></span>
                <span class="phone__social"></span>
                <span class="phone__social"></span>
            </div>
        @endif

        {{-- Bouton pleine largeur --}}
        <div class="phone__save">Enregistrer le contact</div>
    </div>
</div>
