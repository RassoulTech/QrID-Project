@component('emails.layout', ['title' => 'Paiement confirmé'])
    <h1 style="margin:0 0 12px;font-size:20px;">Bonjour {{ $name }},</h1>

    <p style="margin:0 0 20px;line-height:1.5;">
        Votre paiement de <strong>{{ $montant }} FCFA</strong> est encaissé et
        votre abonnement est actif. Conservez ce message : il vaut reçu.
    </p>

    @include('emails.partials.details', ['lignes' => array_filter([
        'Référence' => $reference,
        'Date' => $date,
        'Formule' => $formule,
        'Moyen de paiement' => $moyen,
        'Montant' => $montant.' FCFA',
        'Valable jusqu\'au' => $echeance,
    ])])

    @if ($publicUrl)
        @include('emails.partials.bouton', ['url' => $publicUrl, 'libelle' => 'Voir ma carte en ligne'])

        <p style="margin:0 0 20px;padding:12px 14px;background:#F1F5F9;border-radius:8px;font-size:14px;word-break:break-all;">
            <span style="color:#64748B;font-size:12px;">Votre lien à partager</span><br>
            <a href="{{ $publicUrl }}" style="color:#0B5D3B;font-weight:bold;">{{ $publicUrl }}</a>
        </p>
    @else
        @include('emails.partials.bouton', ['url' => $dashboardUrl, 'libelle' => 'Ouvrir mon espace'])
    @endif

    <p style="margin:0 0 16px;line-height:1.5;font-size:14px;">
        Votre QR Code et le fichier prêt pour l'impression sont joints à ce
        message. Ils restent également téléchargeables depuis votre espace.
    </p>

    <p style="margin:0;font-size:12px;color:#94a3b8;line-height:1.5;">
        Une question sur ce paiement ? Répondez à ce message en citant la
        référence ci-dessus.
    </p>
@endcomponent
