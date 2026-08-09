{{--
  x-modal — fenêtre modale Bootstrap native (attributs data-bs-*, aucun JS écrit).

  Déclencheur :
    <x-button type="button" data-bs-toggle="modal" data-bs-target="#suppression">Supprimer</x-button>

  Modale :
    <x-modal id="suppression" title="Confirmer la suppression" :show="$errors->userDeletion->isNotEmpty()">
        <p>Cette action est irréversible.</p>
        <x-slot name="footer">
            <x-button type="button" variant="secondary" data-bs-dismiss="modal">Annuler</x-button>
            <x-button variant="danger">Supprimer</x-button>
        </x-slot>
    </x-modal>

  Props : id, title, size (sm | lg | xl), centered, show (ouverture serveur
          après une erreur de validation — sans JavaScript, via la classe .show)
  Slots : default, footer
--}}
@props([
    'id',
    'title' => null,
    'size' => null,
    'centered' => true,
    'show' => false,
])

<div
    class="modal fade {{ $show ? 'show d-block' : '' }}"
    id="{{ $id }}"
    tabindex="-1"
    aria-labelledby="{{ $id }}-title"
    aria-hidden="{{ $show ? 'false' : 'true' }}"
    @if ($show) style="background-color: rgba(15,23,42,.5);" @endif
>
    <div class="modal-dialog {{ $centered ? 'modal-dialog-centered' : '' }} {{ $size ? 'modal-'.$size : '' }}">
        <div class="modal-content">
            @if ($title)
                <div class="modal-header">
                    <h2 class="modal-title h6 fw-bold" id="{{ $id }}-title">{{ $title }}</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
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
