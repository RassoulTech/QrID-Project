<section>
    <p class="text-secondary small">{{ __('profile.compte.supprimer_avertissement') }}</p>

    @if ($errors->userDeletion->isNotEmpty())
        <x-alert type="danger">{{ $errors->userDeletion->first('password') }}</x-alert>
    @endif

    <x-button variant="danger" type="button"
              data-bs-toggle="modal" data-bs-target="#confirmUserDeletion">
        {{ __('profile.compte.supprimer_bouton') }}
    </x-button>

    <x-modal id="confirmUserDeletion" :title="__('profile.compte.supprimer_confirmer')">
        <form method="post" action="{{ route('compte.destroy') }}" id="deleteAccountForm">
            @csrf
            @method('delete')

            <p class="text-secondary small">{{ __('profile.compte.supprimer_modale') }}</p>

            <x-password
                name="password"
                id="delete_password"
                :label="__('profile.compte.mot_de_passe')"
                autocomplete="current-password"
                errorBag="userDeletion"
            />
        </form>

        <x-slot name="footer">
            <x-button type="button" variant="secondary" data-bs-dismiss="modal">{{ __('common.actions.annuler') }}</x-button>
            <x-button variant="danger" form="deleteAccountForm">{{ __('profile.compte.supprimer_definitivement') }}</x-button>
        </x-slot>
    </x-modal>
</section>
