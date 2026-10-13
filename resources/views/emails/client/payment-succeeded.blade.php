@component('emails.layout', ['title' => __('emails.paiement_reussi.titre')])
    @include('emails.partials.cachet', ['ton' => 'ok', 'libelle' => __('emails.paiement_reussi.titre')])

    <h1 class="q-texte" style="margin:0 0 16px;font-size:24px;line-height:30px;font-weight:800;letter-spacing:-0.3px;color:#15211C;">{{ __('emails.commun.bonjour', ['nom' => $name]) }}</h1>

    <p class="q-texte" style="margin:0 0 20px;font-size:16px;line-height:26px;color:#15211C;">
        {!! __('emails.paiement_reussi.intro', ['montant' => e($montant)]) !!}
    </p>

    @include('emails.partials.details', ['lignes' => array_filter([
        __('emails.paiement_reussi.lignes.reference') => $reference,
        __('emails.paiement_reussi.lignes.date') => $date,
        __('emails.paiement_reussi.lignes.formule') => $formule,
        __('emails.paiement_reussi.lignes.moyen') => $moyen,
        __('emails.paiement_reussi.lignes.montant') => $montant.' FCFA',
        __('emails.paiement_reussi.lignes.echeance') => $echeance,
    ])])

    @if ($publicUrl)
        @include('emails.partials.bouton', ['url' => $publicUrl, 'libelle' => __('emails.paiement_reussi.bouton_carte')])

        @component('emails.partials.panneau', ['ton' => 'neutre', 'surtitre' => __('emails.commun.lien_a_partager')])
            <a href="{{ $publicUrl }}" class="q-lien" style="color:#146A50;font-weight:700;text-decoration:underline;word-break:break-word;overflow-wrap:anywhere;">{{ $publicUrl }}</a>
        @endcomponent
    @else
        @include('emails.partials.bouton', ['url' => $dashboardUrl, 'libelle' => __('emails.paiement_reussi.bouton_espace')])
    @endif

    <p class="q-texte" style="margin:0 0 16px;font-size:16px;line-height:26px;color:#15211C;">
        {{ __('emails.paiement_reussi.pieces') }}
    </p>

    <p class="q-texte2 q-trait" style="margin:24px 0 0;padding-top:16px;border-top:1px solid #E0D5C1;font-size:14px;line-height:22px;color:#56605A;">
        {{ __('emails.paiement_reussi.question') }}
    </p>
@endcomponent
