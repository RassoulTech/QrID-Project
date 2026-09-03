<?php

namespace App\Observers;

use App\Models\Profile;
use App\Services\QrCodeService;
use App\Services\SharePreviewService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Le QR Code et l'aperçu de partage suivent la carte, sans que personne
 * n'ait à le demander.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * DEUX FICHIERS, DEUX DÉCLENCHEURS DIFFÉRENTS
 * ═══════════════════════════════════════════════════════════════════════
 * Le QR encode une ADRESSE : seuls le slug et la variante le changent.
 *
 * L'aperçu de partage AFFICHE des informations : le nom, la fonction,
 * l'entreprise et la photo. Corriger une faute dans sa fonction doit donc
 * refaire l'aperçu, alors que cela laisse le QR intact.
 *
 * Les confondre coûterait dans les deux sens : refaire le QR à chaque
 * modification serait du calcul perdu, et ne pas refaire l'aperçu laisserait
 * l'ancienne fonction s'afficher dans tous les partages, indéfiniment.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * AUCUNE de ces opérations ne doit faire échouer l'enregistrement du profil.
 * ═══════════════════════════════════════════════════════════════════════
 * Un disque plein ou une extension absente ne peut pas coûter à l'utilisateur
 * le parcours qu'il vient de terminer : on journalise et on laisse passer. Les
 * deux fichiers se reconstruisent au prochain accès.
 */
class ProfileObserver
{
    /** Champs affichés PAR l'aperçu de partage. */
    private const CHAMPS_APERCU = ['first_name', 'last_name', 'job_title', 'company', 'cover_path'];

    public function __construct(
        private QrCodeService $qr,
        private SharePreviewService $apercu,
    ) {}

    public function created(Profile $profile): void
    {
        $this->sansCasser($profile, fn () => $this->qr->refresh($profile));

        /*
         | L'aperçu n'est PAS produit ici, seulement à la première visite de la
         | page publique. Une carte sur deux n'est jamais partagée : peindre
         | une image de 1200 × 630 pour chacune, à la création, serait du
         | travail fait pour rien — et allongerait le parcours au moment où
         | l'utilisateur attend son écran d'aperçu.
         */
    }

    public function updated(Profile $profile): void
    {
        // Le QR encode l'adresse : slug et variante, rien d'autre.
        if ($profile->wasChanged(['slug', 'primary_color'])) {
            $this->sansCasser($profile, fn () => $this->qr->refresh($profile));
        }

        /*
         | L'aperçu affiche des informations. Le SLUG y figure aussi, non pour
         | son contenu mais parce qu'il détermine le dossier : sans cette
         | condition, un changement de slug laisserait l'ancien dossier
         | derrière lui.
         */
        if ($profile->wasChanged([...self::CHAMPS_APERCU, 'slug'])) {
            $this->sansCasser($profile, fn () => $this->apercu->forget($profile));
        }
    }

    public function deleted(Profile $profile): void
    {
        $this->sansCasser($profile, fn () => $this->qr->forget($profile));
        $this->sansCasser($profile, fn () => $this->apercu->forget($profile));
    }

    private function sansCasser(Profile $profile, callable $action): void
    {
        try {
            $action();
        } catch (Throwable $e) {
            Log::error('Fichier de carte non généré.', [
                'profile_id' => $profile->id,
                'slug' => $profile->slug,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
