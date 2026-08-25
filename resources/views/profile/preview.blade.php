{{--
  APERÇU — l'écran décisif du produit.

  Deux visuels côte à côte, et rien d'autre : la carte physique telle qu'elle
  sera imprimée, et la page réelle qui s'ouvre après un scan. Jusqu'ici,
  l'utilisateur activait sans avoir jamais vu sa carte — on lui demandait de
  payer pour un objet qu'il n'avait pas regardé.

  Le second visuel n'est PAS une simulation : c'est le composant x-phone
  alimenté par le profil réel, celui-là même que rendra la page publique.

  Aucune autre action sur cette page. Un bouton, un lien de retour.
--}}
<x-app-layout :title="__('profile.apercu.titre')">
    <div class="preview">
        <h1 class="preview__title">{{ __('profile.apercu.titre') }}</h1>
        <p class="preview__sub">{!! __('profile.apercu.sous') !!}</p>

        <div class="preview__duo">

            {{-- ================= VISUEL 1 — LA CARTE PVC ================= --}}
            <section class="preview__col">
                <h2 class="preview__legend">{{ __('profile.apercu.physique') }}</h2>

                <x-card-duo :profile="$profile" />

                <p class="preview__note">{{ __('profile.apercu.physique_note') }}</p>
            </section>

            {{-- ============ VISUEL 2 — CE QUE VERRONT LES CONTACTS ======== --}}
            <section class="preview__col">
                <h2 class="preview__legend">{{ __('profile.apercu.contacts') }}</h2>

                <x-phone :profile="$profile" size="lg" :animate="false" />

                <p class="preview__note">{{ __('profile.apercu.contacts_note') }}</p>
            </section>
        </div>

        <form method="POST" action="{{ route('abonnement.checkout') }}" class="preview__action">
            @csrf
            <x-button>{{ __('profile.apercu.activer') }}</x-button>
        </form>

        <a href="{{ route('profile.edit') }}" class="preview__edit">{{ __('profile.apercu.modifier') }}</a>
    </div>
</x-app-layout>
