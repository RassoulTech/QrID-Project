@component('emails.layout', ['title' => __('emails.mot_de_passe_change.titre')])
    @include('emails.partials.cachet', ['ton' => 'attente', 'libelle' => __('emails.mot_de_passe_change.titre')])

    <h1 class="q-texte" style="margin:0 0 16px;font-size:24px;line-height:30px;font-weight:800;letter-spacing:-0.3px;color:#15211C;">{{ __('emails.commun.bonjour', ['nom' => $name]) }}</h1>

    <p class="q-texte" style="margin:0 0 16px;font-size:16px;line-height:26px;color:#15211C;">
        {!! __('emails.mot_de_passe_change.intro', ['date' => e($date)]) !!}
    </p>

    <p class="q-texte" style="margin:0 0 16px;font-size:16px;line-height:26px;color:#15211C;">
        {!! __('emails.mot_de_passe_change.si_vous') !!}
    </p>

    <p class="q-texte" style="margin:0 0 8px;font-size:16px;line-height:26px;color:#15211C;">
        {!! __('emails.mot_de_passe_change.sinon') !!}
    </p>

    @include('emails.partials.bouton', [
        'url' => $resetUrl,
        'libelle' => __('emails.mot_de_passe_change.bouton'),
        'ton' => 'sombre',
    ])
    @include('emails.partials.lien-brut', ['url' => $resetUrl])

    @if ($ip)
        <p class="q-texte2" style="margin:0 0 12px;font-size:14px;line-height:22px;color:#56605A;">
            {{ __('emails.mot_de_passe_change.ip', ['ip' => $ip]) }}
        </p>
    @endif

    <p class="q-texte2 q-trait" style="margin:24px 0 0;padding-top:16px;border-top:1px solid #E0D5C1;font-size:14px;line-height:22px;color:#56605A;">
        {{ __('emails.mot_de_passe_change.toujours_envoye') }}
    </p>
@endcomponent
