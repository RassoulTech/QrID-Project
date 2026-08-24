{{-- Référence visuelle. Accessible uniquement en environnement local.
     Props : $profile (profil de démonstration) --}}
<x-public-layout title="Système de design">
    <section class="section">
        <div class="wrap">
            <h1 class="section-title">Système de design</h1>
            <p class="section-sub">Page locale. Voir <code>docs/DESIGN.md</code> pour les règles.</p>

            {{-- ================= LE LOGO =================

                 UN SEUL COMPOSANT POUR TOUT LE PRODUIT : navbar, menu latéral,
                 pages d'erreur, pied public, cartes PVC et PDF d'impression.
                 Un second dessin quelque part serait un second logo, qui
                 divergerait au premier ajustement.

                 Le monogramme est CALCULÉ à partir de APP_NAME, jamais écrit
                 en dur : « QrID » → « QI ». Changer le nom du produit met le
                 logo à jour partout, sans toucher un fichier. --}}
            <h2 class="section-title" style="margin-top:72px">Logo</h2>
            <p class="section-sub">
                Monogramme <code>{{ \App\Support\Marque::monogramme() }}</code>, calculé
                depuis <code>APP_NAME</code>. Le nom du ton désigne le <em>texte</em>,
                pas le fond : <code>dark</code> se pose sur un fond clair,
                <code>light</code> sur un fond sombre.
            </p>
            <p class="section-sub">
                Dans les deux tons, <strong>les lettres du monogramme restent
                blanches</strong> : seul le carré change de teinte. Un monogramme
                tantôt blanc sur vert, tantôt vert sur blanc, donnerait deux logos.
            </p>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:24px;margin-top:32px">
                <div style="padding:28px;border:1px solid rgba(10,31,26,.10);border-radius:20px;background:#FFFFFF">
                    <p class="section-sub" style="margin:0 0 20px"><code>tone="dark"</code> — sur fond clair</p>
                    <div style="display:flex;flex-direction:column;gap:18px;align-items:flex-start">
                        <x-brand size="lg" :link="false" />
                        <x-brand size="md" :link="false" />
                        <x-brand size="sm" :link="false" />
                        <x-brand :words="false" :link="false" size="lg" />
                    </div>
                </div>

                <div style="padding:28px;border-radius:20px;background:#0B3B2E">
                    <p class="section-sub" style="margin:0 0 20px;color:rgba(255,255,255,.7)"><code>tone="light"</code> — sur fond sombre</p>
                    <div style="display:flex;flex-direction:column;gap:18px;align-items:flex-start">
                        <x-brand size="lg" tone="light" :link="false" />
                        <x-brand size="md" tone="light" :link="false" />
                        <x-brand size="sm" tone="light" :link="false" />
                        <x-brand :words="false" :link="false" tone="light" size="lg" />
                    </div>
                </div>
            </div>

            {{-- ---------------------------------------------------------- --}}
            <h2 class="section-title" style="margin-top:72px">Carte PVC</h2>
            <p class="section-sub">
                Ratio 1,586 (85,6 × 54 mm, ISO/IEC 7810 ID-1) · coins à angle vif ·
                typographie en unités de conteneur · deux variantes, verte et blanche.
            </p>
            <p class="section-sub">
                <strong>Recto</strong> — le porteur : nom, code, fonction. Son QR mène
                à sa carte.
                <strong>Verso</strong> — la plateforme, identique sur toutes les
                cartes, sans aucune donnée de profil. Son QR mène à la plateforme.
            </p>
            <p class="section-sub">
                <strong>Densité</strong> — marges à 6 % de la largeur, QR à ≈47 % de
                la hauteur, aucune zone morte de plus d'un quart de la hauteur.
            </p>

            <div style="margin-top:56px">
                <p class="section-sub" style="margin-bottom:24px">
                    Présentation « duo » — les deux faces en perspective, comme la référence.
                </p>
                <div style="display:flex;justify-content:center">
                    <x-card-duo :profile="$profile" />
                </div>
            </div>

            {{-- ================= LES QUATRE FACES =================

                 Deux variantes × deux faces. C'est la planche de référence :
                 tout ce qui peut partir à l'impression est ici, et rien
                 d'autre n'existe.

                 On les montre à la MÊME ÉCHELLE et côte à côte, car c'est le
                 seul moyen de juger ce qui compte réellement — le contraste
                 du QR Code sur chaque fond, et l'équilibre entre un recto
                 centré et un verso asymétrique.

                 La carte de gauche est celle du profil réel, avec son QR ;
                 celle de droite force l'autre variante sur le même profil. --}}
            <div style="margin-top:64px">
                <p class="section-sub" style="margin-bottom:8px">
                    Les quatre faces — deux variantes, recto et verso.
                </p>
                <p class="section-sub" style="margin-bottom:24px;max-width:60ch">
                    La variante <strong>blanche</strong> est conforme à ISO/IEC 18004
                    (code sombre sur fond clair). La <strong>verte</strong> l'inverse :
                    les lecteurs modernes la gèrent, d'autres non, et leur échec est
                    silencieux. À qualité d'impression égale, la blanche scanne plus sûrement.
                </p>

                @foreach (\App\Enums\VarianteCarte::toutes() as $variante)
                    @php
                        // On clone le profil pour lui imposer la variante sans
                        // toucher à la base : cette page ne doit rien écrire.
                        $exemplaire = clone $profile;
                        $exemplaire->primary_color = $variante->value;
                    @endphp

                    <p class="section-sub" style="margin:32px 0 16px">
                        <strong>Variante {{ $variante->libelle() }}</strong> — {{ $variante->description() }}
                    </p>

                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:40px;align-items:start">
                        <div>
                            <x-card face="recto" :profile="$exemplaire" :variant="$variante->carte()" />
                            <p class="section-sub" style="margin-top:16px">
                                <strong>Recto</strong> — centré, symétrique. Son QR mène
                                à la carte du porteur.
                            </p>
                        </div>

                        <div>
                            <x-card face="verso" :profile="$exemplaire" :variant="$variante->carte()" />
                            <p class="section-sub" style="margin-top:16px">
                                <strong>Verso</strong> — asymétrique, identique sur toutes
                                les cartes. Son QR mène à la <em>plateforme</em>.
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div style="margin-top:64px">
                <p class="section-sub" style="margin-bottom:24px">
                    Trois tailles, avec permutation.
                </p>
                <div style="display:flex;flex-wrap:wrap;gap:40px;justify-content:center;align-items:flex-start">
                    <x-card face="recto" :profile="$profile" />
                    <x-card face="verso" :profile="$profile" />
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

            @include('design-system.cartes-publiques', ['profile' => $profile])

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
