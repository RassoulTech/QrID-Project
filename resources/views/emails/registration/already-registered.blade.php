@component('emails.layout', ['title' => __('emails.deja_inscrit.titre')])
    <h1 class="q-texte" style="margin:0 0 16px;font-size:24px;line-height:30px;font-weight:800;letter-spacing:-0.3px;color:#15211C;">{{ __('emails.deja_inscrit.titre') }}</h1>

    <p class="q-texte" style="margin:0 0 16px;font-size:16px;line-height:26px;color:#15211C;">
        {!! __('emails.deja_inscrit.intro', ['marque' => e(config('app.name'))]) !!}
    </p>

    <p class="q-texte" style="margin:0 0 8px;font-size:16px;line-height:26px;color:#15211C;">
        {{ __('emails.deja_inscrit.si_vous') }}
    </p>

    @include('emails.partials.bouton', ['url' => $loginUrl, 'libelle' => __('emails.deja_inscrit.bouton')])

    <p class="q-texte" style="margin:0 0 8px;font-size:16px;line-height:26px;color:#15211C;">
        {{ __('emails.deja_inscrit.oubli') }}
        <a href="{{ $resetUrl }}" class="q-lien" style="color:#146A50;font-weight:700;text-decoration:underline;">{{ __('emails.deja_inscrit.oubli_lien') }}</a>.
    </p>

    <p class="q-texte2 q-trait" style="margin:24px 0 0;padding-top:16px;border-top:1px solid #E0D5C1;font-size:14px;line-height:22px;color:#56605A;">
        {{ __('emails.deja_inscrit.ignorer') }}
    </p>
@endcomponent
