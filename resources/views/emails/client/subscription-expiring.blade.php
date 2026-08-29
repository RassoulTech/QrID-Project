@component('emails.layout', ['title' => __('emails.abonnement_expirant.titre')])
    <h1 style="margin:0 0 12px;font-size:20px;">{{ __('emails.commun.bonjour', ['nom' => $name]) }}</h1>

    <p style="margin:0 0 20px;line-height:1.5;">
        @if ($joursRestants <= 0)
            {!! __('emails.abonnement_expirant.aujourdhui', ['formule' => e($formule)]) !!}
        @elseif ($joursRestants === 1)
            {!! __('emails.abonnement_expirant.demain', ['formule' => e($formule), 'date' => e($echeance)]) !!}
        @else
            {!! __('emails.abonnement_expirant.dans_jours', ['formule' => e($formule), 'jours' => $joursRestants, 'date' => e($echeance)]) !!}
        @endif
    </p>

    <p style="margin:0 0 16px;line-height:1.5;">
        {{ __('emails.abonnement_expirant.consequence') }}
    </p>

    <p style="margin:0 0 20px;line-height:1.5;">
        {!! __('emails.abonnement_expirant.rien_supprime') !!}
    </p>

    @include('emails.partials.bouton', ['url' => $renewUrl, 'libelle' => __('emails.abonnement_expirant.bouton')])
    @include('emails.partials.lien-brut', ['url' => $renewUrl])
@endcomponent
