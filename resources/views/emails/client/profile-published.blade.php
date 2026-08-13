@component('emails.layout', ['title' => 'Votre carte est en ligne'])
    <h1 style="margin:0 0 12px;font-size:20px;">Bonjour {{ $name }},</h1>

    <p style="margin:0 0 16px;line-height:1.5;">
        Votre carte est publiée. Toute personne qui ouvre le lien ci-dessous
        voit vos coordonnées et peut les enregistrer dans son téléphone en un
        geste.
    </p>

    @include('emails.partials.bouton', ['url' => $publicUrl, 'libelle' => 'Voir ma carte'])

    <p style="margin:0 0 20px;padding:12px 14px;background:#F1F5F9;border-radius:8px;font-size:14px;word-break:break-all;">
        <span style="color:#64748B;font-size:12px;">Votre lien à partager</span><br>
        <a href="{{ $publicUrl }}" style="color:#0B5D3B;font-weight:bold;">{{ $publicUrl }}</a>
    </p>

    <p style="margin:0 0 16px;line-height:1.5;font-size:14px;">
        Depuis votre espace, vous pouvez télécharger le QR Code de cette carte
        et le fichier prêt pour l'impression.
    </p>

    @include('emails.partials.lien-brut', ['url' => $dashboardUrl])
@endcomponent
