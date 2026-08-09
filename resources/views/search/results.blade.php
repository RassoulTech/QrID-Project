{{--
  Résultats de recherche.

  Le périmètre est celui du COMPTE CONNECTÉ : sa carte, ses paiements, ses
  notifications. Il n'existe aucun chemin permettant de chercher ailleurs.
--}}
<x-app-layout title="Recherche">

    <div class="db-tete">
        <div>
            <h1 class="db-tete__titre">Recherche</h1>
            <p class="db-tete__sous">
                @if ($terme !== '')
                    Résultats pour «&nbsp;{{ $terme }}&nbsp;»
                @else
                    Saisissez un terme dans la barre de recherche.
                @endif
            </p>
        </div>
    </div>

    @if ($tropCourt)
        <x-alert type="info" :dismissible="false">
            Saisissez au moins deux caractères.
        </x-alert>
    @endif

    @php
        $total = ($profil ? 1 : 0) + $paiements->count() + $notifications->count();
    @endphp

    @if ($terme !== '' && ! $tropCourt && $total === 0)
        <section class="db-card">
            <div class="db-vide">
                <p class="db-vide__titre">Aucun résultat</p>
                <p class="db-vide__texte">
                    Rien ne correspond à «&nbsp;{{ $terme }}&nbsp;» dans votre carte,
                    vos paiements ou vos notifications.
                </p>
            </div>
        </section>
    @endif

    @if ($profil)
        <section class="db-card mb-3">
            <h2 class="db-card__titre">Ma carte</h2>
            <a class="notif-item" href="{{ route('profile.edit') }}">
                <span class="notif-item__titre">{{ $profil->full_name }}</span>
                <span class="notif-item__corps">
                    {{ $profil->job_title }}@if ($profil->company) · {{ $profil->company }}@endif
                </span>
            </a>
        </section>
    @endif

    @if ($paiements->isNotEmpty())
        <section class="db-card mb-3">
            <h2 class="db-card__titre">Paiements</h2>
            @foreach ($paiements as $paiement)
                <div class="notif-item">
                    <span class="notif-item__titre">{{ $paiement->formattedAmount() }}</span>
                    <span class="notif-item__corps">
                        {{ $paiement->method_label }} · {{ $paiement->provider_ref }}
                    </span>
                    <span class="notif-item__date">{{ $paiement->created_at->diffForHumans() }}</span>
                </div>
            @endforeach
        </section>
    @endif

    @if ($notifications->isNotEmpty())
        <section class="db-card">
            <h2 class="db-card__titre">Notifications</h2>
            @foreach ($notifications as $alerte)
                <a class="notif-item" href="{{ route('notifications.open', $alerte) }}">
                    <span class="notif-item__titre">{{ $alerte->title }}</span>
                    @if ($alerte->body)
                        <span class="notif-item__corps">{{ $alerte->body }}</span>
                    @endif
                    <span class="notif-item__date">{{ $alerte->created_at->diffForHumans() }}</span>
                </a>
            @endforeach
        </section>
    @endif
</x-app-layout>
