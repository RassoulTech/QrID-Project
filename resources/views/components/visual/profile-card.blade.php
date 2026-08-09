{{--
  x-visual.profile-card — la carte professionnelle miniature.

      <x-visual.profile-card initials="AN" name="Awa Ndiaye" role="Architecte · Atelier Teranga" />

  Entièrement en CSS : aucune requête, aucune image, aucun profil lu en base
  pour un visuel décoratif. Posée dans le slot « aside » par les pages dont la
  maquette la montre — pas par toutes.
--}}
@props([
    'initials' => 'AN',
    'name' => 'Awa Ndiaye',
    'role' => 'Architecte · Atelier Teranga',
    'lines' => 3,
    'cta' => true,
])

<div {{ $attributes->merge(['class' => 'av-card']) }}>
    <div class="av-card__head">
        <span class="av-card__avatar">{{ $initials }}</span>
        <span class="av-card__name">{{ $name }}</span>
        <span class="av-card__role">{{ $role }}</span>
    </div>

    <div class="av-card__body">
        @for ($i = 1; $i <= $lines; $i++)
            <span @class(['av-card__line', 'av-card__line--w70' => $i === 2, 'av-card__line--w50' => $i === 3])></span>
        @endfor

        @if ($cta)
            <span class="av-card__cta"></span>
        @endif
    </div>
</div>
