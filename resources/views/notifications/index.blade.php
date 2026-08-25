{{-- Toutes les notifications du compte, les plus récentes en premier. --}}
<x-app-layout :title="__('dashboard.notifications.titre')">

    <div class="db-tete">
        <div>
            <h1 class="db-tete__titre">{{ __('dashboard.notifications.titre') }}</h1>
            <p class="db-tete__sous">{{ __('dashboard.notifications.sous') }}</p>
        </div>

        @if ($notifications->isNotEmpty())
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <x-button type="submit" variant="outline" size="sm">{{ __('dashboard.notifications.tout_marquer') }}</x-button>
            </form>
        @endif
    </div>

    <section class="db-card">
        @forelse ($notifications as $alerte)
            <a class="notif-item @if ($alerte->isUnread()) is-unread @endif"
               href="{{ route('notifications.open', $alerte) }}">
                <span class="notif-item__titre">{{ $alerte->title }}</span>
                @if ($alerte->body)
                    <span class="notif-item__corps">{{ $alerte->body }}</span>
                @endif
                <span class="notif-item__date">{{ $alerte->created_at->diffForHumans() }}</span>
            </a>
        @empty
            <div class="db-vide">
                <svg width="26" height="26" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                    <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2M8 1.918l-.797.161A4 4 0 0 0 4 6c0 .628-.134 2.197-.459 3.742-.16.767-.376 1.566-.663 2.258h10.244c-.287-.692-.502-1.49-.663-2.258C12.134 8.197 12 6.628 12 6a4 4 0 0 0-3.203-3.92zM14.22 12c.223.447.481.801.78 1H1c.299-.199.557-.553.78-1C2.68 10.2 3 6.88 3 6c0-2.42 1.72-4.44 4.005-4.901a1 1 0 1 1 1.99 0A5 5 0 0 1 13 6c0 .88.32 4.2 1.22 6"/>
                </svg>
                <p class="db-vide__titre">{{ __('dashboard.notifications.vide_titre') }}</p>
                <p class="db-vide__texte">{!! __('dashboard.notifications.vide_texte') !!}</p>
            </div>
        @endforelse

        @if ($notifications->hasPages())
            <div class="mt-3">{{ $notifications->links() }}</div>
        @endif
    </section>
</x-app-layout>
