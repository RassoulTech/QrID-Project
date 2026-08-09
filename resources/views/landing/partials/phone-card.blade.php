{{-- Téléphone : bordure verte épaisse, coins très arrondis.
     Le profil provient de la base (DemoSeeder), jamais écrit en dur.

     Props : profile (App\Models\Profile) --}}
@props(['profile'])

@php
    $initials = mb_strtoupper(mb_substr($profile->first_name, 0, 1).mb_substr($profile->last_name, 0, 1));
@endphp

<div class="phone">
  <div class="phone__screen">
    <div class="pf-avatar">
      @if ($profile->photo_path)
        <img src="{{ Storage::url($profile->photo_path) }}"
             alt="{{ $profile->full_name }}"
             width="64" height="64" style="width:100%;height:100%;object-fit:cover">
      @else
        {{ $initials }}
      @endif
    </div>

    <div class="pf-name">{{ $profile->full_name }}</div>
    <div class="pf-role">{{ $profile->job_title }}</div>

    {{-- Deux lignes de contenu grisées --}}
    <div class="pf-lines">
      <div class="pf-line">
        <span class="pf-line__dot"></span>
        <span class="pf-line__bar"></span>
      </div>
      <div class="pf-line pf-line--short">
        <span class="pf-line__dot"></span>
        <span class="pf-line__bar"></span>
      </div>
    </div>
  </div>
</div>
