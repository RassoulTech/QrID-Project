@component('emails.layout', ['title' => __('emails.abonnement_expire.titre')])
    @include('emails.partials.cachet', ['ton' => 'echec', 'libelle' => __('emails.abonnement_expire.titre')])

    <h1 class="q-texte" style="margin:0 0 16px;font-size:24px;line-height:30px;font-weight:800;letter-spacing:-0.3px;color:#15211C;">{{ __('emails.commun.bonjour', ['nom' => $name]) }}</h1>

    <p class="q-texte" style="margin:0 0 16px;font-size:16px;line-height:26px;color:#15211C;">
        {{ __('emails.abonnement_expire.intro', ['date' => $echeance]) }}
    </p>

    {{-- La nouvelle rassurante, détachée en vert « réussi » : rien n'est perdu. --}}
    @component('emails.partials.panneau', ['ton' => 'ok'])
        {!! __('emails.abonnement_expire.intactes') !!}
    @endcomponent

    <p class="q-texte" style="margin:0 0 16px;font-size:16px;line-height:26px;color:#15211C;">
        {{ __('emails.abonnement_expire.renouveler') }}
    </p>

    @include('emails.partials.bouton', ['url' => $renewUrl, 'libelle' => __('emails.abonnement_expire.bouton')])
    @include('emails.partials.lien-brut', ['url' => $renewUrl])

    @if ($publicUrl)
        <p class="q-texte2 q-trait" style="margin:24px 0 0;padding-top:16px;border-top:1px solid #E0D5C1;font-size:14px;line-height:22px;color:#56605A;word-break:break-word;overflow-wrap:anywhere;">
            {{ __('emails.abonnement_expire.adresse_conservee', ['url' => $publicUrl]) }}
        </p>
    @endif
@endcomponent
