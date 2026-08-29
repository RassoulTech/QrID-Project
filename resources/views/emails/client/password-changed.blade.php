@component('emails.layout', ['title' => __('emails.mot_de_passe_change.titre')])
    <h1 style="margin:0 0 12px;font-size:20px;">{{ __('emails.commun.bonjour', ['nom' => $name]) }}</h1>

    <p style="margin:0 0 20px;line-height:1.5;">
        {!! __('emails.mot_de_passe_change.intro', ['date' => e($date)]) !!}
    </p>

    <p style="margin:0 0 20px;line-height:1.5;">
        {!! __('emails.mot_de_passe_change.si_vous') !!}
    </p>

    <p style="margin:0 0 8px;line-height:1.5;">
        {!! __('emails.mot_de_passe_change.sinon') !!}
    </p>

    @include('emails.partials.bouton', [
        'url' => $resetUrl,
        'libelle' => __('emails.mot_de_passe_change.bouton'),
        'ton' => 'sombre',
    ])
    @include('emails.partials.lien-brut', ['url' => $resetUrl])

    @if ($ip)
        <p style="margin:0 0 12px;font-size:12px;color:#64748b;">
            {{ __('emails.mot_de_passe_change.ip', ['ip' => $ip]) }}
        </p>
    @endif

    <p style="margin:0;font-size:12px;color:#94a3b8;line-height:1.5;">
        {{ __('emails.mot_de_passe_change.toujours_envoye') }}
    </p>
@endcomponent
