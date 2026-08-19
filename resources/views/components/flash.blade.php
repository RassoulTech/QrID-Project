{{-- Affichage global des messages flash de session. --}}
@php
    $flashMap = [
        'success' => 'success',
        'error'   => 'danger',
        'danger'  => 'danger',
        'warning' => 'warning',
        'status'  => 'info',
        'info'    => 'info',
    ];
@endphp

@foreach ($flashMap as $key => $type)
    @if (session()->has($key))
        {{-- data-flash-auto : SEULS ces messages s'effacent au bout de 30 s.
             Les erreurs de validation de champ restent, elles designent une
             correction a faire. --}}
        <x-alert :type="$type" data-flash-auto>{{ session($key) }}</x-alert>
    @endif
@endforeach
