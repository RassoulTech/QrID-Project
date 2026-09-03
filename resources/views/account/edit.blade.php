{{-- MON COMPTE — ACTION PRINCIPALE : mettre à jour ses identifiants d'accès.

     Le COMPTE (users) = nom, e-mail, mot de passe.
     À ne pas confondre avec le PROFIL professionnel (profiles).

     ═══════════════════════════════════════════════════════════════════════
     CETTE PAGE N'AFFICHAIT AUCUN DE SES FORMULAIRES
     ═══════════════════════════════════════════════════════════════════════
     Elle enveloppait chaque bloc dans `<x-card :title=... :subtitle=...>`.
     Or `x-card` est le composant de la CARTE PVC — l'objet vert qu'on
     imprime — dont les props sont `face`, `variant` et `profile`. Il ignore
     `title`, `subtitle` ET le contenu qu'on lui passe.

     La page rendait donc quatre cartes de visite vides, et le client ne
     pouvait ni corriger son e-mail, ni changer son mot de passe, ni
     supprimer son compte. Statut 200, aucune erreur, aucune trace.

     C'est la collision de noms `.card` sous une autre forme : deux objets
     entièrement différents portant le même nom dans le même projet. Le
     produit n'a pas de composant de bloc d'interface ; il utilise la classe
     `db-card`, comme le tableau de bord et les statistiques. --}}
<x-app-layout :title="__('profile.compte.titre')">
    <x-slot name="header">
        <h1 class="h4 fw-bold mb-0">{{ __('profile.compte.titre') }}</h1>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-8 d-flex flex-column gap-4">

            @foreach ([
                ['avatar_carte', 'avatar_carte_sous', 'update-avatar-form'],
                ['informations', 'informations_sous', 'update-account-information-form'],
                ['mot_de_passe', 'mot_de_passe_sous', 'update-password-form'],
                ['supprimer', 'supprimer_sous', 'delete-user-form'],
            ] as [$titre, $sous, $formulaire])
                <section class="db-card">
                    <h2 class="db-card__titre">{{ __('profile.compte.'.$titre) }}</h2>
                    <p class="db-card__sous">{{ __('profile.compte.'.$sous) }}</p>

                    @include('account.partials.'.$formulaire)
                </section>
            @endforeach

        </div>
    </div>
</x-app-layout>
