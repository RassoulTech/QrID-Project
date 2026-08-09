{{-- MON COMPTE — ACTION PRINCIPALE : mettre à jour ses identifiants d'accès.

     Le COMPTE (users) = nom, e-mail, mot de passe.
     À ne pas confondre avec le PROFIL professionnel (profiles). --}}
<x-app-layout title="Mon compte">
    <x-slot name="header">
        <h1 class="h4 fw-bold mb-0">Mon compte</h1>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-8 d-flex flex-column gap-4">
            <x-card title="Informations du compte"
                    subtitle="Vos identifiants de connexion.">
                @include('account.partials.update-account-information-form')
            </x-card>

            <x-card title="Mot de passe"
                    subtitle="Utilisez un mot de passe long et unique.">
                @include('account.partials.update-password-form')
            </x-card>

            <x-card title="Supprimer le compte"
                    subtitle="Cette action supprime aussi votre profil professionnel.">
                @include('account.partials.delete-user-form')
            </x-card>
        </div>
    </div>
</x-app-layout>
