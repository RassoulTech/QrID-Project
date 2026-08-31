@component('emails.layout', ['title' => __('emails.confirmation.titre')])
    <h1 style="margin:0 0 12px;font-size:20px;">{{ __('emails.commun.bonjour', ['nom' => $name]) }}</h1>

    <p style="margin:0 0 16px;line-height:1.5;">
        {{ __('emails.confirmation.intro', ['jours' => $trialDays ?? 15]) }}
    </p>

    <p style="margin:0 0 24px;" align="center">
        <a href="{{ $verifyUrl }}"
           style="display:inline-block;background:#0B3B2E;color:#ffffff;text-decoration:none;padding:14px 26px;border-radius:8px;font-weight:bold;font-size:16px;">
            {{ __('emails.confirmation.bouton') }}
        </a>
    </p>

    <p style="margin:0 0 8px;font-size:13px;color:#64748b;line-height:1.5;">
        {{ __('emails.confirmation.validite', ['minutes' => $ttlMinutes]) }}
    </p>

    <p style="margin:0 0 16px;font-size:13px;color:#64748b;word-break:break-all;">
        {{ __('emails.commun.lien_brut') }}<br>
        <a href="{{ $verifyUrl }}" style="color:#0B3B2E;">{{ $verifyUrl }}</a>
    </p>

    <p style="margin:0;font-size:12px;color:#94a3b8;line-height:1.5;">
        {{ __('emails.commun.ignorer') }}
    </p>
@endcomponent
