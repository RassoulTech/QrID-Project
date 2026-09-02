<x-admin-layout :title="__('admin.sante.titre')">
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h4 fw-bold mb-0">{{ __('admin.sante.titre') }}</h1>
            {{-- Rafraîchissement 100 % serveur : simple rechargement de page, aucun JS. --}}
            <x-button :href="route('admin.system.health')" variant="outline-secondary" size="sm">Actualiser</x-button>
        </div>
    </x-slot>

    @if ($failedJobs > 0)
        <x-alert type="danger" :dismissible="false">
            {!! __('admin.sante.jobs_echec', [
                'compte' => $failedJobs,
                'table' => '<code>failed_jobs</code>',
                'commande' => '<code>php artisan queue:retry all</code>',
            ]) !!}
        </x-alert>
    @endif

    @if ($queueAlert)
        <x-alert type="warning" :dismissible="false">
            {{ __('admin.sante.file_engorgee', ['compte' => $mailQueue]) }}
        </x-alert>
    @endif

    {{-- ==================== LE PLANIFICATEUR ====================
         Il porte désormais l'agrégation des statistiques, la purge des
         inscriptions, les relances et le récapitulatif. S'il s'arrête, rien
         ne casse visiblement : les chiffres cessent simplement d'avancer.

         Quinze minutes est le seuil parce qu'il bat toutes les cinq : trois
         battements manqués ne sont plus un hasard. En dessous, on affiche
         sans alerter — un conteneur qui vient de se réveiller n'a pas encore
         eu le temps de battre. --}}
    @if ($planificateurMinutes === null || $planificateurMinutes > 15)
        <x-alert type="warning" :dismissible="false">
            {{ __('admin.sante.planificateur_muet', ['minutes' => $planificateurMinutes ?? '—']) }}
        </x-alert>
    @endif

    <div class="row g-3">
        <div class="col-6 col-lg-3">
            <x-card>
                <div class="text-secondary small">{{ __('admin.sante.planificateur') }}</div>
                <div class="h3 fw-bold mb-0 {{ ($planificateurMinutes === null || $planificateurMinutes > 15) ? 'text-danger' : '' }}">
                    {{ $planificateurMinutes === null ? '—' : $planificateurMinutes }}
                </div>
                <div class="text-secondary small">
                    {{ $planificateurMinutes === null
                        ? __('admin.sante.planificateur_jamais')
                        : __('admin.sante.planificateur_battement', ['minutes' => $planificateurMinutes]) }}
                </div>
            </x-card>
        </div>
        <div class="col-6 col-lg-3">
            <x-card>
                <div class="text-secondary small">{{ __('admin.sante.file_mail') }}</div>
                <div class="h3 fw-bold mb-0">{{ $mailQueue }}</div>
                <div class="text-secondary small">en attente</div>
            </x-card>
        </div>
        <div class="col-6 col-lg-3">
            <x-card>
                <div class="text-secondary small">{{ __('admin.sante.total_jobs') }}</div>
                <div class="h3 fw-bold mb-0">{{ $totalJobs }}</div>
                <div class="text-secondary small">toutes files</div>
            </x-card>
        </div>
        <div class="col-6 col-lg-3">
            <x-card>
                <div class="text-secondary small">{{ __('admin.sante.jobs_echoues') }}</div>
                <div class="h3 fw-bold mb-0 {{ $failedJobs > 0 ? 'text-danger' : '' }}">{{ $failedJobs }}</div>
                <div class="text-secondary small">{{ __('admin.sante.a_relancer') }}</div>
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
            <strong>{{ __('admin.sante.mails_bloques_titre') }}</strong><br>
            {{-- Les balises <code> sont passees EN PARAMETRE, pas ecrites
                 dans la traduction : une phrase qui contient son propre
                 balisage se traduit mal, et le traducteur casse le HTML
                 sans s'en apercevoir. --}}
            {!! __('admin.sante.mails_bloques_texte', [
                'pilote' => '<code>'.e($pilote).'</code>',
                'commande' => '<code>queue:work</code>',
                'table' => '<code>jobs</code>',
            ]) !!}<br>
            <span class="small">
                {!! __('admin.sante.mails_bloques_correction', [
                    'variable' => '<code>QUEUE_CONNECTION</code>',
                    'valeur' => '<code>sync</code>',
                ]) !!}
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
            <h2 class="h6 fw-bold mb-0">{{ __('admin.sante.derniers_mails') }}</h2>
            <span class="text-secondary small">
                Pilote : <code>{{ $pilote }}</code> ·
                {{ $mailsDuJour }} {{ __('admin.sante.aujourdhui') }}
            </span>
        </div>

        @if ($derniersMails->isEmpty())
            <x-empty-state
                :title="__('admin.sante.aucun_mail')"
                message="Si vous venez de demander un lien de réinitialisation et que rien n'apparaît ici, le message n'a pas quitté l'application — cherchez du côté du pilote de file, pas du côté de votre boîte de réception." />
        @else
            <div class="table-scroll">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th scope="col">Date</th>
                            <th scope="col">Destinataire</th>
                            <th scope="col">Objet</th>
                            <th scope="col">{{ __('admin.commun.statut') }}</th>
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
                                        <x-badge variant="success">{{ __('admin.sante.envoye') }}</x-badge>
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
        {{ __('admin.sante.rafraichi') }}
    </p>
</x-admin-layout>
