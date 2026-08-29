{{ $motif->estUrgent() ? __('emails.alerte.action_requise') : __('emails.alerte.pour_information') }}
{{ $motif->titre() }}

@foreach ($lignes as $libelle => $valeur)
{{ $libelle }} : {{ $valeur }}
@endforeach
@if ($url)

{{ __('emails.alerte.bouton') }} :
{{ $url }}
@endif

{{ __('emails.alerte.automatique') }}

—
{{ config('app.name') }}
