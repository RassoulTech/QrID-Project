<?php

namespace App\Services;

use App\Enums\VarianteCarte;
use App\Models\Profile;
use App\Models\ProfileDraft;
use App\Models\SocialLink;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * État du parcours de création de profil.
 *
 * Les données transitent en SESSION d'une étape à l'autre et ne sont écrites
 * en base qu'une seule fois, dans une transaction, à la validation finale.
 *
 * Conséquences voulues :
 *  - l'utilisateur qui quitte et revient retrouve son avancement intact ;
 *  - aucune ligne incomplète ne pollue jamais la table profiles.
 */
class ProfileWizardService
{
    private const KEY = 'profile_wizard';

    public const TOTAL_STEPS = 3;

    /** Côté du carré produit pour la photo. Au-delà, le poids ne sert plus. */
    private const PHOTO_SIZE = 512;

    /**
     * LE POIDS MAXIMAL D'UNE PHOTO CONSERVÉE EN BASE.
     *
     * La colonne est un MEDIUMBLOB — 16 Mo — mais la limite technique n'est
     * pas la bonne borne. Ces octets voyagent dans CHAQUE requête qui charge
     * un profil, et une ligne de 3 Mo pèse sur le cache de la base comme sur
     * la mémoire de PHP.
     *
     * 400 Ko laissent passer largement une photo de portrait en 512×512, y
     * compris très détaillée, tout en gardant la ligne raisonnable.
     */
    private const PHOTO_MAX_OCTETS = 400 * 1024;

    /**
     * LA LARGEUR D'AFFICHAGE D'UNE BANNIÈRE.
     *
     * La carte publique ne dépasse jamais 420px de large. 840px couvre les
     * écrans à deux pixels physiques par pixel logique, et rien au-delà : plus
     * haut, on ferait payer des octets que personne ne verra jamais.
     */
    private const COVER_WIDTH = 840;

    /** Réseaux proposés. Liste fermée : aucune URL arbitraire de plateforme. */
    public const PLATFORMS = [
        'linkedin' => 'LinkedIn',
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'tiktok' => 'TikTok',
        'youtube' => 'YouTube',
        'x' => 'X (Twitter)',
    ];

    // -----------------------------------------------------------------------
    // Lecture / écriture de l'état
    // -----------------------------------------------------------------------

    /**
     * L'état courant du parcours.
     *
     * La SESSION est le chemin rapide. Si elle est vide alors qu'un brouillon
     * existe en base, on la réamorce : c'est ce qui fait qu'une déconnexion
     * ne coûte plus la saisie. La session étant détruite à la déconnexion
     * (protection contre la fixation de session, non négociable), la table
     * profile_drafts est la seule mémoire qui traverse cette frontière.
     */
    public function all(): array
    {
        $state = session(self::KEY);

        if (is_array($state)) {
            return $state;
        }

        $draft = $this->draft();

        if ($draft === null) {
            return [];
        }

        session([self::KEY => $draft->state]);

        return $draft->state;
    }

    /** Le brouillon du compte connecté, ou null (invité, ou rien de commencé). */
    private function draft(): ?ProfileDraft
    {
        $id = auth()->id();

        return $id ? ProfileDraft::where('user_id', $id)->first() : null;
    }

    /**
     * Recopie l'état en base. Appelé après CHAQUE écriture de session : les
     * deux ne doivent jamais diverger, sinon la reprise après déconnexion
     * ressusciterait un état périmé.
     */
    private function remember(array $state): void
    {
        $id = auth()->id();

        if (! $id) {
            return;   // hors session authentifiée, la base n'a rien à mémoriser
        }

        ProfileDraft::updateOrCreate(
            ['user_id' => $id],
            ['state' => $state, 'next_step' => $this->nextStepFrom($state)],
        );
    }

