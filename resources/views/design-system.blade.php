{{-- Référence visuelle. Accessible uniquement en environnement local.
     Props : $profile (profil de démonstration) --}}
<x-public-layout title="Système de design">
    <section class="section">
        <div class="wrap">
            <h1 class="section-title">Système de design</h1>
            <p class="section-sub">Page locale. Voir <code>docs/DESIGN.md</code> pour les règles.</p>

            {{-- ---------------------------------------------------------- --}}
            <h2 class="section-title" style="margin-top:72px">Carte PVC</h2>
            <p class="section-sub">
                Ratio 1,586 (85,6 × 54 mm, ISO/IEC 7810 ID-1) · coins à 3 % de la
                largeur · QR inversé, modules blancs à même le vert · typographie
                en unités de conteneur.
            </p>
            <p class="section-sub">
                <strong>Recto</strong> — le porteur : nom, code, fonction.
                <strong>Verso</strong> — la plateforme, identique sur toutes les
                cartes, sans aucune donnée de profil.
            </p>

            <div style="margin-top:56px">
                <p class="section-sub" style="margin-bottom:24px">
                    Présentation « duo » — les deux faces en perspective, comme la référence.
                </p>
                <div style="display:flex;justify-content:center">
                    <x-pvc-card :profile="$profile" layout="duo" :flip="false" />
                </div>
            </div>

            {{-- Les deux faces CÔTE À CÔTE, même taille, pour juger
                 l'équilibre : recto centré et symétrique, verso aligné à
                 gauche et asymétrique. --}}
            <div style="margin-top:64px">
                <p class="section-sub" style="margin-bottom:24px">
                    Les deux faces à la même échelle.
                </p>

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:40px;align-items:start">
                    <div>
                        <div class="pvc pvc--md" style="--pvc-teinte:#0B3B2E;max-width:none">
                            <div class="pvc__scene">
                                @include('components.pvc-card-face-recto', ['profile' => $profile])
                            </div>
                        </div>
                        <p class="section-sub" style="margin-top:16px">
                            <strong>Recto</strong> — centré, symétrique, dominé par le QR Code.
                        </p>
                    </div>

                    <div>
                        <div class="pvc pvc--md" style="--pvc-teinte:#0B3B2E;max-width:none">
                            <div class="pvc__scene">
                                <x-pvc-card-face-verso :profile="$profile" />
                            </div>
                        </div>
                        <p class="section-sub" style="margin-top:16px">
                            <strong>Verso</strong> — aligné à gauche, asymétrique,
                            dominé par la marque. Aucun paramètre.
                        </p>
                    </div>
                </div>
            </div>

            <div style="margin-top:64px">
                <p class="section-sub" style="margin-bottom:24px">
                    Trois tailles, avec permutation.
                </p>
                <div style="display:flex;flex-wrap:wrap;gap:40px;justify-content:center;align-items:flex-start">
                    <x-pvc-card :profile="$profile" size="sm" />
                    <x-pvc-card :profile="$profile" size="md" />
                </div>
            </div>

            {{-- ---------------------------------------------------------- --}}
            <h2 class="section-title" style="margin-top:72px">Composant téléphone</h2>
            <p class="section-sub">Ratio 9/19.5 · 280px, 240px, 220px selon le contexte.</p>

            <div style="display:flex;flex-wrap:wrap;gap:64px;justify-content:center;align-items:flex-start;margin-top:56px">
                <div style="text-align:center">
                    <x-phone :profile="$profile" size="lg" :animate="false" />
                    <p class="section-sub" style="margin-top:20px">Grande — 280px (hero)</p>
                </div>

                <div style="text-align:center">
                    <x-phone :profile="$profile" size="sm" :animate="false" />
                    <p class="section-sub" style="margin-top:20px">Réduite — 240px (section sombre)</p>
                </div>
            </div>

            <div style="margin-top:64px">
                <p class="section-sub">Sur socle, comme dans la section sombre :</p>
                <div class="showcase" style="border-radius:32px;margin-top:24px">
                    <div class="wrap" style="display:flex;justify-content:center">
                        <div class="phone-pedestal">
                            <x-phone :profile="$profile" size="sm" :animate="false" />
                        </div>
                    </div>
                </div>
            </div>

            {{-- ---------------------------------------------------------- --}}
            {{-- BOUTONS — chaque variante dans ses CINQ états, côte à côte.
                 Un texte invisible au repos se voit immédiatement ici.
                 Les états simulés utilisent les classes Bootstrap :hover/:focus
                 forcées par la classe utilitaire `.state-*` définie plus bas. --}}
            <h2 class="section-title" style="margin-top:96px">Boutons — cinq états</h2>
            <p class="section-sub">
                Aucun libellé ne doit dépendre du survol pour être lisible.
            </p>

            <div style="overflow-x:auto;margin-top:40px">
                <table style="width:100%;min-width:720px;border-collapse:collapse;text-align:center">
                    <thead>
                        <tr>
                            <th style="text-align:left;padding:12px" class="figure-card__l">Variante</th>
                            <th style="padding:12px" class="figure-card__l">Repos</th>
                            <th style="padding:12px" class="figure-card__l">Survol</th>
                            <th style="padding:12px" class="figure-card__l">Focus</th>
                            <th style="padding:12px" class="figure-card__l">Actif</th>
                            <th style="padding:12px" class="figure-card__l">Désactivé</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ([
                            'dark' => 'Principal — blanc sur #0B3B2E',
                            'outline' => 'Secondaire — #0A1F1A sur blanc',
                        ] as $variant => $label)
                            <tr>
                                <td style="text-align:left;padding:16px 12px" class="t-label">{{ $label }}</td>
                                <td style="padding:16px 12px"><span class="btn-pill btn-{{ $variant }}">Action</span></td>
                                <td style="padding:16px 12px"><span class="btn-pill btn-{{ $variant }} state-hover">Action</span></td>
                                <td style="padding:16px 12px"><span class="btn-pill btn-{{ $variant }} state-focus">Action</span></td>
                                <td style="padding:16px 12px"><span class="btn-pill btn-{{ $variant }} state-active">Action</span></td>
                                <td style="padding:16px 12px"><span class="btn-pill btn-{{ $variant }}" style="opacity:.5">Action</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Variante posée sur fond sombre --}}
            <div class="showcase" style="border-radius:24px;margin-top:32px;padding:40px 0">
                <div class="wrap" style="text-align:center">
                    <p class="figure-card__l" style="color:rgba(255,255,255,.7)">
                        Sur fond sombre — #0B3B2E sur blanc
                    </p>
                    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:16px">
                        <span class="btn-pill btn-light">Repos</span>
                        <span class="btn-pill btn-light state-hover">Survol</span>
                        <span class="btn-pill btn-light state-focus">Focus</span>
                        <span class="btn-pill btn-light state-active">Actif</span>
                    </div>
                </div>
            </div>

            {{-- ---------------------------------------------------------- --}}
            <h2 class="section-title" style="margin-top:96px">Palette</h2>
            <div class="figures__grid" style="margin-top:32px">
                @foreach ([
                    'Vert foncé #0B3B2E' => '#0B3B2E',
                    'Vert accent #1E9E7A' => '#1E9E7A',
                    'Vert clair #E4F2EC' => '#E4F2EC',
                    'Gris fond #F2F3F1' => '#F2F3F1',
                    'Texte #0A1F1A' => '#0A1F1A',
                    'Gris second. #5C6B66' => '#5C6B66',
                ] as $label => $hex)
                    <div class="figure-card">
                        <div style="height:56px;border-radius:12px;background:{{ $hex }}"></div>
                        <div class="figure-card__l" style="margin-top:12px">{{ $label }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</x-public-layout>
