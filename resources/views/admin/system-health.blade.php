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

    {{-- ==================== PILOTE DE FILE ====================
         LE RENSEIGNEMENT DÉCISIF de cet écran. Sans worker exécutant
         queue:work, un pilote autre que « sync » fait DISPARAÎTRE les
         e-mails : le message est écrit dans la table jobs et personne ne le
         reprend. Aucune erreur, aucune trace — la page confirme même l'envoi
         à l'utilisateur. --}}
    @if ($fileSansWorker)
        <x-alert type="danger" :dismissible="false">
            <strong>Les e-mails ne partent probablement pas.</strong><br>
            Le pilote de file est <code>{{ $pilote }}</code>, ce qui suppose un
            worker exécutant <code>queue:work</code>. Le plan gratuit de Render
            n'en fait pas tourner : les messages sont écrits dans la table
            <code>jobs</code> et jamais repris — sans la moindre erreur.<br>
            <span class="small">
                Correction immédiate : passer <code>QUEUE_CONNECTION</code> à
                <code>sync</code> dans les variables d'environnement, puis
                redéployer. L'envoi se fera dans la requête — plus lent d'une
                seconde ou deux, mais il aboutira.
            </span>
        </x-alert>
    @endif

    {{-- ==================== ENVOIS RÉCENTS ====================
         Un e-mail qui ne part pas ne produit aucune erreur visible. Cette
         table est le seul endroit où l'on peut le CONSTATER plutôt que le
         supposer : chaque ligne prouve qu'un message a réellement quitté
         l'application. --}}
    <div class="mt-4">
        <div class="d-flex align-items-baseline justify-content-between mb-2">
            <h2 class="h6 fw-bold mb-0">Derniers e-mails envoyés</h2>
            <span class="text-secondary small">
                Pilote : <code>{{ $pilote }}</code> ·
                {{ $mailsDuJour }} aujourd'hui
            </span>
        </div>

        @if ($derniersMails->isEmpty())
            <x-empty-state
                title="Aucun e-mail enregistré"
                message="Si vous venez de demander un lien de réinitialisation et que rien n'apparaît ici, le message n'a pas quitté l'application — cherchez du côté du pilote de file, pas du côté de votre boîte de réception." />
        @else
            <div class="table-scroll">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th scope="col">Date</th>
                            <th scope="col">Destinataire</th>
                            <th scope="col">Objet</th>
                            <th scope="col">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($derniersMails as $mail)
                            <tr>
                                <td class="adm-table__second">
                                    {{ $mail->created_at?->format('d/m/Y H:i:s') ?? '—' }}
                                </td>
                                <td class="adm-table__principal">{{ $mail->recipient }}</td>
                                <td class="adm-table__second">{{ $mail->subject ?? '—' }}</td>
                                <td>
                                    @if ($mail->status === 'sent')
                                        <x-badge variant="success">Envoyé</x-badge>
                                    @else
                                        <x-badge variant="danger">{{ $mail->status }}</x-badge>
                                        @if ($mail->error)
                                            <div class="adm-table__second" style="white-space:normal">
                                                {{ \Illuminate\Support\Str::limit($mail->error, 160) }}
                                            </div>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <p class="text-secondary small mt-3 mb-0">
        Rafraîchi au chargement de la page. Aucune donnée temps réel côté navigateur.
    </p>
</x-admin-layout>
