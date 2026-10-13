@component('emails.layout', ['title' => __('emails.confirmation.titre')])
    <h1 class="q-texte" style="margin:0 0 16px;font-size:24px;line-height:30px;font-weight:800;letter-spacing:-0.3px;color:#15211C;">{{ __('emails.commun.bonjour', ['nom' => $name]) }}</h1>

    <p class="q-texte" style="margin:0 0 16px;font-size:16px;line-height:26px;color:#15211C;">
        {{ __('emails.confirmation.intro', ['jours' => $trialDays ?? 15]) }}
    </p>

    @include('emails.partials.bouton', ['url' => $verifyUrl, 'libelle' => __('emails.confirmation.bouton')])

    <p class="q-texte2" style="margin:0 0 12px;font-size:14px;line-height:22px;color:#56605A;">
        {{ __('emails.confirmation.validite', ['minutes' => $ttlMinutes]) }}
    </p>

    @include('emails.partials.lien-brut', ['url' => $verifyUrl])

    <p class="q-texte2 q-trait" style="margin:24px 0 0;padding-top:16px;border-top:1px solid #E0D5C1;font-size:14px;line-height:22px;color:#56605A;">
        {{ __('emails.commun.ignorer') }}
    </p>
@endcomponent
