{{--
  TABLEAU DE BORD — carte créée.

  Deux colonnes : le contenu principal, et un rail de contexte qui passe
  dessous sous 1200px. Aucun calcul ici, aucune requête : tout vient du
  DashboardController.

  Ordre imposé : ce qui alarme d'abord (abonnement échu), puis les chiffres,
  puis la carte, puis l'activité.
--}}
<x-app-layout title="Tableau de bord">

    <div class="db-tete">
        <div>
            <h1 class="db-tete__titre">
                Bonjour {{ Str::before($profile->first_name ?: Auth::user()->name, ' ') }}
            </h1>
            <p class="db-tete__sous">Suivez votre carte et son audience.</p>
        </div>

        @if ($profile->isPubliclyVisible())
            <x-button :href="$publicUrl" variant="outline" size="sm" target="_blank" rel="noopener">
                Voir ma carte publique
            </x-button>
        @else
            <x-button :href="route('abonnement.paiement')" size="sm">Activer ma carte</x-button>
        @endif
    </div>

    @if ($expired)
        <x-alert type="danger" :dismissible="false" title="Votre abonnement a expiré">
            Votre carte n'est plus consultable par vos contacts&nbsp;: le lien public
            ne répond plus. Rien n'est perdu — un paiement la remet en ligne
            immédiatement.
            <span class="d-block mt-2">
                <x-button :href="route('abonnement.paiement')" size="sm">Réactiver ma carte</x-button>
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
