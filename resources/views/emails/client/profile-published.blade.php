@component('emails.layout', ['title' => __('emails.carte_publiee.titre')])
    <h1 style="margin:0 0 12px;font-size:20px;">{{ __('emails.commun.bonjour', ['nom' => $name]) }}</h1>

    <p style="margin:0 0 16px;line-height:1.5;">
        {{ __('emails.carte_publiee.intro') }}
    </p>

    @include('emails.partials.bouton', ['url' => $publicUrl, 'libelle' => __('emails.carte_publiee.bouton')])

    <p style="margin:0 0 20px;padding:12px 14px;background:#F1F5F9;border-radius:8px;font-size:14px;word-break:break-all;">
        <span style="color:#64748B;font-size:12px;">{{ __('emails.commun.lien_a_partager') }}</span><br>
        <a href="{{ $publicUrl }}" style="color:#0B5D3B;font-weight:bold;">{{ $publicUrl }}</a>
    </p>

    <p style="margin:0 0 16px;line-height:1.5;font-size:14px;">
        {{ __('emails.carte_publiee.telechargements') }}
    </p>

    @include('emails.partials.lien-brut', ['url' => $dashboardUrl])
@endcomponent
