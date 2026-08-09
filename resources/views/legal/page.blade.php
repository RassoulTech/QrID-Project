{{-- Gabarit des pages légales. Contenu à compléter avec un juriste avant
     l'ouverture commerciale : ces mentions sont obligatoires pour un service
     payant. Props : $title, $updatedAt, $blocks --}}
<x-public-layout :title="$title.' — '.config('app.name')" :description="$title">
    <section class="section">
        <div class="wrap">
            <div class="section-head">
                <span class="eyebrow">Informations légales</span>
                <h2>{{ $title }}</h2>
                <p>Dernière mise à jour : {{ $updatedAt }}</p>
            </div>

            <div style="max-width:720px;margin-top:44px">
                @foreach ($blocks as $block)
                    <h3 style="font-size:20px;font-weight:700;margin-top:32px">{{ $block['heading'] }}</h3>
                    <p style="color:var(--muted);margin-top:12px">{{ $block['text'] }}</p>
                @endforeach
            </div>
        </div>
    </section>
</x-public-layout>
