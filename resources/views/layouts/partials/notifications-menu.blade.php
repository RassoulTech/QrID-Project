{{--
  Cloche de notifications — un <details>, utilisable SANS JavaScript.

  Elle était un menu déroulant Bootstrap : sans script, la cloche ne
  s'ouvrait pas et « Voir tout » devenait inatteignable. Le <details> s'ouvre
  nativement ; `menus.js` n'ajoute que la fermeture au clic extérieur.

  Les données sont lues ICI plutôt que dans chaque contrôleur : la cloche
  s'affiche sur toutes les pages des deux espaces. Deux requêtes bornées, sur
  index.

  LA PASTILLE porte un CHIFFRE, pas seulement une couleur : « 3 » se lit sans
  distinguer le rouge. Le compte exact est dit au lecteur d'écran.
--}}
@php
    $alertes = Auth::user()->alerts()->latest()->limit(5)->get();
    $nonLues = Auth::user()->alerts()->unread()->count();
@endphp

<details class="menu-coque cloche" data-menu>
    <summary class="btn-icone cloche__declencheur"
             aria-label="{{ $nonLues
                ? __('navigation.notifications.aria_non_lues', ['compte' => $nonLues])
                : __('navigation.notifications.aria') }}">
        <x-icone name="bell" />

        @if ($nonLues > 0)
            <span class="cloche__pastille" aria-hidden="true">{{ $nonLues > 9 ? '9+' : $nonLues }}</span>
        @endif
    </summary>

    <div class="menu-coque__panneau notif-menu">
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
                <x-icone name="bell" />
                <span>{{ __('navigation.notifications.vide') }}</span>
                {{-- L'AIDE DÉPEND DE L'ESPACE : en administration, « dès la
                     première consultation de VOTRE carte » ne parle pas à
                     un exploitant. `$espace` est passé par le gabarit. --}}
                <span class="notif-vide__aide">
                    {{ ($espace ?? 'client') === 'admin'
                        ? __('navigation.notifications.vide_aide_admin')
                        : __('navigation.notifications.vide_aide') }}
                </span>
            </div>
        @endforelse

        <a class="notif-menu__pied" href="{{ route('notifications.index') }}">{{ __('navigation.notifications.voir_tout') }}</a>
    </div>
</details>
