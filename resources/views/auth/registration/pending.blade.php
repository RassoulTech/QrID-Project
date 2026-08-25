{{-- ACTION PRINCIPALE : ouvrir sa boîte mail et suivre le lien reçu. --}}
<x-auth-layout
    :title="__('auth.pending.titre')"
    aside-tone="light"
    :aside-title="__('auth.pending.aside_titre')"
    :aside-text="__('auth.pending.aside_texte')"
    :aside-step="3">

    {{-- Visuel de CETTE page : carte verte « e-mail envoyé » et illustration
         de scène (la photographie de la maquette, remplacée en CSS). --}}
    <x-slot:aside>
        <div class="av-pile">
            <x-visual.badge-card
                icon="enveloppe"
                :title="__('auth.pending.visuel_titre')"
                :text="__('auth.pending.visuel_texte')" />

            <x-visual.portrait variant="scene" />
        </div>
    </x-slot:aside>

    <span class="mail-icon" aria-hidden="true">
        <svg width="26" height="26" viewBox="0 0 16 16" fill="currentColor">
            <path d="M.05 3.555A2 2 0 0 1 2 2h12a2 2 0 0 1 1.95 1.555L8 8.414zM0 4.697v7.104l5.803-3.558zM6.761 8.83l-6.57 4.027A2 2 0 0 0 2 14h12a2 2 0 0 0 1.808-1.144l-6.57-4.027L8 9.586zm3.436-.586L16 11.801V4.697z"/>
        </svg>
    </span>

    <h1 class="auth__title">{{ __('auth.pending.titre') }}</h1>

    {{--
      FORMULATION VALABLE DANS LES TROIS CAS, sans en révéler aucun.

      L'écran est volontairement identique que l'adresse soit inconnue, déjà
      inscrite, ou déjà en attente (anti-énumération). Mais il ne doit rien
      AFFIRMER qui soit faux dans l'un des trois : la version précédente
      annonçait « un lien de confirmation vient d'être envoyé » et demandait de
      cliquer sur un bouton de confirmation. Pour une adresse déjà inscrite,
      l'e-mail reçu est « Vous avez déjà un compte » et ne contient aucun lien
      de ce genre : la page envoyait l'utilisateur chercher quelque chose qui
      n'existait pas, et le renvoyait en boucle sur l'inscription.

      On parle donc du « message », de « la marche à suivre qu'il indique », et
      les deux issues possibles sont offertes ci-dessous.
    --}}
    {{-- L'adresse est INTERPOLÉE dans la phrase traduite, avec son balisage.
         La couper en deux morceaux figerait l'ordre des mots du français. --}}
    <p class="auth__lead">
        {!! __('auth.pending.accroche', [
            'adresse' => '<span class="mail-address">'.e($maskedEmail).'</span>',
        ]) !!}
    </p>

    <ol class="mail-steps">
        <li>{{ __('auth.pending.etape_1') }}</li>
        <li>{{ __('auth.pending.etape_2') }}</li>
        <li>{{ __('auth.pending.etape_3') }}</li>
    </ol>

    <div class="mail-spam">
        <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2"/>
        </svg>
        <span>{!! __('auth.pending.indesirables') !!}</span>
    </div>

    {{-- Le renvoi est un POST : il modifie l'état côté serveur. --}}
    <form method="POST" action="{{ route('registration.resend') }}" class="mt-4">
        @csrf

        <x-button
            :block="true"
            variant="outline"
            :disabled="$resendsLeft === 0"
            data-resend-button
        >{{ __('auth.pending.renvoyer') }}</x-button>
    </form>

    {{-- Compteur : rempli par JavaScript, jamais nécessaire. Sans lui, le
         bouton reste utilisable et c'est le serveur qui applique le délai. --}}
    <p class="resend-note"
       data-resend-note
       data-resend-wait="{{ $resendWait }}">
        @if ($resendsLeft === 0)
            {{ __('auth.pending.limite_atteinte') }}
        @else
            {{-- trans_choice, et non un @elseif sur la valeur 1 : le pluriel
                 ne se décide pas de la même façon dans toutes les langues. --}}
            {{ trans_choice('auth.pending.renvois_restants', $resendsLeft, ['compte' => $resendsLeft]) }}
        @endif
    </p>

    {{--
      LES DEUX ISSUES, bien visibles.

      Si cette adresse a déjà un compte, l'utilisateur n'attend aucun lien de
      confirmation : il lui faut se connecter, et souvent réinitialiser son mot
      de passe. Ces deux sorties étaient reléguées dans une ligne de liens en
      petit ; sans elles en évidence, la seule action apparente était d'attendre
      un e-mail qui ne viendrait jamais.

      Aucune fuite : ces liens s'affichent dans TOUS les cas, ils ne disent rien
      de l'état de l'adresse.
    --}}
    <div class="mail-spam mt-3">
        <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
            <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2m3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2"/>
        </svg>
        <span>
            {!! __('auth.pending.deja_compte') !!}
            <a href="{{ route('login') }}">{{ __('auth.pending.connectez_vous') }}</a>, {{ __('common.divers.ou') }}
            <a href="{{ route('password.request') }}">{{ __('auth.pending.reinitialisez') }}</a>.
        </span>
    </div>

    <p class="f__hint text-center mt-3 mb-0">
        <a href="{{ route('registration.abandon') }}">{{ __('auth.pending.recommencer') }}</a>
        <span class="mx-1" aria-hidden="true">·</span>
        <a href="{{ $supportWhatsapp }}" target="_blank" rel="noopener">{{ __('auth.pending.aide') }}</a>
    </p>

    {{-- Aide de développement. Double garde : ici et dans le contrôleur. --}}
    @if (app()->environment('local') && ! empty($devConfirmUrl))
        <div class="mail-spam mt-4">
            <span>
                <strong class="d-block mb-1">Développement uniquement</strong>
                <a href="{{ $devConfirmUrl }}" class="d-block text-break">{{ $devConfirmUrl }}</a>
            </span>
        </div>
    @endif
</x-auth-layout>
