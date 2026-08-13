Bonjour {{ $name }},

Votre abonnement est arrivé à échéance le {{ $echeance }}. Depuis cette date, le lien public de votre carte ne répond plus.

VOS DONNÉES SONT INTACTES. Rien n'a été supprimé : votre carte, vos coordonnées, vos liens et votre QR Code sont conservés. Votre adresse publique reste la même, donc les cartes que vous avez déjà imprimées ou distribuées redeviendront valables telles quelles.

Un renouvellement remet tout en ligne en quelques secondes.

Réactiver ma carte :
{{ $renewUrl }}
@if ($publicUrl)

Adresse conservée pour votre carte : {{ $publicUrl }}
@endif

—
{{ config('app.name') }}
