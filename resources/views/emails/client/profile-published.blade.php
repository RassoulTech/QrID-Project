@component('emails.layout', ['title' => __('emails.carte_publiee.titre')])
    @include('emails.partials.cachet', ['ton' => 'ok', 'libelle' => __('emails.carte_publiee.titre')])

    <h1 class="q-texte" style="margin:0 0 16px;font-size:24px;line-height:30px;font-weight:800;letter-spacing:-0.3px;color:#15211C;">{{ __('emails.commun.bonjour', ['nom' => $name]) }}</h1>

    <p class="q-texte" style="margin:0 0 16px;font-size:16px;line-height:26px;color:#15211C;">
        {{ __('emails.carte_publiee.intro') }}
    </p>

    @include('emails.partials.bouton', ['url' => $publicUrl, 'libelle' => __('emails.carte_publiee.bouton')])

    @component('emails.partials.panneau', ['ton' => 'neutre', 'surtitre' => __('emails.commun.lien_a_partager')])
        <a href="{{ $publicUrl }}" class="q-lien" style="color:#146A50;font-weight:700;text-decoration:underline;word-break:break-word;overflow-wrap:anywhere;">{{ $publicUrl }}</a>
    @endcomponent

    <p class="q-texte" style="margin:0 0 16px;font-size:16px;line-height:26px;color:#15211C;">
        {{ __('emails.carte_publiee.telechargements') }}
    </p>

    @include('emails.partials.lien-brut', ['url' => $dashboardUrl])
@endcomponent
