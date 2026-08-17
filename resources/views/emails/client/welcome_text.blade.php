Bonjour {{ $name }},

{{-- La directive doit débuter la ligne : Blade ignore un @if collé à un mot. --}}
Votre adresse est confirmée et votre compte est actif. Votre essai gratuit de {{ $trialDays }} jours a démarré aujourd'hui.
@if ($trialEndsAt)
Il court jusqu'au {{ $trialEndsAt }}.
@endif

Il reste une étape : créer votre carte. Comptez cinq minutes — nom, fonction, coordonnées, et le choix d'un modèle. Vous obtenez ensuite un lien et un QR Code à partager immédiatement.

Créer ma carte :
{{ $createUrl }}
@if ($groupeUrl)

Un groupe WhatsApp est réservé à nos clients — entraide, questions, et réponses rapides de notre équipe :
{{ $groupeUrl }}
@endif

Pendant l'essai, votre carte est publiable et consultable sans aucun paiement. Aucun moyen de paiement ne vous est demandé.

—
{{ config('app.name') }}
