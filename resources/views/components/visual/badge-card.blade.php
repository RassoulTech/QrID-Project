{{--
  x-visual.badge-card — carte verte à icône, mise en avant d'un message court.

      <x-visual.badge-card icon="cle" title="Retour rapide" text="…" />

  C'est le bloc vert que montrent les maquettes « mot de passe oublié » et
  « confirmation d'e-mail », posé sur les colonnes de droite à fond clair.
--}}
@props([
    'icon' => 'bouclier',
    'title' => '',
    'text' => null,
])

<div {{ $attributes->merge(['class' => 'av-badge']) }}>
    <span class="av-badge__icon">
        <svg width="20" height="20" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
            @switch($icon)
                @case('cle')
                    <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2m3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2"/>
                    @break
                @case('enveloppe')
                    <path d="M.05 3.555A2 2 0 0 1 2 2h12a2 2 0 0 1 1.95 1.555L8 8.414zM0 4.697v7.104l5.803-3.558zM6.761 8.83l-6.57 4.027A2 2 0 0 0 2 14h12a2 2 0 0 0 1.808-1.144l-6.57-4.027L8 9.586zm3.436-.586L16 11.801V4.697z"/>
                    @break
                @case('horloge')
                    <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z"/>
                    <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0"/>
                    @break
                @default
                    <path d="M8 0c-.69 0-1.843.265-2.928.56-1.11.3-2.229.655-2.887.87a1.54 1.54 0 0 0-1.044 1.262c-.596 4.477.787 7.795 2.465 9.99a11.8 11.8 0 0 0 2.517 2.453c.386.273.744.482 1.048.625.28.132.581.24.829.24s.548-.108.829-.24a7 7 0 0 0 1.048-.625 11.8 11.8 0 0 0 2.517-2.453c1.678-2.195 3.061-5.513 2.465-9.99a1.54 1.54 0 0 0-1.044-1.263 63 63 0 0 0-2.887-.87C9.843.266 8.69 0 8 0"/>
            @endswitch
        </svg>
    </span>

    <span class="av-badge__title">{{ $title }}</span>

    @if ($text)
        <span class="av-badge__text">{{ $text }}</span>
    @endif
</div>
