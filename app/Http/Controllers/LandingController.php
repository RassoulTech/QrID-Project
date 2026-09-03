<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Profile;
use App\Models\SocialLink;
use App\Models\Subscription;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Landing page. Toutes les données affichées viennent de la base ou de la
 * configuration : aucun contenu métier n'est écrit en dur dans les vues.
 */
class LandingController extends Controller
{
    public function index(): View
    {
        /*
         | LA MAQUETTE N'AFFICHE PLUS PERSONNE.
         |
         | Elle montrait le PREMIER PROFIL PUBLIÉ DE LA BASE, et le second dans
         | la section sombre. Sur la production, cela affichait sur la page
         | d'accueil publique le nom, la fonction, l'entreprise, le TÉLÉPHONE
         | et l'ADRESSE E-MAIL d'un client réel — à tout visiteur, sans qu'il
         | l'ait jamais demandé ni su.
         |
         | Le compteur de vues à côté du téléphone était pire encore : c'était
         | le nombre réel de consultations de ce compte. Une donnée
         | d'exploitation d'un client, publiée, et qui bougeait toute seule.
         |
         | Les deux maquettes viennent maintenant de `config/landing.php`.
         | Elles n'existent pas en base, ne sont enregistrées nulle part, et
         | ne changent que si on décide de les changer.
         |
         | Effet de bord bienvenu : l'accueil ne fait plus la requête de
         | profils ni son sous-comptage d'événements.
         */
        return view('welcome', [
            // Un visiteur va vers l'inscription ; un connecté vers son espace.
            'ctaUrl' => auth()->check() ? route('dashboard') : route('register'),

            'heroProfile' => $this->mockupProfile('mockup'),
            'showcaseProfile' => $this->mockupProfile('mockup_secondaire'),

            // Décoratif, pris en configuration. Voir le commentaire là-bas.
            'heroViews' => config('landing.mockup_vues'),

            /*
             | LE NOMBRE DE CARTES EN LIGNE — COMPTÉ, PAS ANNONCÉ.
             |
             | La page affichait « Plus de 500 professionnels » et « +500 »
             | dans la pastille des avatars. Rien ne mesurait ce nombre : il
             | était écrit dans un fichier de langue.
             |
             | Ce n'est pas un détail de rédaction. Un visiteur qui découvre
             | ensuite que le produit compte trois clients ne se dit pas
             | « ils ont arrondi » — il se dit qu'on lui a menti, et cesse de
             | croire le reste de la page, tarifs compris.
             |
             | On compte donc. Et comme afficher « 3 » au lancement
             | dessert autant qu'un mensonge, la vue n'affiche le chiffre
             | qu'au-delà d'un seuil crédible ; en dessous, elle dit la même
             | chose sans nombre. Le jour où le compteur passe le seuil, il
             | devient vrai tout seul — personne n'a à y penser.
             |
             | `cartesEnLigne()` est mise en cache une heure : la landing est
             | la page la plus visitée du produit, et ce compte n'a aucune
             | raison d'être refait à chaque visite.
             */
            'cartesEnLigne' => $this->cartesEnLigne(),
            'seuilVitrine' => (int) config('landing.seuil_vitrine', 50),

            // Les tarifs viennent de la table plans, jamais du gabarit.
            'plans' => Plan::active()->orderBy('price_fcfa')->get(),

            'trades' => config('landing.trades'),
            'figures' => config('landing.figures'),
            'steps' => config('landing.steps'),
        ]);
    }

    /**
     * Profil d'illustration, JAMAIS enregistré en base.
     *
     * La relation socialLinks est laissée non chargée à dessein : x-phone
     * affiche alors ses pastilles décoratives sans jamais interroger la base
     * depuis la vue.
     */
    /**
     * Combien de cartes sont RÉELLEMENT en ligne.
     *
     * Publiées ET portées par un abonnement en cours : une carte publiée
     * dont l'abonnement a expiré n'est plus consultable, et la compter
     * gonflerait le chiffre avec des pages qui répondent « indisponible ».
     */
    private function cartesEnLigne(): int
    {
        return Cache::remember('landing.cartes_en_ligne', now()->addHour(), fn () => Profile::query()
            ->where('is_active', true)
            ->whereNull('deactivated_at')
            ->whereHas('user.subscriptions', fn ($q) => $q
                ->where('status', Subscription::STATUS_ACTIVE)
                ->where('ends_at', '>', now()))
            ->count());
    }

    private function mockupProfile(string $cle): Profile
    {
        $donnees = config('landing.'.$cle);
        $reseaux = $donnees['reseaux'] ?? [];
        unset($donnees['reseaux']);

        $profile = new Profile($donnees);

        /*
         | LA RELATION EST POSÉE À LA MAIN, JAMAIS INTERROGÉE.
         |
         | `x-phone` ne lit `socialLinks` que si la relation est déjà chargée —
         | c'est ce qui garantit qu'aucune requête ne part d'une vue. En la
         | posant ici avec des objets construits en mémoire, la maquette
         | obtient de vrais réseaux sans toucher la base.
         |
         | Sans cela, le composant complétait la grille jusqu'à six tuiles avec
         | des réseaux inventés à l'affichage : la maquette montrait alors trois
         | comptes que le profil illustré ne possède pas.
         */
        $profile->setRelation(
            'socialLinks',
            collect($reseaux)->map(fn (array $r) => new SocialLink($r))
        );

        return $profile;
    }
}
