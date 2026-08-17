{{-- Profil public de démonstration.
     ACTION PRINCIPALE : comprendre à quoi ressemble un profil, puis créer le sien. --}}
<x-public-profile-layout
    :title="$profile ? $profile->full_name.' — exemple de profil' : 'Exemple de profil'"
    description="Exemple de profil professionnel numérique. Créez le vôtre en trois minutes.">

    <div class="alert alert-warning small mb-3" role="status">
        Exemple de démonstration.
    </div>

    <x-card class="text-center">
        @if ($profile)
            <div class="rounded-circle bg-primary mx-auto d-grid mb-3"
                 style="width:5rem;height:5rem;place-items:center;">
                <span class="text-white fw-bold fs-3">
                    {{ mb_substr($profile->first_name, 0, 1).mb_substr($profile->last_name, 0, 1) }}
                </span>
            </div>

            <h1 class="h5 fw-bold mb-1">{{ $profile->full_name }}</h1>
            <p class="text-secondary small mb-0">{{ $profile->job_title }}</p>
            <p class="text-secondary small mb-3">{{ $profile->company }}</p>

            @if ($profile->bio)
                <p class="small mb-3">{{ $profile->bio }}</p>
            @endif

            <div class="d-grid gap-2 mb-3">
                {{-- La démonstration doit montrer le geste qui TERMINE le
                     parcours : garder le contact. L'omettre ici donnerait à
                     voir un produit plus pauvre qu'il n'est. --}}
                <a href="{{ route('profile.vcard', $profile->slug) }}" class="btn btn-primary">
                    Enregistrer le contact
                </a>

                @if ($profile->phone)
                    <a href="{{ $profile->tel_href }}" class="btn btn-outline-secondary">Appeler</a>
                @endif
                @if ($profile->whatsapp_href)
                    <a href="{{ $profile->whatsapp_href }}" target="_blank" rel="noopener"
                       class="btn btn-outline-secondary">WhatsApp</a>
                @endif
                @if ($profile->public_email)
                    <a href="mailto:{{ $profile->public_email }}" class="btn btn-outline-secondary">
                        Envoyer un e-mail
                    </a>
                @endif
            </div>

            @if ($profile->socialLinks->isNotEmpty())
                <div class="d-flex justify-content-center flex-wrap gap-2">
                    @foreach ($profile->socialLinks as $link)
                        <a href="{{ $link->url }}" target="_blank" rel="noopener"
                           class="btn btn-sm btn-outline-secondary">{{ $link->platform_label }}</a>
                    @endforeach
                </div>
            @endif
        @else
            {{-- Base sans profil publié : message net plutôt qu'une 404 ou un
                 aperçu factice qui ferait croire à un vrai profil. --}}
            <x-empty-state
                title="Aucun profil de démonstration"
                message="Créez le vôtre en moins de trois minutes." />
        @endif
    </x-card>

    <div class="text-center mt-4">
        <x-button :href="$ctaUrl">Créer un compte</x-button>
        <p class="mt-2 mb-0">
            <a href="{{ route('home') }}" class="text-secondary small">Retour à l'accueil</a>
        </p>
    </div>
</x-public-profile-layout>
