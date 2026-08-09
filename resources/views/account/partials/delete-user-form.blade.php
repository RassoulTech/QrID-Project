<section>
    <p class="text-secondary small">
        Une fois le compte supprimé, toutes ses données sont définitivement perdues.
        Avant de continuer, téléchargez ce que vous souhaitez conserver.
    </p>

    @if ($errors->userDeletion->isNotEmpty())
        <x-alert type="danger">{{ $errors->userDeletion->first('password') }}</x-alert>
    @endif

    <x-button variant="danger" type="button"
              data-bs-toggle="modal" data-bs-target="#confirmUserDeletion">
        Supprimer mon compte
    </x-button>

    <x-modal id="confirmUserDeletion" title="Confirmer la suppression">
        <form method="post" action="{{ route('compte.destroy') }}" id="deleteAccountForm">
            @csrf
            @method('delete')

            <p class="text-secondary small">
                Cette action est irréversible. Saisissez votre mot de passe pour confirmer
                la suppression définitive de votre compte.
            </p>

            <x-password
                name="password"
                id="delete_password"
                label="Mot de passe"
                autocomplete="current-password"
                errorBag="userDeletion"
            />
        </form>

        <x-slot name="footer">
            <x-button type="button" variant="secondary" data-bs-dismiss="modal">Annuler</x-button>
            <x-button variant="danger" form="deleteAccountForm">Supprimer définitivement</x-button>
        </x-slot>
    </x-modal>
</section>
