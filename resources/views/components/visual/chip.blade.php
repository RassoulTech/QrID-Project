{{--
  x-visual.chip — pastille flottante autour d'une carte.

      <x-visual.chip icon="qr" label="QR Code généré" position="haut-gauche" />

  Icônes : qr · check · vue · bouclier · horloge · enveloppe · cle
  Positions : haut-gauche · haut-droite · bas-gauche · bas-droite
--}}
@props([
    'icon' => 'check',
    'label' => '',
    'position' => 'haut-gauche',
])

@php
    $positions = [
        'haut-gauche' => 'av-chip--hg',
        'haut-droite' => 'av-chip--hd',
        'bas-gauche' => 'av-chip--bg',
        'bas-droite' => 'av-chip--bd',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'av-chip '.($positions[$position] ?? 'av-chip--hg')]) }}>
    <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
        @switch($icon)
            @case('qr')
                <path d="M2 2h2v2H2z"/>
                <path d="M6 0v6H0V0zM5 1H1v4h4zM4 12H2v2h2z"/>
                <path d="M6 10v6H0v-6zm-5 1v4h4v-4zm11-9h2v2h-2z"/>
                <path d="M10 0v6h6V0zm5 1v4h-4V1zM8 8v2H6V8zm2 2V8h2v2zm-2 2v-2H6v2zm2 0h2v-2h-2zm4 0v2h-2v-2z"/>
                @break
            @case('vue')
                <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/>
                <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/>
                @break
            @case('bouclier')
                <path d="M8 0c-.69 0-1.843.265-2.928.56-1.11.3-2.229.655-2.887.87a1.54 1.54 0 0 0-1.044 1.262c-.596 4.477.787 7.795 2.465 9.99a11.8 11.8 0 0 0 2.517 2.453c.386.273.744.482 1.048.625.28.132.581.24.829.24s.548-.108.829-.24a7 7 0 0 0 1.048-.625 11.8 11.8 0 0 0 2.517-2.453c1.678-2.195 3.061-5.513 2.465-9.99a1.54 1.54 0 0 0-1.044-1.263 63 63 0 0 0-2.887-.87C9.843.266 8.69 0 8 0"/>
                @break
            @case('horloge')
                <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z"/>
                <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0"/>
                @break
            @case('enveloppe')
                <path d="M.05 3.555A2 2 0 0 1 2 2h12a2 2 0 0 1 1.95 1.555L8 8.414zM0 4.697v7.104l5.803-3.558zM6.761 8.83l-6.57 4.027A2 2 0 0 0 2 14h12a2 2 0 0 0 1.808-1.144l-6.57-4.027L8 9.586zm3.436-.586L16 11.801V4.697z"/>
                @break
            @case('cle')
                <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2m3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2"/>
                @break
            @default
                <path d="M12.736 3.97a.73.73 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425z"/>
        @endswitch
    </svg>
    {{ $label }}
</span>
