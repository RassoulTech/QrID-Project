{{-- MON COMPTE — ACTION PRINCIPALE : mettre à jour ses identifiants d'accès.

     Le COMPTE (users) = nom, e-mail, mot de passe.
     À ne pas confondre avec le PROFIL professionnel (profiles). --}}
<x-app-layout :title="__('profile.compte.titre')">
    <x-slot name="header">
        <h1 class="h4 fw-bold mb-0">{{ __('profile.compte.titre') }}</h1>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-8 d-flex flex-column gap-4">
            <x-card :title="__('profile.compte.informations')"
                    :subtitle="__('profile.compte.informations_sous')">
                @include('account.partials.update-account-information-form')
            </x-card>

            <x-card :title="__('profile.compte.mot_de_passe')"
                    :subtitle="__('profile.compte.mot_de_passe_sous')">
                @include('account.partials.update-password-form')
            </x-card>

            <x-card :title="__('profile.compte.supprimer')"
                    :subtitle="__('profile.compte.supprimer_sous')">
                @include('account.partials.delete-user-form')
            </x-card>
        </div>
    </div>
</x-app-layout>
