<section>
    <form method="post" action="{{ route('compte.update') }}" novalidate>
        @csrf
        @method('patch')

        <x-input
            name="name"
            label="Nom complet"
            :value="$user->name"
            autocomplete="name"
            :required="true"
        />

        <x-input
            name="email"
            type="email"
            label="Adresse e-mail"
            :value="$user->email"
            autocomplete="username"
            :required="true"
        />

        <div class="d-flex align-items-center gap-3">
            <x-button>Enregistrer</x-button>

            @if (session('status') === 'account-updated')
                <span class="text-success small">Enregistré.</span>
            @endif
        </div>
    </form>
</section>
