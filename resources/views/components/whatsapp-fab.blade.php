{{--
  x-whatsapp-fab — le bouton d'aide WhatsApp, contextuel.

      <x-whatsapp-fab />                         lien discret, centré en bas d'écran
      <x-whatsapp-fab variante="barre" />        bouton-icône « ? » d'une barre
      <x-whatsapp-fab variante="lien" />         lien en ligne (pied de page)
      <x-whatsapp-fab message="Bonjour, …" />    message imposé (hors route)

  IL NE FLOTTE PLUS. La pastille verte « Une question ? » était fixée en bas
  à droite et recouvrait du contenu — le dernier bouton d'un formulaire, la
  colonne d'aperçu d'une carte. Elle vit désormais DANS la page : dans la
  barre des espaces connectés (icône « ? »), dans le pied de page de la
  vitrine, sous le formulaire des écrans d'accès. Rien n'est recouvert, et
  la fonction est intacte.

  LE MESSAGE PRÉ-REMPLI EST DÉDUIT DE LA PAGE (App\Support\AideContextuelle) :
  « je suis à l'étape 2 de la création de ma carte » se traite tout de suite.

  IL N'APPARAÎT PAS SI LE NUMÉRO N'EST PAS CONFIGURÉ : une aide qui mène à
  une conversation vide est pire que pas d'aide.

  target="_blank" + rel="noopener" : WhatsApp s'ouvre à côté, la page en
  cours n'est pas perdue.
--}}
@props([
    'message' => null,
    'variante' => 'bloc',
])

@php
    $lien = $message !== null
        ? \App\Support\Whatsapp::lien(config('landing.support.whatsapp'), $message)
        : \App\Support\Whatsapp::assistance();
@endphp

@if ($lien !== null)
    @if ($variante === 'barre')
        <a href="{{ $lien }}" target="_blank" rel="noopener noreferrer"
           {{ $attributes->merge(['class' => 'aide-wa aide-wa--barre']) }}
           aria-label="{{ __('navigation.coque.aide_whatsapp') }}"
           title="{{ __('navigation.coque.aide_court') }}">
            <x-icone name="help-circle" class="aide-wa__icone" />
        </a>
    @else
        @if ($variante === 'bloc')<p class="aide-wa-bloc">@endif
        <a href="{{ $lien }}" target="_blank" rel="noopener noreferrer"
           {{ $attributes->merge(['class' => 'aide-wa']) }}>
            <svg class="aide-wa__icone" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">
                <path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97s-.47-.15-.67.15-.77.96-.94 1.16-.35.22-.65.07a8.1 8.1 0 0 1-2.39-1.47 9 9 0 0 1-1.65-2.06c-.17-.3-.02-.46.13-.61s.3-.35.45-.52.2-.3.3-.5.05-.37-.02-.52-.67-1.61-.92-2.21c-.24-.58-.49-.5-.67-.51h-.57a1.1 1.1 0 0 0-.8.37 3.35 3.35 0 0 0-1.04 2.48 5.8 5.8 0 0 0 1.22 3.09 13.3 13.3 0 0 0 5.09 4.5c.71.3 1.27.49 1.7.63a4.1 4.1 0 0 0 1.88.12 3.07 3.07 0 0 0 2.01-1.42 2.5 2.5 0 0 0 .17-1.42c-.07-.12-.27-.2-.57-.35z"/>
                <path d="M12.04 2a9.9 9.9 0 0 0-8.5 15.02L2 22.5l5.62-1.47A9.9 9.9 0 1 0 12.04 2m0 1.67a8.23 8.23 0 1 1-4.19 15.31l-.3-.18-3.34.87.89-3.25-.2-.31A8.23 8.23 0 0 1 12.04 3.67"/>
            </svg>
            <span>{{ __('navigation.coque.aide_whatsapp') }}</span>
        </a>
        @if ($variante === 'bloc')</p>@endif
    @endif
@endif
