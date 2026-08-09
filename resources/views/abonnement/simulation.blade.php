{{--
  Écran de simulation — DÉVELOPPEMENT UNIQUEMENT (404 ailleurs).

  Il tient la place de la page de l'opérateur. Les trois issues réelles y sont
  offertes : encaissement, refus, abandon. Sans les trois, un parcours de
  paiement ne serait testable qu'en supposant qu'il réussit toujours — or
  l'échec et l'annulation sont exactement les cas qui font perdre des clients.

  Ce sont des LIENS : la passerelle réelle nous rappellera aussi par une URL.
--}}
<x-app-layout title="Simulation de paiement">

    <div class="step-card">
        <p class="step-card__kicker">Environnement de développement</p>

        <h1 class="step-card__title">Simulation de paiement</h1>

        <p class="step-card__sub">
            Aucune somme réelle n'est en jeu. Cet écran remplace celui de
            l'opérateur tant qu'aucun contrat n'est signé.
        </p>

        <div class="mail-spam">
            <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2"/>
            </svg>
            <span>
                <strong>{{ $payment->method_label }}</strong> ·
                {{ $payment->formattedAmount() }} ·
                référence {{ $payment->provider_ref }}
            </span>
        </div>

        <div class="step-fields mt-4">
            <x-button :href="route('abonnement.retour', ['payment' => $payment->id, 'statut' => 'succes', 'reference' => $payment->provider_ref])">
                Confirmer le paiement
            </x-button>

            <x-button variant="outline"
                      :href="route('abonnement.retour', ['payment' => $payment->id, 'statut' => 'echec'])">
                Simuler un refus
            </x-button>

            <a class="step-back text-center"
               href="{{ route('abonnement.retour', ['payment' => $payment->id, 'statut' => 'annule']) }}">
                Annuler et revenir
            </a>
        </div>
    </div>
</x-app-layout>
