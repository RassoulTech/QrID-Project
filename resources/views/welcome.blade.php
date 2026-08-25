{{-- LANDING PAGE — reproduction de la maquette validée.
     ACTION PRINCIPALE : créer un compte. --}}
<x-public-layout
    :title="__('landing.meta.titre', ['marque' => config('app.name')])"
    :description="__('landing.meta.description')">

    @include('landing.sections.hero')
    @include('landing.sections.trades')
    @include('landing.sections.figures')
    @include('landing.sections.steps')
    @include('landing.sections.showcase')
    @include('landing.sections.plans')

    {{-- Le contact vient AVANT l'appel à l'action final : il s'adresse à qui
         hésite encore, et doit rencontrer sa question ouverte avant qu'on lui
         redemande de créer un compte. --}}
    @include('landing.sections.contact')

    @include('landing.sections.final-cta')
</x-public-layout>
