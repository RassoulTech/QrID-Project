{{--
  x-alert — l'alerte : le cachet en grand (fond teinté, trait, bande, icône).

      <x-alert type="success">Profil publié.</x-alert>
      <x-alert type="warning" title="3 paiements à vérifier">…</x-alert>
      <x-alert type="danger" title="Paiement refusé" :dismissible="false">…</x-alert>

  Types : success · info · warning · danger.

  LE TEXTE RESTE À L'ENCRE du thème (13:1 au pire) ; l'icône et la bande de
  6px portent l'état — jamais la couleur seule. `role="alert"` pour ce qui
  bloque (danger, warning), `role="status"` pour le reste : un message de
  réussite n'interrompt pas la lecture.

  « Fermer » utilise Bootstrap ; sans JavaScript, le message reste affiché,
  ce qui n'empêche rien.
--}}
@props([
    'type' => 'info',
    'dismissible' => true,
    'title' => null,
])

@php
    $role = in_array($type, ['danger', 'warning']) ? 'alert' : 'status';

    $etat = ['success' => 'succes', 'warning' => 'alerte', 'danger' => 'danger'][$type] ?? 'info';
    $icone = ['succes' => 'check-circle', 'alerte' => 'alert-triangle', 'danger' => 'alert-circle'][$etat] ?? 'info';
@endphp

<div {{ $attributes->merge(['class' => 'alerte alerte--'.$etat.' alert alert-'.$type.($dismissible ? ' fade show' : '')]) }}
     role="{{ $role }}">
    <x-icone :name="$icone" class="alerte__icone" />

    <div class="alerte__corps">
        @if ($title)
            <strong class="alerte__titre">{{ $title }}</strong>
        @endif
        {{ $slot }}
    </div>

    @if ($dismissible)
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('common.actions.fermer') }}"></button>
    @endif
</div>
