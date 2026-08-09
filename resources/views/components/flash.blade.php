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
        <x-alert :type="$type">{{ session($key) }}</x-alert>
    @endif
@endforeach
