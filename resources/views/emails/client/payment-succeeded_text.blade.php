Bonjour {{ $name }},

Votre paiement de {{ $montant }} FCFA est encaissé et votre abonnement est actif. Conservez ce message : il vaut reçu.

Référence : {{ $reference }}
Date : {{ $date }}
Formule : {{ $formule }}
Moyen de paiement : {{ $moyen }}
Montant : {{ $montant }} FCFA
@if ($echeance)
Valable jusqu'au : {{ $echeance }}
@endif

@if ($publicUrl)
Votre lien à partager :
{{ $publicUrl }}
@else
Votre espace :
{{ $dashboardUrl }}
@endif

Votre QR Code et le fichier prêt pour l'impression sont joints à ce message. Ils restent également téléchargeables depuis votre espace.

Une question sur ce paiement ? Répondez à ce message en citant la référence ci-dessus.

—
{{ config('app.name') }}
