<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\ProfileEvent;
use App\Services\QrCodeService;
use App\Services\SharePreviewService;
use App\Services\VCardService;
use App\Support\Theme;
use App\Support\Whatsapp;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PublicProfileController extends Controller
{
    /**
     * Profil public — la page que les contacts ouvrent après un scan.
     *
     * Un profil inactif n'est PAS visible : c'est la règle qui rend
     * l'abonnement nécessaire. Une 404 plutôt qu'un 403, pour ne rien
     * révéler de l'existence du profil.
     */
    public function show(Request $request, string $slug): View|Response
    {
        /*
         | ═══════════════════════════════════════════════════════════════
         | SEPT ALLERS-RETOURS VERS LA BASE, POUR UNE PAGE QUI NE CHANGE PAS
         | ═══════════════════════════════════════════════════════════════
         | MESURÉ EN PRODUCTION, en comparant deux pages :
         |
         |     page légale (aucune requête)   250 à 380 ms
         |     carte publique                 470 à 530 ms
         |
         | L'écart — environ 230 ms — n'est pas du calcul : c'est le temps
         | passé à attendre une base DISTANTE. Le profil, ses liens, son
         | propriétaire, l'abonnement de celui-ci, la lecture du cache et
         | l'écriture de la visite font sept allers-retours, à une trentaine
         | de millisecondes chacun.
         |
         | Mémoriser le HTML seul ne réglait rien : cela retirait du calcul
         | — qui n'était pas le problème — et AJOUTAIT un aller-retour pour
         | lire le cache. C'est ce qu'a montré la mesure après le premier
         | essai : aucun gain visible.
         |
         | On mémorise donc TOUT ce qu'il faut pour servir la page, y compris
         | l'identifiant du profil dont le comptage a besoin. Il reste deux
         | allers-retours : lire le cache, écrire la visite.
         |
         | ═══════════════════════════════════════════════════════════════
         | LA VISIBILITÉ EST MÉMORISÉE AUSSI, ET C'EST LE POINT DÉLICAT
         | ═══════════════════════════════════════════════════════════════
         | Une carte dépubliée pourrait donc rester visible le temps du
         | cache. Deux garde-fous rendent ce délai acceptable :
         |
         |   · l'observateur PURGE cette entrée dès qu'un profil est modifié,
         |     dépublié ou désactivé — une décision de modération prend effet
         |     immédiatement, ce qui est la seule chose non négociable ;
         |   · la durée de vie est courte. Seule l'expiration d'un abonnement
         |     survient sans qu'on touche au profil, et deux minutes de carte
         |     encore visible après l'échéance ne lèsent personne.
         */
        $cache = Cache::remember(
            'carte:'.$slug.':'.App::getLocale().':'.(Theme::estSombre() ? 'sombre' : 'clair'),
            now()->addMinutes(2),
            function () use ($slug) {
                $profile = Profile::query()
                    ->with(['socialLinks', 'user.subscriptions'])
                    ->where('slug', $slug)
                    ->first();

                if ($profile === null) {
                    return ['etat' => 'introuvable'];
                }

                if (! $profile->isPubliclyVisible()) {
                    // La page « carte indisponible » est mémorisée elle aussi :
                    // une carte hors ligne peut recevoir autant de scans qu'une
                    // carte en ligne — les QR Codes déjà imprimés continuent
                    // d'exister.
                    return [
                        'etat' => 'hors-ligne',
                        'id' => $profile->id,
                        'html' => $this->carteInactive($profile)->getContent(),
                    ];
                }

                return [
                    'etat' => 'en-ligne',
                    'id' => $profile->id,
                    'html' => $this->rendu($profile),
                ];
            },
        );

        if ($cache['etat'] === 'introuvable') {
            abort(404);
        }

        // LE COMPTAGE RESTE EN DEHORS DU CACHE. Servir un rendu mémorisé sans
        // compter la visite ferait s'arrêter net les statistiques d'un client
        // dont la carte marche bien — au moment précis où elle commence à
        // circuler. L'identifiant vient du cache : aucune requête de plus.
        $this->enregistrerVisite($request, $cache['id']);

        return response(
            $cache['html'],
            $cache['etat'] === 'hors-ligne' ? 404 : 200,
        );
    }

    /** Le HTML de la carte publique — appelé seulement quand le cache est froid. */
    private function rendu(Profile $profile): string
    {
        return view('public.profile', [
            'profile' => $profile,

            /*
             | LE LIEN DE PARTAGE WHATSAPP, CONSTRUIT CÔTÉ SERVEUR.
             |
             | Il pourrait être assemblé dans le gabarit — c'est ce que
             | faisaient les quatre autres endroits du produit qui parlent à
             | WhatsApp, chacun à sa façon. Le construire ici met le texte
             | dans les fichiers de langue, l'encodage dans un seul endroit,
             | et laisse la vue afficher au lieu de calculer.
             |
             | Il ne porte QUE ce qui est déjà public sur cette page : le nom
             | affiché et son adresse. Voir la règle en tête de
             | App\Support\Whatsapp.
             */
            'partageWhatsapp' => Whatsapp::partageDeLaCarte($profile, url()->current()),

            /*
             | L'IMAGE DE PARTAGE. C'est elle qui rend le lien visible dans une
             | conversation WhatsApp — sans elle, l'aperçu se réduit à une
             | ligne de titre grise que personne ne remarque.
             |
             | L'ADRESSE EST ABSOLUE : les robots des messageries ne résolvent
             | aucun chemin relatif, et une URL relative donne exactement le
             | même résultat qu'une balise absente, sans rien signaler.
             |
             | LA GÉNÉRATION NE PEUT PAS CASSER CETTE PAGE. C'est la page qui
             | prend tout le trafic du produit : un défaut de GD, un disque
             | plein ou une photo illisible doivent coûter une vignette, jamais
             | la carte elle-même. En cas d'échec, la balise disparaît et le
             | partage retombe simplement sur son ancien comportement.
             */
            'apercuUrl' => $this->apercu($profile),

            /*
             | LE QR DE LA CARTE, POSÉ EN LIGNE.
             |
             | Il sert à montrer sa carte à une TROISIÈME personne sans lui
             | demander de recopier une adresse. Le SVG est inséré dans le HTML
             | plutôt que servi en fichier : sur un réseau lent, une requête de
             | plus est une seconde de plus.
             |
             | Même règle que l'aperçu : sa génération ne peut pas casser la
             | page. En cas d'échec, le lien disparaît, la carte reste.
             */
            'qrSvg' => $this->qr($profile),

            /*
             | LA PHOTO — VÉRIFIÉE SUR LE DISQUE, PAS EN BASE.
             |
             | cover_path renseigné ne veut pas dire fichier présent. Sur un
             | stockage éphémère — FILESYSTEM_DISK=local dans un conteneur
             | Render — chaque déploiement efface les photos téléversées : la
             | colonne reste, le fichier disparaît.
             |
             | La page affichait alors une balise <img> vers un 404, donc un
             | bandeau vide avec une icône d'image cassée. Constaté en
             | production le 18 août : la photo ET l'aperçu de partage
             | répondaient 404 quelques heures après avoir répondu 200.
             |
             | On vérifie donc l'EXISTENCE du fichier. En son absence, les
             | initiales prennent le relais — jamais un vide.
             */
            'couvertureUrl' => $this->couvertureUrl($profile),
        ])->render();
    }

    /**
     * ENREGISTREMENT DE LA VISITE — vue directe ou scan de QR Code.
     *
     * ═══════════════════════════════════════════════════════════════════════
     * CE COMPTEUR N'EXISTAIT PAS
     * ═══════════════════════════════════════════════════════════════════════
     * `ProfileEvent` était LU partout — tableau de bord, statistiques,
     * administration — et écrit NULLE PART. Tous les écrans affichaient donc
     * « 0 vue » avec aplomb, quel que soit le trafic réel. Le client concluait
     * que personne ne regardait sa carte.
     *
     * LE PARAMÈTRE DE PROVENANCE distingue les deux gestes : le QR Code encode
     * l'adresse avec `?src=qr`, un lien collé dans WhatsApp ne l'a pas. Sans
     * cette distinction, « vues » et « scans » afficheraient le même nombre et
     * ne diraient plus rien.
     *
     * L'ÉCHEC EST AVALÉ, VOLONTAIREMENT. Une statistique n'a pas le droit
     * d'empêcher une carte de s'afficher : c'est la page qui prend tout le
     * trafic du produit, et un compteur ne vaut pas une visite perdue.
     */
    private function enregistrerVisite(Request $request, int $profileId, ?string $type = null, ?string $canal = null): void
    {
        try {
            ProfileEvent::create([
                'profile_id' => $profileId,
                'type' => $type ?? ($request->query('src') === 'qr'
                    ? ProfileEvent::TYPE_SCAN
                    : ProfileEvent::TYPE_VIEW),

                // Nul pour les trois types historiques : une vue n'a pas de
                // moyen. Renseigné pour un partage, où il dit lequel.
                'canal' => $canal,

                // L'adresse IP n'est jamais stockée en clair : on ne garde
                // qu'une empreinte, suffisante pour dédoublonner, inutilisable
                // pour identifier quelqu'un.
                'ip_hash' => hash('sha256', (string) $request->ip()),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
                'referer' => mb_substr((string) $request->headers->get('referer'), 0, 255),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Visite non enregistrée.', [
                'profil' => $profileId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * « PARTAGER » A ÉTÉ ACTIONNÉ.
     *
     * ═══════════════════════════════════════════════════════════════════
     * CE QUE CETTE ROUTE ENREGISTRE, ET CE QU'ELLE REFUSE DE PRÉTENDRE
     * ═══════════════════════════════════════════════════════════════════
     * Un partage INITIÉ : quelqu'un a appuyé, WhatsApp ou la feuille du
     * système s'est ouvert. Rien de plus.
     *
     * Ce qui se passe ensuite échappe entièrement à l'application. Elle ne
     * voit pas le message, ne connaît pas le destinataire, et ne saura
     * jamais si l'envoi a eu lieu. Un compteur nommé « messages envoyés »
     * serait un chiffre inventé — et un chiffre inventé sur un tableau de
     * bord fait douter de tous les autres.
     *
     * ═══════════════════════════════════════════════════════════════════
     * ELLE REND TOUJOURS 204, MÊME QUAND ELLE N'ÉCRIT RIEN
     * ═══════════════════════════════════════════════════════════════════
     * Un slug inconnu ou une carte hors ligne ne donnent PAS 404. Cette
     * route est publique et sans authentification : un 404 permettrait de
     * demander « ce slug existe-t-il ? » autant de fois qu'on veut, et
     * d'énumérer les cartes du produit une par une.
     *
     * Le navigateur, lui, n'a rien à faire de la réponse : il envoie et
     * poursuit. Ne rien dire ne lui coûte rien et ne renseigne personne.
     */
    public function partage(Request $request, string $slug): Response
    {
        $valide = $request->validate([
            'canal' => ['required', Rule::in(ProfileEvent::CANAUX)],
        ]);

        $profile = Profile::query()->where('slug', $slug)->first();

        if ($profile && $profile->isPubliclyVisible()) {
            $this->enregistrerVisite($request, $profile->id, ProfileEvent::TYPE_SHARE, $valide['canal']);
        }

        return response()->noContent();
    }

    /**
     * L'appareil est-il un téléphone ou une tablette ?
     *
     * ═══════════════════════════════════════════════════════════════════
     * RENIFLER L'AGENT UTILISATEUR EST UNE MAUVAISE HABITUDE — SAUF ICI
     * ═══════════════════════════════════════════════════════════════════
     * On ne l'emploie pas pour deviner une taille d'écran, ce que les
     * requêtes média font mieux, ni pour supposer une capacité, ce que la
     * détection de fonctionnalité fait mieux.
     *
     * Il s'agit d'autre chose : le MÊME en-tête HTTP produit deux
     * comportements SYSTÈME opposés. Un Content-Disposition fait ouvrir les
     * contacts sur un ordinateur et ranger un fichier sur un iPhone. Aucune
     * négociation de contenu n'exprime cela — il n'existe pas d'en-tête
     * « ce que votre système fera d'un text/vcard ».
     *
     * La liste est volontairement courte et grossière : se tromper coûte un
     * fichier téléchargé au lieu d'une feuille de contact, jamais une page
     * cassée.
     */
    private function estUnMobile(Request $request): bool
    {
        return (bool) preg_match(
            '/Android|iPhone|iPad|iPod|Mobile Safari|Opera Mini/i',
            (string) $request->userAgent()
        );
    }

    /**
     * L'ADRESSE DE LA COUVERTURE — l'unique image d'une carte.
     *
     * ═══════════════════════════════════════════════════════════════════
     * IL Y AVAIT DEUX MÉCANIQUES POUR DEUX IMAGES. IL N'EN RESTE QU'UNE.
     * ═══════════════════════════════════════════════════════════════════
     * Le produit demandait autrefois un portrait ET une bannière. L'assistant
     * n'en demande plus qu'une, mais ce contrôleur cherchait encore les deux :
     * une méthode `photo()`, un paramètre `$genre`, deux branches à chaque
     * lecture. La branche « photo » ne pouvait plus rien trouver.
     *
     * ═══════════════════════════════════════════════════════════════════
     * ON TESTE LES OCTETS, PAS LE FICHIER
     * ═══════════════════════════════════════════════════════════════════
     * `couvertureBinaire()` lit la base quand le disque est vide — et remet
     * le fichier en place au passage. Tester l'existence du FICHIER privait
     * d'image toutes les cartes servies après un déploiement, alors que
     * l'image était bel et bien conservée.
     */
    private function couvertureUrl(Profile $profile): ?string
    {
        if (blank($profile->cover_path)) {
            return null;
        }

        try {
            return $profile->couvertureBinaire() !== null
                ? Storage::url($profile->cover_path)
                : null;
        } catch (\Throwable $e) {
            Log::warning('Couverture de carte illisible', [
                'slug' => $profile->slug,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /** Le QR Code de la carte, en SVG, ou null s'il n'a pas pu être produit. */
    private function qr(Profile $profile): ?string
    {
        try {
            return app(QrCodeService::class)->svg($profile);
        } catch (\Throwable $e) {
            Log::warning('QR de la carte publique non produit', [
                'slug' => $profile->slug,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * La carte existe mais n'est pas en ligne.
     *
     * ═══════════════════════════════════════════════════════════════════════
     * POURQUOI CETTE PAGE, ET POURQUOI ELLE RÉPOND QUAND MÊME 404
     * ═══════════════════════════════════════════════════════════════════════
     * Le premier geste d'un client qui vient de créer sa carte est de scanner
     * son propre QR pour voir si « ça marche ». Il tombait sur une page
     * d'erreur nue. Il n'avait aucun moyen de savoir que rien n'était cassé et
     * qu'il lui restait une seule chose à faire — activer.
     *
     * LE STATUT RESTE 404, ET C'EST VOULU. Renvoyer un 200 ici distinguerait
     * une carte inactive d'un slug inexistant : on pourrait énumérer les
     * comptes en essayant des adresses. Le corps de la réponse est utile, le
     * code ne dit rien de plus qu'avant.
     *
     * LA PAGE NE SUPPOSE AUCUNE SESSION. Le scan se fait au téléphone, souvent
     * sur un navigateur où l'on n'est pas connecté : une page réservée au
     * propriétaire authentifié n'aurait servi que dans le cas le plus rare. Le
     * nom du profil n'est donc affiché QUE si le visiteur est bien son
     * propriétaire — sinon la page ne révèle rien de qui que ce soit.
     */
    private function carteInactive(Profile $profile): Response
    {
        /*
         | L'ORDRE DES CAS EST LA LOGIQUE MÉTIER ELLE-MÊME.
         |
         | Une suspension passe avant tout : dire « payez » à un compte suspendu
         | par l'administration l'enverrait payer pour rien.
         |
         | L'abonnement passe avant le brouillon : sans abonnement actif,
         | l'activation est de toute façon impossible — c'est donc lui qui
         | bloque, et le message doit nommer le vrai obstacle.
         */
        $raison = match (true) {
            $profile->isDeactivated() => 'suspendue',
            ! $profile->user?->hasActiveSubscription() => 'abonnement',
            default => 'brouillon',
        };

        $proprietaire = auth()->id() !== null && auth()->id() === $profile->user_id;

        return response()->view('public.carte-inactive', [
            'profile' => $profile,
            'raison' => $raison,
            'proprietaire' => $proprietaire,

            /*
             | LE DERNIER SAUT, SUPPRIMÉ.
             |
             | Tant qu'aucune passerelle n'encaisse, « Activer ma carte »
             | menait au paiement, qui ne menait nulle part. L'exploitant
             | tournait entre les deux écrans sans jamais atteindre la
             | prolongation — qui est pourtant la seule voie ouverte, et dont
             | il a déjà les droits.
             |
             | Quand celui qui regarde est à la fois le PROPRIÉTAIRE de la
             | carte et un ADMINISTRATEUR, le bouton va donc droit à sa fiche.
             | Aucun pouvoir nouveau : la prolongation exige un motif et reste
             | journalisée. Un raccourci, pas une porte dérobée.
             */
            'ficheAdmin' => $proprietaire && auth()->user()?->isAdmin()
                ? route('admin.clients.show', $profile->user_id)
                : null,
        ], 404);
    }

    /** L'URL absolue de l'image de partage, ou null si elle n'a pu être produite. */
    private function apercu(Profile $profile): ?string
    {
        /*
         | L'IMAGE DE COUVERTURE EST L'IMAGE DE PARTAGE.
         |
         | Quand le porteur en a choisi une, c'est elle qui doit apparaître
         | dans WhatsApp, pas une vignette composée : il l'a choisie pour
         | représenter sa carte, et l'aperçu de partage est le premier endroit
         | où on la verra — souvent avant même d'ouvrir le lien.
         |
         | L'aperçu composé reste le repli, pour les profils sans image.
         */
        if ($couverture = $this->couvertureUrl($profile)) {
            return $couverture;
        }

        try {
            $service = app(SharePreviewService::class);

            $service->png($profile);   // écrite une fois, relue ensuite

            return $service->url($profile);
        } catch (\Throwable $e) {
            Log::warning('Aperçu de partage non produit', [
                'slug' => $profile->slug,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Profil public de démonstration, ciblé par « Voir un exemple ».
     *
     * Prend le premier profil publié du jeu de démonstration. Si la base est
     * vide (production avant le premier client), la vue affiche un état vide
     * plutôt qu'une 404 au bout d'un lien de la page d'accueil.
     */
    public function demo(): View
    {
        $profile = Profile::published()
            ->with(['socialLinks', 'template'])
            ->oldest('id')
            ->first();

        return view('public.demo', ['profile' => $profile]);
    }

    /**
     * « Enregistrer le contact » — la fiche vCard du profil.
     *
     * LES MÊMES GARDES QUE LA PAGE PUBLIQUE, ET POUR LA MÊME RAISON. Un profil
     * dépublié ou sans abonnement ne doit pas laisser fuir ses coordonnées par
     * une seconde porte : ce serait contourner l'abonnement en changeant
     * d'adresse. Une 404, jamais un 403, pour ne rien révéler de son
     * existence.
     */
    public function vcard(Request $request, string $slug, VCardService $vcard): Response
    {
        $profile = Profile::query()
            ->with(['socialLinks'])
            ->where('slug', $slug)
            ->firstOrFail();

        abort_unless($profile->isPubliclyVisible(), 404);

        /*
         | Le jeu de caractères est ANNONCÉ. Sans lui, « Aïssatou » ou « Thiès »
         | s'enregistrent en caractères abîmés sur plusieurs lecteurs, qui
         | retombent alors sur un encodage local par défaut.
         |
         | L'en-tête « attachment » est ce qui déclenche l'ouverture par
         | l'application Contacts du téléphone. Sans elle, Android affiche le
         | fichier comme du texte brut — l'utilisateur voit BEGIN:VCARD et
         | referme.
         */
        /*
         | L'ENREGISTREMENT DU CONTACT EST L'ABOUTISSEMENT DU SCAN.
         |
         | C'est le seul geste qui transforme une visite en relation, et il
         | n'etait compte nulle part : le tableau de bord affichait « 0 contact
         | enregistre » quel que soit le nombre reel de telechargements.
         |
         | Comme la vue, l'echec est avale : une statistique n'a pas le droit
         | d'empecher un contact de s'enregistrer.
         */
        $this->enregistrerVisite($request, $profile->id, ProfileEvent::TYPE_SAVE);

        /*
         | ═══════════════════════════════════════════════════════════════
         | SUR MOBILE, AUCUNE DISPOSITION — ET C'EST TOUT LE SUJET
         | ═══════════════════════════════════════════════════════════════
         | LE DÉFAUT QUE CELA CORRIGE.
         |
         | L'en-tête est passé par « attachment », puis par « inline ». Les
         | deux faisaient télécharger sur iPhone, et pour la même raison :
         | dès qu'une réponse porte un Content-Disposition AVEC un nom de
         | fichier, Safari la traite comme un document à ranger dans
         | Fichiers. « inline » ne change que l'endroit du rangement, pas la
         | décision.
         |
         | SANS AUCUNE DISPOSITION, Safari se fie au seul type MIME. Il
         | reconnaît text/vcard, ouvre sa feuille de contact et propose
         | « Ajouter aux contacts » — le geste unique qu'un scan promet.
         |
         | SUR ORDINATEUR, LE FICHIER RESTE LA BONNE RÉPONSE. Un navigateur
         | de bureau n'a pas d'application Contacts à ouvrir ; le
         | téléchargement, avec un nom lisible, est ce qu'on attend. D'où la
         | distinction par l'appareil : ce n'est pas une préférence, ce sont
         | deux comportements système différents pour la même réponse.
         |
         | SUR ANDROID, l'affaire se joue ailleurs : aucun en-tête ne fait
         | ouvrir l'écran de création de contact. C'est enregistrer-contact.js
         | qui remplace le lien par une intention native, et cette
         | réponse-ci ne sert plus que de repli.
         */
        $entetes = [
            'Content-Type' => 'text/vcard; charset=utf-8',

            // Une fiche de contact ne se met pas en cache partagé : elle
            // porte des coordonnées personnelles.
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ];

        if (! $this->estUnMobile($request)) {
            $entetes['Content-Disposition'] = 'attachment; filename="'.$vcard->nomFichier($profile).'"';
        }

        return response($vcard->pour($profile), 200, $entetes);
    }
}
