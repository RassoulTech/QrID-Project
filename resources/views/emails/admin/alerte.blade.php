@component('emails.layout', ['title' => $motif->titre()])
    {{-- Urgente : le panneau prend la teinte « échoué » de la direction A.
         Informative : le sable neutre. Le SURTITRE dit lequel des deux —
         la couleur ne fait que le confirmer. --}}
    @component('emails.partials.panneau', [
        'ton' => $motif->estUrgent() ? 'echec' : 'neutre',
        'surtitre' => $motif->estUrgent() ? __('emails.alerte.action_requise') : __('emails.alerte.pour_information'),
        'titre' => $motif->titre(),
    ])
    @endcomponent

    @include('emails.partials.details', ['lignes' => $lignes])

    @if ($url)
        @include('emails.partials.bouton', [
            'url' => $url,
            'libelle' => __('emails.alerte.bouton'),
            'ton' => $motif->estUrgent() ? 'sombre' : 'vert',
        ])
    @endif

    <p class="q-texte2 q-trait" style="margin:24px 0 0;padding-top:16px;border-top:1px solid #E0D5C1;font-size:14px;line-height:22px;color:#56605A;">
        {{ __('emails.alerte.automatique') }}
    </p>
@endcomponent
