{{ __('emails.commun.bonjour', ['nom' => $name]) }}

{{ __('emails.paiement_reussi.intro_texte', ['montant' => $montant]) }}

{{ __('emails.paiement_reussi.lignes.reference') }} : {{ $reference }}
{{ __('emails.paiement_reussi.lignes.date') }} : {{ $date }}
{{ __('emails.paiement_reussi.lignes.formule') }} : {{ $formule }}
{{ __('emails.paiement_reussi.lignes.moyen') }} : {{ $moyen }}
{{ __('emails.paiement_reussi.lignes.montant') }} : {{ $montant }} FCFA
@if ($echeance)
{{ __('emails.paiement_reussi.lignes.echeance') }} : {{ $echeance }}
@endif
@if ($publicUrl)

{{ __('emails.commun.lien_a_partager') }} :
{{ $publicUrl }}
@else

{{ __('emails.paiement_reussi.bouton_espace') }} :
{{ $dashboardUrl }}
@endif

{{ __('emails.paiement_reussi.pieces') }}

{{ __('emails.paiement_reussi.question') }}

—
{{ config('app.name') }}
