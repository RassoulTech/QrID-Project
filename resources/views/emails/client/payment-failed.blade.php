@component('emails.layout', ['title' => 'Paiement non abouti'])
    <h1 style="margin:0 0 12px;font-size:20px;">Bonjour {{ $name }},</h1>

    <p style="margin:0 0 16px;padding:12px 14px;background:#F1F5F9;border-radius:8px;line-height:1.5;">
        <strong>Aucune somme n'a été prélevée.</strong> Votre paiement de
        {{ $montant }} FCFA pour la formule {{ $formule }} n'est pas allé à son
        terme, et votre abonnement n'a pas été modifié.
    </p>

    <p style="margin:0 0 16px;line-height:1.5;">
        Cela arrive le plus souvent pour une raison simple : solde insuffisant
        au moment de l'opération, code de confirmation non saisi à temps, ou
        page fermée avant la fin. Vous pouvez réessayer immédiatement.
    </p>

    @include('emails.partials.bouton', ['url' => $retryUrl, 'libelle' => 'Réessayer le paiement'])

    @include('emails.partials.lien-brut', ['url' => $retryUrl])

    <p style="margin:0;font-size:12px;color:#94a3b8;line-height:1.5;">
        Si une somme apparaissait malgré tout sur votre compte, répondez à ce
        message : nous la retrouvons et nous la traitons.
    </p>
@endcomponent
