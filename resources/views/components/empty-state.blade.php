{{--
  x-empty-state — écran vide, avec une action pour en sortir.

  <x-empty-state title="Aucun profil publié"
                 message="Créez votre profil pour le partager par QR Code.">
      <x-slot name="action"><x-button :href="route('profile.create.step1')">Créer</x-button></x-slot>
  </x-empty-state>

  Props : title, message, icon (profile | payment | search | document)
  Slots : action
  Les icônes sont des SVG internes : aucune image externe, aucune requête.
--}}
@props([
    'title' => 'Rien à afficher',
    'message' => null,
    'icon' => 'document',
])

<div {{ $attributes->merge(['class' => 'empty-state']) }}>
    <div class="empty-state__icon" aria-hidden="true">
        @switch($icon)
            @case('profile')
                <svg width="26" height="26" viewBox="0 0 16 16" fill="currentColor"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z"/></svg>
                @break
            @case('payment')
                <svg width="26" height="26" viewBox="0 0 16 16" fill="currentColor"><path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v1h14V4a1 1 0 0 0-1-1zm13 4H1v5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1z"/></svg>
                @break
            @case('search')
                <svg width="26" height="26" viewBox="0 0 16 16" fill="currentColor"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1M12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/></svg>
                @break
            @default
                <svg width="26" height="26" viewBox="0 0 16 16" fill="currentColor"><path d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h5.5zm-3 0A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V4.5z"/></svg>
        @endswitch
    </div>

    <h3 class="h6 fw-bold mb-1">{{ $title }}</h3>

    @if ($message)
        <p class="text-secondary small mb-3 mx-auto" style="max-width: 24rem;">{{ $message }}</p>
    @endif

    @isset($action)
        <div>{{ $action }}</div>
    @endisset
</div>
