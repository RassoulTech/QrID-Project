@component('emails.layout', ['title' => 'Votre compte est actif'])
    <h1 style="margin:0 0 12px;font-size:20px;">Bonjour {{ $name }},</h1>

    {{--
      La directive est sur sa propre ligne, et ce n'est pas du confort de
      lecture : Blade ne reconnaît pas un @if collé à un mot — « aujourd'hui@if »
      reste du texte brut, tandis que le @endif correspondant, lui, compile.
      Le gabarit produit alors un fichier PHP invalide, et l'erreur ne se
      manifeste qu'à l'envoi réel.
    --}}
    <p style="margin:0 0 16px;line-height:1.5;">
        Votre adresse est confirmée et votre compte est actif. Votre essai
        gratuit de {{ $trialDays }} jours a démarré aujourd'hui.
        @if ($trialEndsAt)
            Il court jusqu'au <strong>{{ $trialEndsAt }}</strong>.
        @endif
    </p>

    <p style="margin:0 0 16px;line-height:1.5;">
        Il reste une étape : créer votre carte. Comptez cinq minutes — nom,
        fonction, coordonnées, et le choix d'un modèle. Vous obtenez ensuite un
        lien et un QR Code à partager immédiatement.
    </p>

    @include('emails.partials.bouton', ['url' => $createUrl, 'libelle' => 'Créer ma carte'])

    @include('emails.partials.lien-brut', ['url' => $createUrl])

    <p style="margin:0;font-size:12px;color:#94a3b8;line-height:1.5;">
        Pendant l'essai, votre carte est publiable et consultable sans aucun
        paiement. Aucun moyen de paiement ne vous est demandé.
    </p>
@endcomponent
