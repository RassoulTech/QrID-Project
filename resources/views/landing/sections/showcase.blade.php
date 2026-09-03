{{-- SECTION SOMBRE — fond vert foncé, pleine largeur.
     À gauche : titre, texte, icône « Scanner & Voir » et avatars +500.
     À droite : carte claire arrondie contenant un téléphone.
     Props : $showcaseProfile --}}
<section class="showcase">
  <div class="wrap showcase__grid" data-reveal="scale">

    <div>
      <h2 class="showcase__title">{{ __('landing.demo.titre') }}</h2>

      <p class="showcase__text">
        {{ __('landing.demo.texte', ['marque' => config('app.name')]) }}
      </p>

      <div class="showcase__meta">
        <div class="scan-badge">
          <span class="scan-badge__box" aria-hidden="true">
            <svg width="22" height="22" viewBox="0 0 16 16" fill="currentColor">
              <path d="M2 2h2v2H2z"/>
              <path d="M6 0v6H0V0zM5 1H1v4h4zM4 12H2v2h2z"/>
              <path d="M6 10v6H0v-6zm-5 1v4h4v-4zm11-9h2v2h-2z"/>
              <path d="M10 0v6h6V0zm5 1v4h-4V1zM8 8v2H6V8zm2 2V8h2v2zm-2 2v-2H6v2zm2 0h2v-2h-2zm4 0v2h-2v-2z"/>
            </svg>
          </span>
          <div class="scan-badge__label">{!! __('landing.demo.scanner') !!}</div>
        </div>

        {{-- ═══════════════ LE NOMBRE DE CARTES EN LIGNE ═══════════════
             COMPTÉ, ET AFFICHÉ SEULEMENT QUAND IL PARLE.

             Cette pastille disait « +500 » et l'étiquette « Plus de 500
             professionnels ». Rien ne mesurait ce nombre : il était écrit
             dans un fichier de langue.

             Un visiteur qui découvre ensuite que le produit compte trois
             clients ne se dit pas « ils ont arrondi ». Il se dit qu'on lui a
             menti, et cesse de croire le reste de la page — tarifs compris.

             En dessous du seuil, la même idée sans nombre : la preuve
             sociale ne vaut que si elle est vraie, et une formulation
             qualitative n'engage rien de faux. Au-dessus, le chiffre réel
             devient une bien meilleure preuve que n'importe quel arrondi. --}}
        <div class="avatars"
             aria-label="{{ $cartesEnLigne >= $seuilVitrine
                ? __('landing.demo.professionnels', ['compte' => $cartesEnLigne])
                : __('landing.demo.professionnels_debut') }}">
          <span class="avatars__item">AD</span>
          <span class="avatars__item">MF</span>
          <span class="avatars__item">FS</span>

          @if ($cartesEnLigne >= $seuilVitrine)
            <span class="avatars__item avatars__more">+{{ $cartesEnLigne }}</span>
          @endif
        </div>
      </div>
    </div>

    {{-- Socle vert plus clair, ombre interne, téléphone débordant vers le haut --}}
    <div class="phone-pedestal">
      <x-phone :profile="$showcaseProfile" size="sm" />
    </div>

  </div>
</section>
