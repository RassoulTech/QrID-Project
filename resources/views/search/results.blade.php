{{--
  Résultats de recherche.

  Le périmètre est celui du COMPTE CONNECTÉ : sa carte, ses paiements, ses
  notifications. Il n'existe aucun chemin permettant de chercher ailleurs.
--}}
<x-app-layout :title="__('dashboard.recherche.titre')">

    <div class="db-tete">
        <div>
            <h1 class="db-tete__titre">{{ __('dashboard.recherche.titre') }}</h1>
            <p class="db-tete__sous">
                @if ($terme !== '')
                    {!! __('dashboard.recherche.resultats_pour', ['terme' => e($terme)]) !!}
                @else
                    {{ __('dashboard.recherche.invite') }}
                @endif
            </p>
        </div>
    </div>

    @if ($tropCourt)
        <x-alert type="info" :dismissible="false">
            {{ __('dashboard.recherche.trop_court') }}
        </x-alert>
    @endif

    @php
        $total = ($profil ? 1 : 0) + $paiements->count() + $notifications->count();
    @endphp

    @if ($terme !== '' && ! $tropCourt && $total === 0)
        <section class="db-card">
            <div class="db-vide">
                <p class="db-vide__titre">{{ __('dashboard.recherche.aucun_titre') }}</p>
                <p class="db-vide__texte">
                    {!! __('dashboard.recherche.aucun_texte', ['terme' => e($terme)]) !!}
                </p>
            </div>
        </section>
    @endif

    @if ($profil)
        <section class="db-card mb-3">
            <h2 class="db-card__titre">{{ __('dashboard.recherche.ma_carte') }}</h2>
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
            <h2 class="db-card__titre">{{ __('dashboard.recherche.paiements') }}</h2>
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
