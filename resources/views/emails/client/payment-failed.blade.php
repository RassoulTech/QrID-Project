@component('emails.layout', ['title' => __('emails.paiement_echoue.titre')])
    @include('emails.partials.cachet', ['ton' => 'echec', 'libelle' => __('emails.paiement_echoue.titre')])

    <h1 class="q-texte" style="margin:0 0 16px;font-size:24px;line-height:30px;font-weight:800;letter-spacing:-0.3px;color:#15211C;">{{ __('emails.commun.bonjour', ['nom' => $name]) }}</h1>

    {{-- Le premier mot est rassurant : rien n'a été prélevé. L'encadré
         sable le détache du reste sans le colorer d'une alerte. --}}
    @component('emails.partials.panneau', ['ton' => 'neutre', 'filet' => true])
        {!! __('emails.paiement_echoue.rien_preleve', ['montant' => e($montant), 'formule' => e($formule)]) !!}
    @endcomponent

    <p class="q-texte" style="margin:0 0 16px;font-size:16px;line-height:26px;color:#15211C;">
        {{ __('emails.paiement_echoue.causes') }}
    </p>

    @include('emails.partials.bouton', ['url' => $retryUrl, 'libelle' => __('emails.paiement_echoue.bouton')])
    @include('emails.partials.lien-brut', ['url' => $retryUrl])

    <p class="q-texte2 q-trait" style="margin:24px 0 0;padding-top:16px;border-top:1px solid #E0D5C1;font-size:14px;line-height:22px;color:#56605A;">
        {{ __('emails.paiement_echoue.litige') }}
    </p>
@endcomponent
