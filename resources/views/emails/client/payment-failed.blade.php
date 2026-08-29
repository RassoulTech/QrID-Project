@component('emails.layout', ['title' => __('emails.paiement_echoue.titre')])
    <h1 style="margin:0 0 12px;font-size:20px;">{{ __('emails.commun.bonjour', ['nom' => $name]) }}</h1>

    <p style="margin:0 0 16px;padding:12px 14px;background:#F1F5F9;border-radius:8px;line-height:1.5;">
        {!! __('emails.paiement_echoue.rien_preleve', ['montant' => e($montant), 'formule' => e($formule)]) !!}
    </p>

    <p style="margin:0 0 16px;line-height:1.5;">
        {{ __('emails.paiement_echoue.causes') }}
    </p>

    @include('emails.partials.bouton', ['url' => $retryUrl, 'libelle' => __('emails.paiement_echoue.bouton')])
    @include('emails.partials.lien-brut', ['url' => $retryUrl])

    <p style="margin:0;font-size:12px;color:#94a3b8;line-height:1.5;">
        {{ __('emails.paiement_echoue.litige') }}
    </p>
@endcomponent
