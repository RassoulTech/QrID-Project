<x-admin-layout title="État système">
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h4 fw-bold mb-0">État système</h1>
            {{-- Rafraîchissement 100 % serveur : simple rechargement de page, aucun JS. --}}
            <x-button :href="route('admin.system.health')" variant="outline-secondary" size="sm">Actualiser</x-button>
        </div>
    </x-slot>

    @if ($failedJobs > 0)
        <x-alert type="danger" :dismissible="false">
            {{ $failedJobs }} job(s) en échec. Inspecte <code>failed_jobs</code> puis relance avec
            <code>php artisan queue:retry all</code>.
        </x-alert>
    @endif

    @if ($queueAlert)
        <x-alert type="warning" :dismissible="false">
            File « mail » engorgée ({{ $mailQueue }} en attente). Vérifie que le worker tourne.
        </x-alert>
    @endif

    <div class="row g-3">
        <div class="col-6 col-lg-3">
            <x-card>
                <div class="text-secondary small">File « mail »</div>
                <div class="h3 fw-bold mb-0">{{ $mailQueue }}</div>
                <div class="text-secondary small">en attente</div>
            </x-card>
        </div>
        <div class="col-6 col-lg-3">
            <x-card>
                <div class="text-secondary small">Total jobs</div>
                <div class="h3 fw-bold mb-0">{{ $totalJobs }}</div>
                <div class="text-secondary small">toutes files</div>
            </x-card>
        </div>
        <div class="col-6 col-lg-3">
            <x-card>
                <div class="text-secondary small">Jobs échoués</div>
                <div class="h3 fw-bold mb-0 {{ $failedJobs > 0 ? 'text-danger' : '' }}">{{ $failedJobs }}</div>
                <div class="text-secondary small">à relancer</div>
            </x-card>
        </div>
        <div class="col-6 col-lg-3">
            <x-card>
                <div class="text-secondary small">Inscriptions</div>
                <div class="h3 fw-bold mb-0">{{ $pendingRegistrations }}</div>
                <div class="text-secondary small">en attente de confirmation</div>
            </x-card>
        </div>
    </div>

    <p class="text-secondary small mt-3 mb-0">
        Rafraîchi au chargement de la page. Aucune donnée temps réel côté navigateur.
    </p>
</x-admin-layout>
