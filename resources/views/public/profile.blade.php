{{-- PROFIL PUBLIC — la page ouverte après un scan.

     C'est la page la plus légère du produit : elle arrive souvent en 3G, sur
     un téléphone, depuis un QR Code. Aucune image, aucune police distante,
     tout en HTML et CSS.

     Props : $profile (chargé avec socialLinks et user.subscriptions) --}}
{{-- $apercuUrl : l'image qui s'affichera dans WhatsApp. Elle est ABSOLUE —
     les robots des messageries ne résolvent aucun chemin relatif, et une URL
     relative donne le même résultat qu'une balise absente. --}}
<x-public-profile-layout
    :title="$profile->full_name"
    :description="$profile->job_title.($profile->company ? ' · '.$profile->company : '')"
    :apercu-url="$apercuUrl ?? null"
>
    <div class="pub">
        <x-phone :profile="$profile" size="lg" :animate="false" />

        {{-- Actions réelles, hors du cadre : le téléphone est une mise en
             scène, ces liens-ci sont ceux qu'on touche. --}}
        <div class="pub__actions">
            @if ($profile->phone)
                <a href="tel:{{ $profile->phone }}" class="btn-pill btn-dark">Appeler</a>
            @endif

            @if ($profile->whatsapp_href)
                <a href="{{ $profile->whatsapp_href }}" class="btn-pill btn-outline"
                   target="_blank" rel="noopener">WhatsApp</a>
            @endif

            @if ($profile->public_email)
                <a href="mailto:{{ $profile->public_email }}" class="btn-pill btn-outline">E-mail</a>
            @endif
        </div>

        @if ($profile->socialLinks->isNotEmpty())
            <div class="pub__socials">
                @foreach ($profile->socialLinks as $lien)
                    <a href="{{ $lien->url }}" class="pub__social"
                       target="_blank" rel="noopener">{{ $lien->platform_label }}</a>
                @endforeach
            </div>
        @endif

        <p class="pub__foot">
            Profil professionnel créé avec {{ config('app.name') }} —
            <a href="{{ route('home') }}">créer le mien</a>
        </p>
    </div>
</x-public-profile-layout>
