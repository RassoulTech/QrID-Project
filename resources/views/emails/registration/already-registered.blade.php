@component('emails.layout', ['title' => __('emails.deja_inscrit.titre')])
    <h1 style="margin:0 0 12px;font-size:20px;">{{ __('emails.deja_inscrit.titre') }}</h1>

    <p style="margin:0 0 16px;line-height:1.5;">
        {!! __('emails.deja_inscrit.intro', ['marque' => e(config('app.name'))]) !!}
    </p>

    <p style="margin:0 0 16px;line-height:1.5;">
        {{ __('emails.deja_inscrit.si_vous') }}
    </p>

    <p style="margin:0 0 20px;" align="center">
        <a href="{{ $loginUrl }}"
           style="display:inline-block;background:#0B5D3B;color:#ffffff;text-decoration:none;padding:14px 26px;border-radius:8px;font-weight:bold;font-size:16px;">
            {{ __('emails.deja_inscrit.bouton') }}
        </a>
    </p>

    <p style="margin:0 0 8px;font-size:14px;line-height:1.5;">
        {{ __('emails.deja_inscrit.oubli') }}
        <a href="{{ $resetUrl }}" style="color:#0B5D3B;">{{ __('emails.deja_inscrit.oubli_lien') }}</a>.
    </p>

    <p style="margin:16px 0 0;font-size:12px;color:#94a3b8;line-height:1.5;">
        {{ __('emails.deja_inscrit.ignorer') }}
    </p>
@endcomponent
