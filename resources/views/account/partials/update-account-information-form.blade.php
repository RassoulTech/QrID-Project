<section>
    <form method="post" action="{{ route('compte.update') }}" novalidate>
        @csrf

    <x-form-legende />
        @method('patch')

        <x-input
            name="name"
            :label="__('auth.champs.nom_complet')"
            :value="$user->name"
            autocomplete="name"
            :required="true"
        />

        <x-input
            name="email"
            type="email"
            :label="__('common.champs.email')"
            :value="$user->email"
            autocomplete="username"
            :required="true"
        />

        <div class="d-flex align-items-center gap-3">
            <x-button>{{ __('common.actions.enregistrer') }}</x-button>

            @if (session('status') === 'account-updated')
                <span class="text-success small">{{ __('profile.compte.enregistre') }}</span>
            @endif
        </div>
    </form>
</section>
