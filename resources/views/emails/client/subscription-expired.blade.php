@component('emails.layout', ['title' => 'Votre carte n\'est plus consultable'])
    <h1 style="margin:0 0 12px;font-size:20px;">Bonjour {{ $name }},</h1>

    <p style="margin:0 0 16px;line-height:1.5;">
        Votre abonnement est arrivé à échéance le {{ $echeance }}. Depuis cette
        date, le lien public de votre carte ne répond plus.
    </p>

    <p style="margin:0 0 20px;padding:12px 14px;background:#F1F5F9;border-radius:8px;line-height:1.5;">
        <strong>Vos données sont intactes.</strong> Rien n'a été supprimé :
        votre carte, vos coordonnées, vos liens et votre QR Code sont conservés.
        Votre adresse publique reste la même, donc les cartes que vous avez
        déjà imprimées ou distribuées redeviendront valables telles quelles.
    </p>

    <p style="margin:0 0 16px;line-height:1.5;">
        Un renouvellement remet tout en ligne en quelques secondes.
    </p>

    @include('emails.partials.bouton', ['url' => $renewUrl, 'libelle' => 'Réactiver ma carte'])

    @include('emails.partials.lien-brut', ['url' => $renewUrl])

    @if ($publicUrl)
        <p style="margin:0;font-size:12px;color:#94a3b8;line-height:1.5;word-break:break-all;">
            Adresse conservée pour votre carte : {{ $publicUrl }}
        </p>
    @endif
@endcomponent
