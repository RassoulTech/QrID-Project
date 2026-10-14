{{--
  x-modal — la boîte de dialogue (Bootstrap natif, habillée par le socle).

      <x-modal id="suppression" title="Confirmer la suppression">
          …
          <x-slot name="footer">…</x-slot>
      </x-modal>

  Rayon 24, ombre de niveau 4, titre H3. Une action qui ne doit jamais
  dépendre du script passe plutôt par un <dialog> ou une page de
  confirmation : sans JavaScript, une modale Bootstrap ne s'ouvre pas.

  `show` l'affiche ouverte d'emblée (rendu serveur après une erreur) : le
  voile est alors posé par la classe `modal--ouverte`, pas en ligne.
--}}
@props([
    'id',
    'title' => null,
    'size' => null,
    'centered' => true,
    'show' => false,
])

<div
    @class(['modal', 'fade', 'show d-block modal--ouverte' => $show])
    id="{{ $id }}"
    tabindex="-1"
    aria-labelledby="{{ $id }}-title"
    aria-hidden="{{ $show ? 'false' : 'true' }}"
>
    <div class="modal-dialog {{ $centered ? 'modal-dialog-centered' : '' }} {{ $size ? 'modal-'.$size : '' }}">
        <div class="modal-content">
            @if ($title)
                <div class="modal-header">
                    <h2 class="modal-title" id="{{ $id }}-title">{{ $title }}</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.actions.fermer') }}"></button>
                </div>
            @endif
            <div class="modal-body">
                {{ $slot }}
            </div>
            @isset($footer)
                <div class="modal-footer">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>
