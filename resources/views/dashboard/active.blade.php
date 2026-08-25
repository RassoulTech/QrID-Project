{{--
  TABLEAU DE BORD — carte créée.

  Deux colonnes : le contenu principal, et un rail de contexte qui passe
  dessous sous 1200px. Aucun calcul ici, aucune requête : tout vient du
  DashboardController.

  Ordre imposé : ce qui alarme d'abord (abonnement échu), puis les chiffres,
  puis la carte, puis l'activité.
--}}
<x-app-layout :title="__('dashboard.titre')">

    <div class="db-tete">
        <div>
            {{-- Le PRÉNOM est du contenu saisi : il n'est jamais traduit.
                 Seule la formule d'accueil qui l'entoure l'est. --}}
            <h1 class="db-tete__titre">
                {{ __('dashboard.tete.bonjour', [
                    'prenom' => Str::before($profile->first_name ?: Auth::user()->name, ' '),
                ]) }}
            </h1>
            <p class="db-tete__sous">{{ __('dashboard.tete.sous') }}</p>
        </div>

        @if ($profile->isPubliclyVisible())
            <x-button :href="$publicUrl" variant="outline" size="sm" target="_blank" rel="noopener">
                {{ __('dashboard.tete.voir_carte') }}
            </x-button>
        @else
            <x-button :href="route('abonnement.paiement')" size="sm">{{ __('dashboard.tete.activer_carte') }}</x-button>
        @endif
    </div>

    @if ($expired)
        <x-alert type="danger" :dismissible="false" :title="__('dashboard.expire.titre')">
            {!! __('dashboard.expire.texte') !!}
            <span class="d-block mt-2">
                <x-button :href="route('abonnement.paiement')" size="sm">{{ __('dashboard.expire.reactiver') }}</x-button>
            </span>
        </x-alert>
    @endif

    <div class="db-grille">
        <div class="db-principal">
            @include('dashboard.partials.stats')
            @include('dashboard.partials.card-block')
            @include('dashboard.partials.carte-physique')
            @include('dashboard.partials.activity-chart')
        </div>

        @include('dashboard.partials.side-panel')
    </div>
</x-app-layout>
