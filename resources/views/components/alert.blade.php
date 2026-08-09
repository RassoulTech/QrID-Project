{{--
  x-alert — message contextuel.

  <x-alert type="success">Votre profil a été publié.</x-alert>
  <x-alert type="danger" :dismissible="false" title="Paiement refusé">
      Vérifiez votre solde puis réessayez.
  </x-alert>

  Types : success, info, warning, danger
  Props : type, dismissible, title
  Une icône SVG cohérente est ajoutée automatiquement selon le type.
--}}
@props([
    'type' => 'info',
    'dismissible' => true,
    'title' => null,
])

@php
    $role = in_array($type, ['danger', 'warning']) ? 'alert' : 'status';
@endphp

<div
    {{ $attributes->merge(['class' => 'alert alert-'.$type.($dismissible ? ' alert-dismissible fade show' : '').' d-flex gap-2']) }}
    role="{{ $role }}"
>
    <span class="flex-shrink-0 pt-1" aria-hidden="true">
        @switch($type)
            @case('success')
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05"/></svg>
                @break
            @case('danger')
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/><path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/></svg>
                @break
            @case('warning')
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5m.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/></svg>
                @break
            @default
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2"/></svg>
        @endswitch
    </span>

    <div class="flex-grow-1">
        @if ($title)
            <strong class="d-block">{{ $title }}</strong>
        @endif
        {{ $slot }}
    </div>

    @if ($dismissible)
        <button type="button" class="btn-close flex-shrink-0" data-bs-dismiss="alert" aria-label="Fermer"></button>
    @endif
</div>
