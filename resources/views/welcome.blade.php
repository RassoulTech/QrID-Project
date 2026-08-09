{{-- LANDING PAGE — reproduction de la maquette validée.
     ACTION PRINCIPALE : créer un compte. --}}
<x-public-layout
    :title="config('app.name').' | Votre Identité Professionnelle Numérique'"
    description="La plateforme d'identité numérique sécurisée pour l'élite professionnelle du Sénégal. Centralisez votre expertise et rayonnez avec élégance.">

    @include('landing.sections.hero')
    @include('landing.sections.trades')
    @include('landing.sections.figures')
    @include('landing.sections.steps')
    @include('landing.sections.showcase')
    @include('landing.sections.plans')
    @include('landing.sections.final-cta')
</x-public-layout>
