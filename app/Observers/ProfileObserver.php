<?php

namespace App\Observers;

use App\Models\Profile;
use App\Services\QrCodeService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Le QR Code suit la carte, sans que personne n'ait à le demander.
 *
 * Il est produit à la création et refait dès que ce qu'il porte change :
 * le slug (l'URL encodée) ou la couleur (son encre). Rien d'autre ne
 * l'affecte — refaire le QR à chaque modification de téléphone serait du
 * calcul perdu.
 *
 * AUCUNE de ces opérations ne doit faire échouer l'enregistrement du profil.
 * Un disque plein ou une extension absente ne peut pas coûter à l'utilisateur
 * le parcours qu'il vient de terminer : on journalise et on laisse passer.
 * Le QR sera régénéré au prochain accès, la lecture le reconstruit à la volée.
 */
class ProfileObserver
{
    public function __construct(private QrCodeService $qr) {}

    public function created(Profile $profile): void
    {
        $this->sansCasser($profile, fn () => $this->qr->refresh($profile));
    }

    public function updated(Profile $profile): void
    {
        if (! $profile->wasChanged(['slug', 'primary_color'])) {
            return;
        }

        $this->sansCasser($profile, fn () => $this->qr->refresh($profile));
    }

    public function deleted(Profile $profile): void
    {
        $this->sansCasser($profile, fn () => $this->qr->forget($profile));
    }

    private function sansCasser(Profile $profile, callable $action): void
    {
        try {
            $action();
        } catch (Throwable $e) {
            Log::error('QR Code non généré.', [
                'profile_id' => $profile->id,
                'slug' => $profile->slug,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
