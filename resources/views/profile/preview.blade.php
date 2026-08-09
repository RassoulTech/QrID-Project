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
<x-app-layout title="Votre carte est prête">
    <div class="preview">
        <h1 class="preview__title">Votre carte est prête</h1>
        <p class="preview__sub">
            Regardez-la avant de l'activer&nbsp;: rien n'est publié, rien n'est
            débité tant que vous n'avez pas décidé.
        </p>

        <div class="preview__duo">

            {{-- ================= VISUEL 1 — LA CARTE PVC ================= --}}
            <section class="preview__col">
                <h2 class="preview__legend">Votre carte physique</h2>

                <x-pvc-card :profile="$profile" size="lg" />

                <p class="preview__note">Format carte bancaire, prête à imprimer.</p>
            </section>

            {{-- ============ VISUEL 2 — CE QUE VERRONT LES CONTACTS ======== --}}
            <section class="preview__col">
                <h2 class="preview__legend">Ce que verront vos contacts</h2>

                <x-phone :profile="$profile" size="lg" :animate="false" />

                <p class="preview__note">La page qui s'ouvre après un scan.</p>
            </section>
        </div>

        <form method="POST" action="{{ route('abonnement.checkout') }}" class="preview__action">
            @csrf
            <x-button>Activer ma carte</x-button>
        </form>

        <a href="{{ route('profile.edit') }}" class="preview__edit">Modifier mes informations</a>
    </div>
</x-app-layout>
