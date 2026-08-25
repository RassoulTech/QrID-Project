{{--
  Cloche de notifications — menu déroulant Bootstrap natif (data-bs-*).

  Les données sont lues ICI plutôt que dans chaque contrôleur : la cloche
  s'affiche sur toutes les pages de l'espace client, et aucune d'elles n'a à
  s'en préoccuper. Deux requêtes bornées, sur index.

  Le compteur ne dépasse jamais « 9+ » : au-delà, le chiffre exact n'apporte
  rien et déborde de la pastille.
--}}
@php
    $alertes = Auth::user()->alerts()->latest()->limit(5)->get();
    $nonLues = Auth::user()->alerts()->unread()->count();
@endphp

<div class="dropdown">
    <button class="app-bell" type="button" id="menuNotifications"
            data-bs-toggle="dropdown" aria-expanded="false"
            aria-label="{{ $nonLues
                ? __('navigation.notifications.aria_non_lues', ['compte' => $nonLues])
                : __('navigation.notifications.aria') }}">
        <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
            <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2M8 1.918l-.797.161A4 4 0 0 0 4 6c0 .628-.134 2.197-.459 3.742-.16.767-.376 1.566-.663 2.258h10.244c-.287-.692-.502-1.49-.663-2.258C12.134 8.197 12 6.628 12 6a4 4 0 0 0-3.203-3.92zM14.22 12c.223.447.481.801.78 1H1c.299-.199.557-.553.78-1C2.68 10.2 3 6.88 3 6c0-2.42 1.72-4.44 4.005-4.901a1 1 0 1 1 1.99 0A5 5 0 0 1 13 6c0 .88.32 4.2 1.22 6"/>
        </svg>

        @if ($nonLues > 0)
            <span class="app-bell__pastille">{{ $nonLues > 9 ? '9+' : $nonLues }}</span>
        @endif
    </button>

    <div class="dropdown-menu dropdown-menu-end notif-menu" aria-labelledby="menuNotifications">
        <div class="notif-menu__tete">
            <span class="notif-menu__titre">{{ __('navigation.notifications.titre') }}</span>

            @if ($nonLues > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="notif-menu__tout">{{ __('navigation.notifications.tout_marquer') }}</button>
                </form>
            @endif
        </div>

        @forelse ($alertes as $alerte)
            <a class="notif-item @if ($alerte->isUnread()) is-unread @endif"
               href="{{ route('notifications.open', $alerte) }}">
                <span class="notif-item__titre">{{ $alerte->title }}</span>
                @if ($alerte->body)
                    <span class="notif-item__corps">{{ $alerte->body }}</span>
                @endif
                <span class="notif-item__date">{{ $alerte->created_at->diffForHumans() }}</span>
            </a>
        @empty
            {{-- État vide explicite : on dit ce qui déclenchera la première. --}}
            <div class="notif-vide">
                <svg width="22" height="22" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                    <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2M8 1.918l-.797.161A4 4 0 0 0 4 6c0 .628-.134 2.197-.459 3.742-.16.767-.376 1.566-.663 2.258h10.244c-.287-.692-.502-1.49-.663-2.258C12.134 8.197 12 6.628 12 6a4 4 0 0 0-3.203-3.92zM14.22 12c.223.447.481.801.78 1H1c.299-.199.557-.553.78-1C2.68 10.2 3 6.88 3 6c0-2.42 1.72-4.44 4.005-4.901a1 1 0 1 1 1.99 0A5 5 0 0 1 13 6c0 .88.32 4.2 1.22 6"/>
                </svg>
                <span>{{ __('navigation.notifications.vide') }}</span>
                <span class="notif-vide__aide">
                    {{ __('navigation.notifications.vide_aide') }}
                </span>
            </div>
        @endforelse

        @if ($alertes->isNotEmpty())
            <a class="notif-menu__pied" href="{{ route('notifications.index') }}">{{ __('navigation.notifications.voir_tout') }}</a>
        @endif
    </div>
</div>
