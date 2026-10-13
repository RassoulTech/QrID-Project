@component('emails.layout', ['title' => __('emails.abonnement_expirant.titre')])
    @include('emails.partials.cachet', ['ton' => 'attente', 'libelle' => __('emails.abonnement_expirant.titre')])

    <h1 class="q-texte" style="margin:0 0 16px;font-size:24px;line-height:30px;font-weight:800;letter-spacing:-0.3px;color:#15211C;">{{ __('emails.commun.bonjour', ['nom' => $name]) }}</h1>

    <p class="q-texte" style="margin:0 0 16px;font-size:16px;line-height:26px;color:#15211C;">
        @if ($joursRestants <= 0)
            {!! __('emails.abonnement_expirant.aujourdhui', ['formule' => e($formule)]) !!}
        @elseif ($joursRestants === 1)
            {!! __('emails.abonnement_expirant.demain', ['formule' => e($formule), 'date' => e($echeance)]) !!}
        @else
            {!! __('emails.abonnement_expirant.dans_jours', ['formule' => e($formule), 'jours' => $joursRestants, 'date' => e($echeance)]) !!}
        @endif
    </p>

    <p class="q-texte" style="margin:0 0 16px;font-size:16px;line-height:26px;color:#15211C;">
        {{ __('emails.abonnement_expirant.consequence') }}
    </p>

    <p class="q-texte" style="margin:0 0 16px;font-size:16px;line-height:26px;color:#15211C;">
        {!! __('emails.abonnement_expirant.rien_supprime') !!}
    </p>

    @include('emails.partials.bouton', ['url' => $renewUrl, 'libelle' => __('emails.abonnement_expirant.bouton')])
    @include('emails.partials.lien-brut', ['url' => $renewUrl])
@endcomponent
