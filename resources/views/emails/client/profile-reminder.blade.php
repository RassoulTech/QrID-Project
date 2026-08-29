@component('emails.layout', ['title' => __('emails.rappel_carte.titre')])
    <h1 style="margin:0 0 12px;font-size:20px;">{{ __('emails.commun.bonjour', ['nom' => $name]) }}</h1>

    @if ($rang === 1)
        <p style="margin:0 0 16px;line-height:1.5;">
            {{ __('emails.rappel_carte.premier') }}
        </p>
    @else
        <p style="margin:0 0 16px;line-height:1.5;">
            {{ __('emails.rappel_carte.second') }}
        </p>
    @endif

    <p style="margin:0 0 16px;line-height:1.5;">
        {{ __('emails.rappel_carte.gratuit') }}
    </p>

    @include('emails.partials.bouton', ['url' => $activateUrl, 'libelle' => __('emails.rappel_carte.bouton')])
    @include('emails.partials.lien-brut', ['url' => $activateUrl])

    <p style="margin:0;font-size:12px;color:#94a3b8;line-height:1.5;">
        {{ __($rang === 1 ? 'emails.rappel_carte.rang_1' : 'emails.rappel_carte.rang_2') }}
    </p>
@endcomponent
