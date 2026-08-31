@component('emails.layout', ['title' => __('emails.bienvenue.titre')])
    <h1 style="margin:0 0 12px;font-size:20px;">{{ __('emails.commun.bonjour', ['nom' => $name]) }}</h1>

    {{--
        La directive est sur sa propre ligne, et ce n'est pas du confort de
        lecture : Blade ne reconnaît pas un @if collé à un mot — « mot@if »
        reste du texte brut, tandis que le @endif correspondant, lui,
        compile. Le gabarit produit alors un fichier PHP invalide, et
        l'erreur ne se manifeste qu'à l'envoi réel.
    --}}
    <p style="margin:0 0 16px;line-height:1.5;">
        {{ __('emails.bienvenue.essai', ['jours' => $trialDays]) }}
        @if ($trialEndsAt)
            {{ __('emails.bienvenue.essai_fin', ['date' => $trialEndsAt]) }}
        @endif
    </p>

    <p style="margin:0 0 16px;line-height:1.5;">
        {{ __('emails.bienvenue.etape') }}
    </p>

    @include('emails.partials.bouton', ['url' => $createUrl, 'libelle' => __('emails.bienvenue.bouton')])
    @include('emails.partials.lien-brut', ['url' => $createUrl])

    @if ($groupeUrl)
        {{-- LE GROUPE VIENT APRÈS L'ACTION PRINCIPALE, jamais avant.
             Quelqu'un qui vient de confirmer son compte a une seule chose à
             faire : créer sa carte. Lui proposer d'abord de rejoindre un
             groupe le détournerait de l'étape qui donne au produit sa
             valeur. --}}
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
               style="margin:8px 0 20px;background:#F1F5F9;border-radius:8px;">
            <tr>
                <td style="padding:14px 16px;font-size:14px;line-height:1.5;color:#1E293B;">
                    <strong>{{ __('emails.bienvenue.groupe_titre') }}</strong><br>
                    {{ __('emails.bienvenue.groupe_texte') }}
                    <a href="{{ $groupeUrl }}" style="color:#0B3B2E;font-weight:bold;">{{ __('emails.bienvenue.groupe_lien') }}</a>.
                </td>
            </tr>
        </table>
    @endif

    <p style="margin:0;font-size:12px;color:#94a3b8;line-height:1.5;">
        {{ __('emails.bienvenue.sans_paiement') }}
    </p>
@endcomponent
