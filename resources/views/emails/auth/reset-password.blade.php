@component('emails.layout', ['title' => __('emails.reinitialisation.titre')])
    <h1 class="q-texte" style="margin:0 0 16px;font-size:24px;line-height:30px;font-weight:800;letter-spacing:-0.3px;color:#15211C;">{{ __('emails.reinitialisation.titre') }}</h1>

    <p class="q-texte" style="margin:0 0 16px;font-size:16px;line-height:26px;color:#15211C;">
        {{ __('emails.reinitialisation.intro', ['marque' => config('app.name')]) }}
    </p>

    @include('emails.partials.bouton', ['url' => $resetUrl, 'libelle' => __('emails.reinitialisation.bouton')])

    <p class="q-texte2" style="margin:0 0 12px;font-size:14px;line-height:22px;color:#56605A;">
        {{ __('emails.reinitialisation.validite', ['minutes' => $ttlMinutes]) }}
    </p>

    @include('emails.partials.lien-brut', ['url' => $resetUrl])

    <p class="q-texte2 q-trait" style="margin:24px 0 0;padding-top:16px;border-top:1px solid #E0D5C1;font-size:14px;line-height:22px;color:#56605A;">
        {{ __('emails.reinitialisation.ignorer') }}
    </p>
@endcomponent
