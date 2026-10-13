@component('emails.layout', ['title' => __('emails.rappel_carte.titre')])
    <h1 class="q-texte" style="margin:0 0 16px;font-size:24px;line-height:30px;font-weight:800;letter-spacing:-0.3px;color:#15211C;">{{ __('emails.commun.bonjour', ['nom' => $name]) }}</h1>

    @if ($rang === 1)
        <p class="q-texte" style="margin:0 0 16px;font-size:16px;line-height:26px;color:#15211C;">
            {{ __('emails.rappel_carte.premier') }}
        </p>
    @else
        <p class="q-texte" style="margin:0 0 16px;font-size:16px;line-height:26px;color:#15211C;">
            {{ __('emails.rappel_carte.second') }}
        </p>
    @endif

    <p class="q-texte" style="margin:0 0 16px;font-size:16px;line-height:26px;color:#15211C;">
        {{ __('emails.rappel_carte.gratuit') }}
    </p>

    @include('emails.partials.bouton', ['url' => $activateUrl, 'libelle' => __('emails.rappel_carte.bouton')])
    @include('emails.partials.lien-brut', ['url' => $activateUrl])

    <p class="q-texte2 q-trait" style="margin:24px 0 0;padding-top:16px;border-top:1px solid #E0D5C1;font-size:14px;line-height:22px;color:#56605A;">
        {{ __($rang === 1 ? 'emails.rappel_carte.rang_1' : 'emails.rappel_carte.rang_2') }}
    </p>
@endcomponent
