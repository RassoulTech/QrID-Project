{{--
  /design-system — LA PLANCHE DE LA DIRECTION A « Teranga institutionnel ».

  Page locale (le contrôleur rend 404 ailleurs). Elle sert de RÉFÉRENCE aux
  lots qui composent les écrans : chaque composant y est rendu par son vrai
  gabarit, dans les deux thèmes. Ce qu'on juge ici est ce que le produit sert.

  Aucune valeur n'est écrite dans cette page : les pastilles de couleur
  lisent les jetons (x-nuancier), les styles viennent du socle.
  Props : $profile (profil de démonstration)
--}}
<x-public-layout title="Système de design">
<div class="ds">

    {{-- ===================== EN-TÊTE ===================== --}}
    <header class="wrap ds-tete">
        <p class="t-surtitre">Direction A · Teranga institutionnel</p>
        <h1 class="t-display">Le système de design</h1>
        <p class="t-lead">
            La rigueur d'une institution, l'hospitalité d'un salon dakarois. Vert
            baobab sur sable, claustra en filigrane, lisière tissée pour signer
            ce qui compte, l'or réservé à ce qui engage. Règles et mode d'emploi :
            <code>docs/DESIGN.md</code>.
        </p>
    </header>

    <div class="wrap ds-grille">

        {{-- ===================== CONCEPT ===================== --}}
        <section class="tuile tuile--couverture tuile--panneau ds-col-5 ds-concept avec-lisiere" aria-labelledby="ds-concept">
            <x-brand size="xl" tone="light" :words="false" :link="false" />
            <div>
                <h2 id="ds-concept" class="ds-concept__titre">Teranga institutionnel</h2>
                <p class="ds-concept__texte">
                    Une institution moderne qui accueille : la structure d'une banque,
                    la chaleur d'une maison. Le vert signe, le sable accueille, la
                    latérite alerte, l'or distingue.
                </p>
            </div>
            <div class="ds-rangee">
                <span class="puce-op">Claustra</span>
                <span class="puce-op">Lisière tissée</span>
                <span class="puce-op">Schibsted Grotesk</span>
            </div>
        </section>

        {{-- ===================== PALETTES ===================== --}}
        <section class="tuile ds-col-7" aria-labelledby="ds-clair">
            <div class="tuile__tete">
                <h2 id="ds-clair" class="tuile__titre">Palette — thème clair</h2>
                <span class="t-over">Rôle · hex</span>
            </div>
            <x-nuancier theme="clair" />
        </section>

        <section class="tuile ds-col-7 ds-col-debut-6 theme-dark ds-sombre" aria-labelledby="ds-sombre">
            <div class="tuile__tete">
                <h2 id="ds-sombre" class="tuile__titre">Palette — thème sombre</h2>
                <span class="t-over">Rôle · hex</span>
            </div>
            <x-nuancier theme="sombre" />
        </section>

        {{-- ===================== TYPOGRAPHIE ===================== --}}
        <section class="tuile ds-col-7" aria-labelledby="ds-type">
            <div class="tuile__tete">
                <h2 id="ds-type" class="tuile__titre">Échelle typographique — Schibsted Grotesk</h2>
                <span class="t-over">Variable 400 → 900 · woff2 latin 46,8 Ko</span>
            </div>
            <dl class="ds-type">
                <dt>Display · 72/850</dt><dd class="t-display">La carte qui reste.</dd>
                <dt>H1 · 44/820</dt><dd class="t-h1">Bonjour Mouhamed</dd>
                <dt>H2 · 36/800</dt><dd class="t-h2">Un prix clair</dd>
                <dt>H3 · 22/760</dt><dd class="t-h3">Comment ça marche</dd>
                <dt>Chapô · 20/400</dt><dd class="t-lead">On scanne, et vos coordonnées sont enregistrées.</dd>
                <dt>Corps · 16/400</dt><dd>Texte courant, jamais sous 16 px sur mobile. Chiffres tabulaires pour les montants.</dd>
                <dt>Surtitre · 13/650</dt><dd class="t-over">Abonnement · Standard</dd>
                <dt>Surtitre de section</dt><dd><span class="t-surtitre">Comment ça marche</span></dd>
                <dt>Chiffre · 44/820</dt><dd><span class="indic__valeur">1 248<small>FCFA</small></span></dd>
            </dl>
        </section>

        {{-- ===================== RAYONS, OMBRES, ESPACES ===================== --}}
        <section class="tuile ds-col-5" aria-labelledby="ds-formes">
            <div class="tuile__tete"><h2 id="ds-formes" class="tuile__titre">Rayons et ombres</h2></div>
            <div class="ds-rayons">
                @foreach (['xs' => '6 · badge', 'sm' => '10 · bouton', 'md' => '16 · carte', 'lg' => '24 · tuile', 'xl' => '32 · panneau'] as $rayon => $legende)
                    <figure class="ds-rayon">
                        <span class="ds-rayon__forme ds-rayon--{{ $rayon }}" aria-hidden="true"></span>
                        <figcaption>{{ $legende }}</figcaption>
                    </figure>
                @endforeach
            </div>
            <div class="ds-ombres">
                <span class="ds-ombre ds-ombre--1">Niveau 1<br>tuiles</span>
                <span class="ds-ombre ds-ombre--2">Niveau 2<br>flottants</span>
                <span class="ds-ombre ds-ombre--4">Objet<br>carte PVC</span>
            </div>
            <p class="t-caption ds-note">
                Espacements, base 4 : 4 · 8 · 12 · 16 · 20 · 24 · 32 · 40 · 48 · 64 · 80 · 96 · 128.
                Le trait fait le travail, l'ombre reste discrète. Sur la carte publique :
                niveau 1 seulement, aucun flou d'arrière-plan.
            </p>
        </section>

        {{-- ===================== COMPOSANTS, CLAIR ET SOMBRE ===================== --}}
        @foreach (['clair' => '', 'sombre' => 'theme-dark'] as $nomTheme => $classeTheme)
            <section @class(['tuile', 'tuile--gres', 'ds-col-6', 'ds-composants', $classeTheme]) aria-label="Composants — {{ $nomTheme }}">
                <p class="t-over">Composants · {{ $nomTheme }}</p>

                <div class="ds-rangee">
                    <x-button icon="user-plus" type="button">Enregistrer le contact</x-button>
                    <x-button variant="secondary" icon="share-2" type="button">Partager</x-button>
                    <x-button variant="quiet" type="button">Voir un exemple</x-button>
                </div>

                <div class="ds-rangee">
                    <span class="btn-pill btn-outline state-focus">Focus clavier</span>
                    <x-button variant="secondary" size="sm" icon="download" type="button">QR en PNG</x-button>
                    <x-button variant="danger" size="sm" type="button">Supprimer</x-button>
                    <button type="button" class="btn-icone" aria-label="Aide"><x-icone name="help-circle" /></button>
                </div>

                <div class="f">
                    <label class="f__label" for="ds-courriel-{{ $nomTheme }}">Adresse e-mail</label>
                    <input class="f__control is-invalid" type="email" id="ds-courriel-{{ $nomTheme }}"
                           value="mouhameddione@exemple" aria-invalid="true" aria-describedby="ds-erreur-{{ $nomTheme }}">
                    <span class="f__error ds-erreur" id="ds-erreur-{{ $nomTheme }}">
                        <x-icone name="alert-circle" class="icone--sm" />
                        Il manque la fin de l'adresse, par exemple nom@exemple.sn
                    </span>
                </div>

                <div class="f">
                    <label class="f__label" for="ds-formule-{{ $nomTheme }}">Formule <span class="f__opt">facultatif</span></label>
                    <select class="f__select" id="ds-formule-{{ $nomTheme }}">
                        <option>Standard — 3 mois</option>
                        <option>Standard — 12 mois</option>
                    </select>
                    <span class="f__hint">L'aide sous le champ : 15 px, 16 sur téléphone.</span>
                </div>

                <div class="ds-rangee">
                    <label class="form-check">
                        <input class="form-check-input" type="checkbox" checked>
                        <span class="form-check-label">Se souvenir de moi</span>
                    </label>
                </div>

                <div class="ds-rangee">
                    <x-badge variant="success">Réussi</x-badge>
                    <x-badge variant="warning">En attente</x-badge>
                    <x-badge variant="danger">Échoué</x-badge>
                    <x-badge variant="neutral">Brouillon</x-badge>
                    <x-badge variant="gold">Formule complète</x-badge>
                </div>

                <div class="ds-rangee">
                    <x-puce-operateur methode="wave" />
                    <x-puce-operateur methode="orange_money" />
                    <x-puce-operateur methode="free_money" />
                </div>
            </section>
        @endforeach

        {{-- ===================== MOTIFS ===================== --}}
        <section class="tuile ds-col-4" aria-labelledby="ds-claustra">
            <div class="tuile__tete"><h2 id="ds-claustra" class="tuile__titre">Claustra</h2><span class="t-over">SVG 0,4 Ko</span></div>
            <div class="claustra ds-motif" aria-hidden="true"></div>
            <p class="t-caption ds-note">Le bloc ajouré des façades modernistes de Dakar. Filigrane à 14 % sur le vert : couverture de carte, tuile d'abonnement, tarifs, verso PVC. Classe <code>.claustra</code>.</p>
        </section>

        <section class="tuile ds-col-4" aria-labelledby="ds-lisiere">
            <div class="tuile__tete"><h2 id="ds-lisiere" class="tuile__titre">Lisière tissée</h2><span class="t-over">SVG 0,3 Ko</span></div>
            <div class="ds-motif ds-motif--gres" aria-hidden="true">
                <span class="lisiere"></span>
                <span class="lisiere lisiere--fine"></span>
            </div>
            <p class="t-caption ds-note">Frise des pagnes manjak : losanges latérite et or. Elle ferme ce qui compte — bas de couverture, onglet actif, offre Standard, tranche de la carte. Classes <code>.lisiere</code>, <code>.avec-lisiere</code>.</p>
        </section>

        <section class="tuile ds-col-4" aria-labelledby="ds-mono">
            <div class="tuile__tete"><h2 id="ds-mono" class="tuile__titre">Monogramme</h2><span class="t-over">Repère QR + queue</span></div>
            <div class="ds-monos">
                <span class="ds-mono ds-mono--gres"><x-brand :words="false" :link="false" size="lg" /></span>
                <span class="ds-mono claustra"><x-brand :words="false" :link="false" size="lg" tone="light" /></span>
                <span class="ds-mono theme-dark"><x-brand :words="false" :link="false" size="lg" /></span>
            </div>
            <p class="t-caption ds-note">Le carré de repérage du QR Code devient un Q ; la queue en or le signe. Lisible à 16 px. Composant <code>&lt;x-brand&gt;</code>.</p>
        </section>

        {{-- ===================== PIÈCES D'ÉCRAN ===================== --}}
        <section class="tuile ds-col-8" aria-labelledby="ds-indic">
            <div class="tuile__tete">
                <h2 id="ds-indic" class="tuile__titre">Indicateurs chiffrés</h2>
                <a class="lien-fleche" href="#ds-alertes">Alertes <x-icone name="arrow-right" /></a>
            </div>
            <div class="ds-indics">
                <x-stat label="Vues" value="1 248" icon="eye" trend="up" trend-value="+293 en 7 jours" />
                <x-stat label="Scans du QR" value="312" icon="scan-line" trend="up" trend-value="+77 en 7 jours" />
                <x-stat label="Encaissé" value="266 000" unit="FCFA" icon="credit-card" hint="76 paiements réussis" />
            </div>
        </section>

        <section class="tuile tuile--couverture ds-col-4 avec-lisiere" aria-labelledby="ds-abo">
            <p class="t-over ds-or">Abonnement</p>
            <h2 id="ds-abo" class="tuile__titre ds-abo__titre">Standard</h2>
            <div class="ds-rangee">
                <span class="badge badge--couverture"><x-icone name="check" />Actif</span>
            </div>
            <div class="ds-rangee">
                <x-button variant="ivory" size="sm" type="button">Prolonger de 3 mois</x-button>
                <x-button variant="outline-ivory" size="sm" type="button">Historique</x-button>
            </div>
        </section>

        <section class="tuile ds-col-6" aria-labelledby="ds-alertes">
            <div class="tuile__tete"><h2 id="ds-alertes" class="tuile__titre">Alertes</h2></div>
            <div class="ds-pile">
                <x-alert type="warning" title="3 paiements à vérifier" :dismissible="false">Le plus ancien attend depuis 2 jours.</x-alert>
                <x-alert type="success" :dismissible="false">Profil publié.</x-alert>
                <x-alert type="danger" title="Paiement refusé" :dismissible="false">Vérifiez votre solde, puis recommencez.</x-alert>
                <x-alert type="info" :dismissible="false">Votre lien peut être modifié une seule fois.</x-alert>
            </div>
        </section>

        <section class="tuile ds-col-6" aria-labelledby="ds-nav">
            <div class="tuile__tete"><h2 id="ds-nav" class="tuile__titre">Navigation et repères</h2></div>
            <div class="ds-pile">
                <x-breadcrumb :items="[['label' => 'Gestion', 'url' => route('design.system')], ['label' => 'Paiements']]" />
                <x-wizard-progress :step="2" :labels="['Identité', 'Coordonnées', 'Apparence']" />
                <div class="ds-rangee">
                    <x-language-toggle />
                    <x-theme-toggle />
                    <x-whatsapp-fab variante="barre" message="Bonjour, je regarde le système de design." />
                </div>
                <x-google-button />
            </div>
        </section>

        <section class="tuile ds-col-12" aria-labelledby="ds-vide">
            <div class="tuile__tete"><h2 id="ds-vide" class="tuile__titre">État vide</h2></div>
            <x-empty-state icon="payment" title="Aucun paiement pour l'instant"
                           message="Les paiements apparaîtront ici dès le premier abonnement réglé.">
                <x-slot name="action"><x-button variant="secondary" size="sm" type="button">Voir les formules</x-button></x-slot>
            </x-empty-state>
        </section>
    </div>

    {{-- ===================== LES OBJETS DU PRODUIT =====================
         Composants des autres lots, rendus tels quels : la carte PVC (deux
         variantes, recto et verso), le téléphone, la carte publique. --}}
    <section class="wrap ds-objets" aria-labelledby="ds-objets">
        <p class="t-surtitre">Les objets</p>
        <h2 id="ds-objets" class="t-h2">Carte PVC, téléphone, carte publique</h2>
        <p class="t-lead">CR80 : 85,6 × 54 mm, coins à 3,7 % de la largeur. QR toujours sombre sur un panneau clair (ISO/IEC 18004).</p>

        <div class="ds-rangee ds-objets__rangee">
            <x-card-duo :profile="$profile" />
        </div>

        @foreach (\App\Enums\VarianteCarte::toutes() as $variante)
            @php
                // On clone le profil pour imposer la variante sans rien écrire.
                $exemplaire = clone $profile;
                $exemplaire->primary_color = $variante->value;
            @endphp
            <p class="t-over ds-objets__legende">Variante {{ $variante->libelle() }} — {{ $variante->description() }}</p>
            <div class="ds-faces">
                <x-card face="recto" :profile="$exemplaire" :variant="$variante->carte()" />
                <x-card face="verso" :profile="$exemplaire" :variant="$variante->carte()" />
            </div>
        @endforeach

        <div class="ds-rangee ds-objets__rangee">
            <x-phone :profile="$profile" size="lg" :animate="false" />
            <x-phone :profile="$profile" size="sm" :animate="false" />
        </div>

        @include('design-system.cartes-publiques', ['profile' => $profile])
    </section>
</div>
</x-public-layout>
