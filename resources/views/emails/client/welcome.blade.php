@component('emails.layout', ['title' => 'Votre compte est actif'])
    <h1 style="margin:0 0 12px;font-size:20px;">Bonjour {{ $name }},</h1>

    {{--
      La directive est sur sa propre ligne, et ce n'est pas du confort de
      lecture : Blade ne reconnaît pas un @if collé à un mot — « aujourd'hui@if »
      reste du texte brut, tandis que le @endif correspondant, lui, compile.
      Le gabarit produit alors un fichier PHP invalide, et l'erreur ne se
      manifeste qu'à l'envoi réel.
    --}}
    <p style="margin:0 0 16px;line-height:1.5;">
        Votre adresse est confirmée et votre compte est actif. Votre essai
        gratuit de {{ $trialDays }} jours a démarré aujourd'hui.
        @if ($trialEndsAt)
            Il court jusqu'au <strong>{{ $trialEndsAt }}</strong>.
        @endif
    </p>

    <p style="margin:0 0 16px;line-height:1.5;">
        Il reste une étape : créer votre carte. Comptez cinq minutes — nom,
        fonction, coordonnées, et le choix d'un modèle. Vous obtenez ensuite un
        lien et un QR Code à partager immédiatement.
    </p>

    @include('emails.partials.bouton', ['url' => $createUrl, 'libelle' => 'Créer ma carte'])

    @include('emails.partials.lien-brut', ['url' => $createUrl])

    @if ($groupeUrl)
        {{-- LE GROUPE VIENT APRÈS L'ACTION PRINCIPALE, jamais avant.
             Quelqu'un qui vient de confirmer son compte a une seule chose à
             faire : créer sa carte. Lui proposer d'abord de rejoindre un
             groupe le détournerait de l'étape qui donne au produit sa valeur. --}}
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
               style="margin:8px 0 20px;background:#F1F5F9;border-radius:8px;">
            <tr>
                <td style="padding:14px 16px;font-size:14px;line-height:1.5;color:#1E293B;">
                    <strong>Un groupe WhatsApp est réservé à nos clients.</strong><br>
                    Entraide, questions, et réponses rapides de notre équipe.
                    <a href="{{ $groupeUrl }}" style="color:#0B5D3B;font-weight:bold;">Rejoindre le groupe</a>.
                </td>
            </tr>
        </table>
    @endif

    <p style="margin:0;font-size:12px;color:#94a3b8;line-height:1.5;">
        Pendant l'essai, votre carte est publiable et consultable sans aucun
        paiement. Aucun moyen de paiement ne vous est demandé.
    </p>
@endcomponent
