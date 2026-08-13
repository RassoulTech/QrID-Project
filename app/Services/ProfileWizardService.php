<?php

namespace App\Services;

use App\Enums\VarianteCarte;
use App\Models\Profile;
use App\Models\ProfileDraft;
use App\Models\SocialLink;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
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
        // La photo déposée mais jamais confirmée part avec l'état.
        if ($path = $this->get('data.photo_path')) {
            Storage::disk('public')->delete($path);
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
     */
    public function storePhoto(UploadedFile $file): string
    {
        if ($old = $this->get('data.photo_path')) {
            Storage::disk('public')->delete($old);
        }

        $path = 'profils/'.Str::uuid()->toString().'.jpg';

        Storage::disk('public')->put($path, $this->toSquareJpeg($file));

        return $path;
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

        ob_start();
        imagejpeg($canvas, null, 82);
        $binary = (string) ob_get_clean();

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
