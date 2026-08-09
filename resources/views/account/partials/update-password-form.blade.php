<section>
    <form method="post" action="{{ route('password.update') }}" novalidate>
        @csrf
        @method('put')

        <x-password
            name="current_password"
            id="update_password_current_password"
            label="Mot de passe actuel"
            autocomplete="current-password"
            errorBag="updatePassword"
        />

        <x-password
            name="password"
            id="update_password_password"
            label="Nouveau mot de passe"
            autocomplete="new-password"
            errorBag="updatePassword"
            help="Au moins 8 caractères."
        />

        <x-password
            name="password_confirmation"
            id="update_password_password_confirmation"
            label="Confirmer le mot de passe"
            autocomplete="new-password"
            errorBag="updatePassword"
        />

        <div class="d-flex align-items-center gap-3">
            <x-button>Enregistrer</x-button>

            @if (session('status') === 'password-updated')
                <span class="text-success small">Enregistré.</span>
            @endif
        </div>
    </form>
</section>
