{{--
  x-card — bloc de contenu.

  <x-card title="Mon profil" subtitle="Vos informations publiques">
      ...contenu...
      <x-slot name="footer"><x-button>Enregistrer</x-button></x-slot>
  </x-card>

  Props : title, subtitle, flush (sans padding sur le corps)
  Slots : default, header (remplace title/subtitle), actions (à droite de
          l'en-tête), footer
--}}
@props([
    'title' => null,
    'subtitle' => null,
    'flush' => false,
])

<div {{ $attributes->merge(['class' => 'card']) }}>
    @if (isset($header) || $title || $subtitle)
        <div class="card-header bg-white d-flex justify-content-between align-items-center gap-3">
            <div>
                @isset($header)
                    {{ $header }}
                @else
                    @if ($title)
                        <h2 class="h6 fw-bold mb-0">{{ $title }}</h2>
                    @endif
                    @if ($subtitle)
                        <p class="text-secondary small mb-0 mt-1">{{ $subtitle }}</p>
                    @endif
                @endisset
            </div>

            @isset($actions)
                <div class="flex-shrink-0">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    <div class="{{ $flush ? '' : 'card-body' }}">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="card-footer bg-white">
            {{ $footer }}
        </div>
    @endisset
</div>
