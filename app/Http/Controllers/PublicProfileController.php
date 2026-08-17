<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Services\SharePreviewService;
use App\Services\VCardService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
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
    public function show(string $slug): View
    {
        // isPubliclyVisible() consulte l'abonnement du propriétaire : sans ces
        // deux relations chargées d'avance, la page part en N+1 dès le premier
        // scan. C'est LA page qui prend tout le trafic.
        $profile = Profile::query()
            ->with(['socialLinks', 'user.subscriptions'])
            ->where('slug', $slug)
            ->firstOrFail();

        abort_unless($profile->isPubliclyVisible(), 404);

        return view('public.profile', [
            'profile' => $profile,

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
        ]);
    }

    /** L'URL absolue de l'image de partage, ou null si elle n'a pu être produite. */
    private function apercu(Profile $profile): ?string
    {
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
    public function vcard(string $slug, VCardService $vcard): Response
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
        return response($vcard->pour($profile), 200, [
            'Content-Type' => 'text/vcard; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$vcard->nomFichier($profile).'"',
        ]);
    }
}
