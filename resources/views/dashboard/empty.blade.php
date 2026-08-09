{{-- DASHBOARD — état vide.

     UNE seule action possible : créer son profil. Aucun bloc de statistiques
     à zéro, aucune carte vide, aucun conseil à lire. --}}
<x-app-layout title="Tableau de bord">
    <div class="board-empty">
        <div class="board-empty__card">
            <span class="board-empty__icon" aria-hidden="true">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="10" cy="8" r="3.5"/><path d="M3.5 20a6.5 6.5 0 0 1 13 0"/>
                    <path d="M19 7v6M22 10h-6"/>
                </svg>
            </span>

            <h1 class="board-empty__title">Bienvenue {{ $user->name }}</h1>
            <p class="board-empty__text">
                Créez votre profil professionnel pour le partager par lien ou QR Code.
            </p>

            <x-button :href="route('profile.create.step1')" size="lg">
                Créer mon profil
            </x-button>
        </div>
    </div>
</x-app-layout>
