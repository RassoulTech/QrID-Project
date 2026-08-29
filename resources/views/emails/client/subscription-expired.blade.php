@component('emails.layout', ['title' => __('emails.abonnement_expire.titre')])
    <h1 style="margin:0 0 12px;font-size:20px;">{{ __('emails.commun.bonjour', ['nom' => $name]) }}</h1>

    <p style="margin:0 0 16px;line-height:1.5;">
        {{ __('emails.abonnement_expire.intro', ['date' => $echeance]) }}
    </p>

    <p style="margin:0 0 20px;padding:12px 14px;background:#F1F5F9;border-radius:8px;line-height:1.5;">
        {!! __('emails.abonnement_expire.intactes') !!}
    </p>

    <p style="margin:0 0 16px;line-height:1.5;">
        {{ __('emails.abonnement_expire.renouveler') }}
    </p>

    @include('emails.partials.bouton', ['url' => $renewUrl, 'libelle' => __('emails.abonnement_expire.bouton')])
    @include('emails.partials.lien-brut', ['url' => $renewUrl])

    @if ($publicUrl)
        <p style="margin:0;font-size:12px;color:#94a3b8;line-height:1.5;word-break:break-all;">
            {{ __('emails.abonnement_expire.adresse_conservee', ['url' => $publicUrl]) }}
        </p>
    @endif
@endcomponent
