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
      {{-- LA COUVERTURE, COMME SUR LA VRAIE PAGE PUBLIQUE.

           Cet appareil montrait un portrait dans une pastille ronde. Le
           produit ne demande plus de portrait : la carte réelle affiche une
           SEULE image, en bandeau, et le nom par-dessus.

           Un aperçu qui ne ressemble pas à ce qu'on livre promet autre chose
           que le produit — c'est la pire forme de démonstration. --}}
      @if ($profile->cover_path)
        <img src="{{ Storage::url($profile->cover_path) }}"
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
