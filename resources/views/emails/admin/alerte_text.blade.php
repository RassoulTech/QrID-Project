{{ $motif->estUrgent() ? 'ACTION REQUISE' : 'POUR INFORMATION' }}
{{ $motif->titre() }}

@foreach ($lignes as $libelle => $valeur)
{{ $libelle }} : {{ $valeur }}
@endforeach
@if ($url)

Ouvrir dans l'administration :
{{ $url }}
@endif

Message automatique destiné à l'équipe. Il n'a pas été envoyé au client.

—
{{ config('app.name') }}
