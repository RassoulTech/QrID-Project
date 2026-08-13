Bonjour {{ $name }},

@if ($joursRestants <= 0)
Votre abonnement {{ $formule }} se termine AUJOURD'HUI.
@elseif ($joursRestants === 1)
Votre abonnement {{ $formule }} se termine DEMAIN, le {{ $echeance }}.
@else
Votre abonnement {{ $formule }} se termine dans {{ $joursRestants }} jours, le {{ $echeance }}.
@endif

Passé cette date, le lien public de votre carte cessera de répondre : les personnes qui l'ouvriront, ou qui scanneront votre QR Code, ne verront plus vos coordonnées.

RIEN N'EST SUPPRIMÉ. Votre carte, vos coordonnées et votre lien sont conservés en l'état. Un renouvellement les remet en ligne immédiatement, sans rien ressaisir et sans changer d'adresse — les cartes déjà imprimées restent valables.

Renouveler mon abonnement :
{{ $renewUrl }}

—
{{ config('app.name') }}
