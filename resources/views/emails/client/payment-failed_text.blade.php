{{ __('emails.commun.bonjour', ['nom' => $name]) }}

{{ __('emails.paiement_echoue.rien_preleve_texte', ['montant' => $montant, 'formule' => $formule]) }}

{{ __('emails.paiement_echoue.causes') }}

{{ __('emails.paiement_echoue.bouton') }} :
{{ $retryUrl }}

{{ __('emails.paiement_echoue.litige') }}

—
{{ config('app.name') }}
