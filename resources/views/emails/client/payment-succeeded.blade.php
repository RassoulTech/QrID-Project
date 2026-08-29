@component('emails.layout', ['title' => __('emails.paiement_reussi.titre')])
    <h1 style="margin:0 0 12px;font-size:20px;">{{ __('emails.commun.bonjour', ['nom' => $name]) }}</h1>

    <p style="margin:0 0 20px;line-height:1.5;">
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

        <p style="margin:0 0 20px;padding:12px 14px;background:#F1F5F9;border-radius:8px;font-size:14px;word-break:break-all;">
            <span style="color:#64748B;font-size:12px;">{{ __('emails.commun.lien_a_partager') }}</span><br>
            <a href="{{ $publicUrl }}" style="color:#0B5D3B;font-weight:bold;">{{ $publicUrl }}</a>
        </p>
    @else
        @include('emails.partials.bouton', ['url' => $dashboardUrl, 'libelle' => __('emails.paiement_reussi.bouton_espace')])
    @endif

    <p style="margin:0 0 16px;line-height:1.5;font-size:14px;">
        {{ __('emails.paiement_reussi.pieces') }}
    </p>

    <p style="margin:0;font-size:12px;color:#94a3b8;line-height:1.5;">
        {{ __('emails.paiement_reussi.question') }}
    </p>
@endcomponent