    /** Première étape non complétée d'un état donné. */
    private function nextStepFrom(array $state): int
    {
        $faites = $state['completed'] ?? [];

        for ($i = 1; $i <= self::TOTAL_STEPS; $i++) {
            if (! in_array($i, $faites, true)) {
                return $i;
            }
        }

        return self::TOTAL_STEPS;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->all(), $key, $default);
    }

    public function data(): array
    {
        return $this->all()['data'] ?? [];
    }

    /** Valeur d'un champ : la saisie rejetée d'abord, la session ensuite. */
    public function field(string $name, mixed $default = null): mixed
    {
        return old($name, $this->get('data.'.$name, $default));
    }

    /** Enregistre les données d'une étape et marque celle-ci comme complétée. */
    public function saveStep(int $step, array $data): void
    {
        $state = $this->all();
        $state['data'] = array_merge($state['data'] ?? [], $data);
        $state['completed'] = array_values(array_unique(
            array_merge($state['completed'] ?? [], [$step])
        ));

        session([self::KEY => $state]);

        $this->remember($state);
    }

    // -----------------------------------------------------------------------
    // Mode édition
    // -----------------------------------------------------------------------

    /**
     * Recharge un profil existant dans la session et bascule en mode édition.
     *
     * Le parcours de création SERT de parcours d'édition : mêmes écrans, mêmes
     * règles, même chrono. Dupliquer trois écrans pour les mêmes champs aurait
     * doublé la surface de bugs sans rien apporter à l'utilisateur.
     */
    public function hydrateFrom(Profile $profile): void
    {
        $state = [
            'editing' => $profile->id,
            'completed' => range(1, self::TOTAL_STEPS),
            'data' => [
                'first_name' => $profile->first_name,
                'last_name' => $profile->last_name,
                'job_title' => $profile->job_title,
                'company' => $profile->company,
                'photo_path' => $profile->photo_path,
                'cover_path' => $profile->cover_path,
                'phone' => $profile->phone,
                'whatsapp' => $profile->whatsapp,
                'public_email' => $profile->public_email,
                'website' => $profile->website,
                'address' => $profile->address,
                'template_id' => $profile->template_id,
                'primary_color' => $profile->primary_color,
                'socials' => $profile->socialLinks
                    ->map(fn ($l) => ['platform' => $l->platform, 'url' => $l->url])
                    ->all(),
            ],
        ];

        session([self::KEY => $state]);

        $this->remember($state);
    }

    public function isEditing(): bool
    {
        return $this->get('editing') !== null;
    }

    public function isStepCompleted(int $step): bool
    {
        return in_array($step, $this->all()['completed'] ?? [], true);
    }

    /** Première étape non complétée : point de reprise. */
    public function nextStep(): int
    {
        for ($i = 1; $i <= self::TOTAL_STEPS; $i++) {
            if (! $this->isStepCompleted($i)) {
                return $i;
            }
        }

        return self::TOTAL_STEPS;
    }

    /**
     * Abandonne le parcours : session ET brouillon en base.
     *
     * Oublier seulement la session laisserait le brouillon ressusciter à la
     * requête suivante — l'utilisateur qui a explicitement recommencé
     * retrouverait sa saisie précédente.
     */
    public function clear(): void
    {
        // Les images déposées mais jamais confirmées partent avec l'état.
        foreach (['data.photo_path', 'data.cover_path'] as $clef) {
            if ($chemin = $this->get($clef)) {
                Storage::disk('public')->delete($chemin);
            }
        }

        session()->forget(self::KEY);

        $this->forgetDraft();
    }

    /** Efface la mémoire durable, sans toucher aux fichiers déjà déposés. */
    public function forgetDraft(): void
    {
        if ($id = auth()->id()) {
            ProfileDraft::where('user_id', $id)->delete();
        }
    }

    /**
     * Le parcours est-il commencé sans être terminé ?
     * C'est ce que le tableau de bord lit pour proposer « reprendre ».
     */
    public function isInProgress(): bool
    {
        $state = $this->all();

        return ($state['completed'] ?? []) !== [] && $this->nextStep() <= self::TOTAL_STEPS
            && ! $this->isStepCompleted(self::TOTAL_STEPS);
    }

    // -----------------------------------------------------------------------
    // Photo
    // -----------------------------------------------------------------------

    /**
     * Dépose la photo, recadrée en carré, et renvoie son chemin relatif.
     *
     * Un fichier ne tient pas en session : on l'écrit tout de suite sur le
     * disque public et on ne garde que le chemin. L'ancienne version est
     * supprimée dans la foulée pour ne pas accumuler d'orphelins.
     *
     * ═══════════════════════════════════════════════════════════════════
     * LES OCTETS NE VOYAGENT PLUS PAR LA SESSION
     * ═══════════════════════════════════════════════════════════════════
     * Ils y transitaient, encodés en base64, du formulaire jusqu'à la
     * finalisation. Ce détour ajoutait un maillon fragile à une chaîne qui
     * n'en avait pas besoin : quarante kilo-octets de texte recopiés dans la
     * session, puis dans le brouillon en base, puis relus — et il suffisait
     * qu'un maillon les perde pour que la photo se retrouve sur le disque et
     * nulle part ailleurs, sans que rien ne le signale.
     *
     * Le fichier EST sur le disque à cet instant. persist() n'a qu'à le
     * relire : c'est plus court, et il n'y a plus rien à perdre en route.
     */
    public function storePhoto(UploadedFile $file): string
    {
        if ($old = $this->get('data.photo_path')) {
            Storage::disk('public')->delete($old);
        }

        $path = 'profils/'.Str::uuid()->toString().'.jpg';
        $octets = $this->toSquareJpeg($file);

        Storage::disk('public')->put($path, $octets);

        return $path;
    }

    /**
     * LES OCTETS D'UN FICHIER DÉPOSÉ, s'ils tiennent en base.
     *
     * AU-DELÀ DU PLAFOND, LE DISQUE SEUL. Le cas ne survient que par les
     * replis de toSquareJpeg() et toBannerJpeg() — GD absent, ou image
     * indécodable — où le fichier d'origine est rendu intact et peut peser
     * plusieurs mégaoctets. Écrire cela dans chaque lecture de profil
     * coûterait plus que la garantie qu'on cherche. Le média garde alors son
     * ancien comportement : il vit sur le disque et ne survit pas au
     * déploiement. C'est une dégradation, pas une panne — et elle est tracée.
     */
    public function octetsDuFichier(?string $chemin): ?string
    {
        if (blank($chemin)) {
            return null;
        }

        try {
            if (! Storage::disk('public')->exists($chemin)) {
                return null;
            }

            $octets = (string) Storage::disk('public')->get($chemin);
        } catch (\Throwable $e) {
            Log::warning('Média illisible sur le disque', [
                'chemin' => $chemin,
                'erreur' => $e->getMessage(),
            ]);

            return null;
        }

        if ($octets === '' || strlen($octets) > self::PHOTO_MAX_OCTETS) {
            Log::warning('Média trop lourd pour la base : il ne vivra que sur le disque', [
                'chemin' => $chemin,
                'octets' => strlen($octets),
                'plafond' => self::PHOTO_MAX_OCTETS,
            ]);

            return null;
        }

        return $octets;
    }

    // -----------------------------------------------------------------------
    // Couverture
    // -----------------------------------------------------------------------

    /**
     * Dépose une bannière de couverture et rend son chemin.
     *
     * ELLE N'EST PAS RECADRÉE EN CARRÉ, contrairement au portrait : une
     * bannière est un rectangle large, et la découper en carré reviendrait à
     * jeter les deux tiers de ce que le porteur a choisi de montrer. Elle est
     * simplement ramenée à une largeur d'affichage et recomprimée : une photo
     * de quatre mégaoctets sortie d'un téléphone n'a aucune raison de voyager
     * en entier sur une 3G.
     */
    public function storeCover(UploadedFile $file): string
    {
        if ($ancienne = $this->get('data.cover_path')) {
            Storage::disk('public')->delete($ancienne);
        }

        $chemin = 'couvertures/'.Str::uuid()->toString().'.jpg';

        Storage::disk('public')->put($chemin, $this->toBannerJpeg($file));

        return $chemin;
    }

    /**
     * Ramène l'image à la largeur d'affichage, en gardant ses proportions.
     *
     * Comme pour le portrait : si GD manque ou si l'image est indécodable, on
     * rend le fichier d'origine. Une couverture non redimensionnée vaut mieux
     * qu'une erreur 500 au milieu du parcours de création.
     */
    private function toBannerJpeg(UploadedFile $file): string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return (string) file_get_contents($file->getRealPath());
        }

        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if ($source === false) {
            return (string) file_get_contents($file->getRealPath());
        }

        $largeur = imagesx($source);
        $hauteur = imagesy($source);

        // Une image déjà plus étroite n'est jamais AGRANDIE : on ne fabrique
        // pas de la définition qui n'existe pas, on la rendrait juste floue.
        $cible = min(self::COVER_WIDTH, $largeur);
        $cibleHauteur = max(1, (int) round($hauteur * $cible / $largeur));

        $toile = imagecreatetruecolor($cible, $cibleHauteur);
        imagefill($toile, 0, 0, imagecolorallocate($toile, 255, 255, 255));

        imagecopyresampled($toile, $source, 0, 0, 0, 0, $cible, $cibleHauteur, $largeur, $hauteur);

        $binaire = '';

        foreach ([82, 70, 60] as $qualite) {
            ob_start();
            imagejpeg($toile, null, $qualite);
            $binaire = (string) ob_get_clean();

            if (strlen($binaire) <= self::PHOTO_MAX_OCTETS) {
                break;
            }
        }

        imagedestroy($toile);
        imagedestroy($source);

        return $binaire;
    }

    /**
     * Recadrage centré en carré puis compression JPEG, en GD pur.
     *
     * Aucune dépendance ajoutée : GD est présent dans toute installation PHP
     * standard. Si l'extension venait à manquer, on retombe sur le fichier
     * d'origine — un profil sans recadrage vaut mieux qu'une erreur 500.
     */
    private function toSquareJpeg(UploadedFile $file): string
    {
        // LES DEUX REPLIS RENDENT LE FICHIER D'ORIGINE, qui n'a subi aucun
        // recadrage ni aucune compression : il peut peser plusieurs mégaoctets.
        // Un profil sans recadrage vaut mieux qu'une erreur 500, mais il ne
        // vaut pas une ligne de base de données de cette taille — c'est
        // storePhoto() qui décidera de ne pas l'écrire en base.
        if (! function_exists('imagecreatetruecolor')) {
            return (string) file_get_contents($file->getRealPath());
        }

        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if ($source === false) {
            return (string) file_get_contents($file->getRealPath());
        }

        $w = imagesx($source);
        $h = imagesy($source);
        $side = min($w, $h);

        $canvas = imagecreatetruecolor(self::PHOTO_SIZE, self::PHOTO_SIZE);

        // Fond blanc : une image transparente ne doit pas virer au noir en JPEG.
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));

        imagecopyresampled(
            $canvas, $source,
            0, 0,
            (int) (($w - $side) / 2), (int) (($h - $side) / 2),
            self::PHOTO_SIZE, self::PHOTO_SIZE,
            $side, $side
        );

        /*
         | LA QUALITÉ DESCEND JUSQU'À CE QUE LE POIDS TIENNE.
         |
         | 82 convient à la quasi-totalité des portraits : 20 à 40 Ko. Mais
         | une image très détaillée — feuillage en arrière-plan, vêtement à
         | motifs, photo prise en basse lumière — atteint 180 Ko et davantage
         | au même réglage. Mesuré sur du bruit pur, le pire cas monte à
         | 184 Ko.
         |
         | Plutôt que de refuser la photo ou de la laisser grossir sans
         | borne, on recomprime. Trois paliers suffisent : entre 82 et 60, un
         | portrait perd un peu de grain et rien de reconnaissable, là où un
         | refus obligerait le client à retoucher son image lui-même — ce
         | qu'il ne fera pas, et il partira sans photo.
         |
         | Le dernier palier est renvoyé tel quel : mieux vaut une photo un
         | peu lourde qu'aucune photo. Le plafond de la base, lui, est trente
         | fois plus haut.
         */
        $binary = '';

        foreach ([82, 70, 60] as $qualite) {
            ob_start();
            imagejpeg($canvas, null, $qualite);
            $binary = (string) ob_get_clean();

            if (strlen($binary) <= self::PHOTO_MAX_OCTETS) {
                break;
            }
        }

        imagedestroy($canvas);
        imagedestroy($source);

        return $binary;
    }

    // -----------------------------------------------------------------------
    // Persistance finale
    // -----------------------------------------------------------------------

    /**
     * Écrit le profil et ses liens sociaux en UNE SEULE transaction.
     * Le profil naît inactif : il ne devient public qu'après activation.
     */
    public function persist(User $user): Profile
    {
        $data = $this->data();
        $editingId = $this->get('editing');

        return DB::transaction(function () use ($user, $data, $editingId) {
            // Verrou de ligne en édition : deux onglets ouverts ne peuvent pas
            // s'écraser mutuellement à mi-parcours.
            $existing = $editingId
                ? Profile::where('user_id', $user->id)->lockForUpdate()->find($editingId)
                : null;

            $attributes = [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'job_title' => $data['job_title'],
                'company' => $data['company'] ?? null,
                'phone' => $data['phone'],
                'whatsapp' => $data['whatsapp'] ?? null,
                'public_email' => $data['public_email'] ?? null,
                'website' => $data['website'] ?? null,
                'address' => $data['address'] ?? null,
                'photo_path' => $data['photo_path'] ?? null,
                'cover_path' => $data['cover_path'] ?? null,

                /*
                 | LES IMAGES SONT ÉCRITES EN BASE, pas seulement sur le disque.
                 |
                 | Sans cela, elles disparaissent au premier déploiement et le
                 | profil affiche son repli sans que rien ne le signale.
                 |
                 | LES OCTETS SONT RELUS SUR LE DISQUE, ici et maintenant. Ils
                 | transitaient auparavant par la session, encodés en base64,
                 | du formulaire jusqu'ici : un maillon de plus, pour rien,
                 | dans une chaîne qui doit tenir. Le fichier vient d'être
                 | déposé, il est là — autant le lire.
                 |
                 | Rien de nouveau déposé : on garde ce que le profil avait
                 | déjà. Corriger une faute dans son nom ne doit pas effacer
                 | sa photo.
                 */
                'photo_data' => $this->octetsDuFichier($data['photo_path'] ?? null)
                    ?? $existing?->photo_data,

                'cover_data' => $this->octetsDuFichier($data['cover_path'] ?? null)
                    ?? $existing?->cover_data,
                'template_id' => $data['template_id'] ?? null,
                // Le repli passe par l'enum : la valeur par défaut de la carte
                // ne doit exister qu'à un seul endroit du projet.
                'primary_color' => VarianteCarte::depuis($data['primary_color'] ?? null)->value,
            ];

            if ($existing) {
                // Le slug ne bouge pas : un lien déjà partagé doit rester valable.
                $existing->update($attributes);
                $profile = $existing;

                // Les réseaux sont remplacés en bloc : plus simple et plus sûr
                // qu'un rapprochement ligne à ligne, et la transaction couvre tout.
                $profile->socialLinks()->delete();
            } else {
                $profile = Profile::create($attributes + [
                    'user_id' => $user->id,
                    'slug' => $this->uniqueSlug($data['first_name'], $data['last_name']),
                    'is_active' => false,
                ]);
            }

            foreach (array_values($data['socials'] ?? []) as $position => $social) {
                SocialLink::create([
                    'profile_id' => $profile->id,
                    'platform' => $social['platform'],
                    'url' => $social['url'],
                    'position' => $position,
                ]);
            }

            // Le modèle reçu doit refléter ce qu'on vient d'écrire. Sans cela,
            // un $user->profile déjà consulté (donc mis en cache à null) reste
            // null après la création : tout code lisant la relation ensuite
            // conclurait que l'utilisateur n'a pas de profil.
            $user->setRelation('profile', $profile);

            // Le brouillon a rempli son office : il disparaît DANS la même
            // transaction que le profil. S'il survivait, le tableau de bord
            // continuerait de proposer « reprendre » un parcours déjà terminé.
            ProfileDraft::where('user_id', $user->id)->delete();

            return $profile;
        });
    }

    /**
     * Slug unique construit sur le nom, suffixé au besoin.
     *
     * withTrashed() est indispensable : l'index d'unicité de la colonne ne
     * connaît pas la suppression douce, un profil supprimé occupe son slug.
     */
    private function uniqueSlug(string $firstName, string $lastName): string
    {
        $base = Str::slug($firstName.' '.$lastName) ?: 'profil';
        $slug = $base;
        $n = 1;

        while (Profile::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$n);
        }

        return $slug;
    }
}
