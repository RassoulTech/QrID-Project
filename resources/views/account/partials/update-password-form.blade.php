{{--
  Changement de mot de passe.

  DEUX ÉTATS, ET LA DIFFÉRENCE N'EST PAS COSMÉTIQUE.

  Un compte créé par Google n'a PAS de mot de passe. Lui réclamer son « mot de
  passe actuel » est une impasse : le champ ne peut jamais être rempli
  correctement, et l'écran refuse indéfiniment sans expliquer pourquoi.

  Dans ce cas on ne demande donc que le nouveau mot de passe, et l'on dit
  clairement ce que l'opération apporte : un SECOND moyen d'accès, en plus de
  Google, et non son remplacement.
--}}
@php
    $aUnMotDePasse = auth()->user()->hasPassword();
@endphp

<section>
    @unless ($aUnMotDePasse)
        <div class="mail-spam mb-3">
            <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2m3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2"/>
            </svg>
            <span>
                Vous vous connectez avec Google, sans mot de passe.
                En définir un ici vous donnera un <strong>second moyen d'accès</strong> :
                la connexion par Google continuera de fonctionner.
            </span>
        </div>
    @endunless

    <form method="post" action="{{ route('password.update') }}" novalidate>
        @csrf
        @method('put')

        @if ($aUnMotDePasse)
            <x-password
                name="current_password"
                id="update_password_current_password"
                label="Mot de passe actuel"
                autocomplete="current-password"
                errorBag="updatePassword"
            />
        @endif

        <x-password
            name="password"
            id="update_password_password"
            label="{{ $aUnMotDePasse ? 'Nouveau mot de passe' : 'Mot de passe' }}"
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
            <x-button>{{ $aUnMotDePasse ? 'Enregistrer' : 'Définir mon mot de passe' }}</x-button>

            @if (session('status') === 'password-updated')
                <span class="text-success small">Enregistré.</span>
            @endif
        </div>
    </form>
</section>
