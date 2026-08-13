@component('emails.layout', ['title' => 'Votre abonnement arrive à échéance'])
    <h1 style="margin:0 0 12px;font-size:20px;">Bonjour {{ $name }},</h1>

    <p style="margin:0 0 20px;line-height:1.5;">
        @if ($joursRestants <= 0)
            Votre abonnement {{ $formule }} se termine <strong>aujourd'hui</strong>.
        @elseif ($joursRestants === 1)
            Votre abonnement {{ $formule }} se termine <strong>demain</strong>,
            le {{ $echeance }}.
        @else
            Votre abonnement {{ $formule }} se termine dans
            <strong>{{ $joursRestants }} jours</strong>, le {{ $echeance }}.
        @endif
    </p>

    <p style="margin:0 0 16px;line-height:1.5;">
        Passé cette date, le lien public de votre carte cessera de répondre :
        les personnes qui l'ouvriront, ou qui scanneront votre QR Code, ne
        verront plus vos coordonnées.
    </p>

    <p style="margin:0 0 20px;line-height:1.5;">
        <strong>Rien n'est supprimé.</strong> Votre carte, vos coordonnées et
        votre lien sont conservés en l'état. Un renouvellement les remet en
        ligne immédiatement, sans rien ressaisir et sans changer d'adresse —
        les cartes déjà imprimées restent valables.
    </p>

    @include('emails.partials.bouton', ['url' => $renewUrl, 'libelle' => 'Renouveler mon abonnement'])

    @include('emails.partials.lien-brut', ['url' => $renewUrl])
@endcomponent
