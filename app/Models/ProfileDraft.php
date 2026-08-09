<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Brouillon du parcours de création — la mémoire durable de l'avancement.
 *
 * La session reste le chemin rapide ; cette ligne survit à la déconnexion.
 * Elle ne contient que des données déjà validées par un Form Request.
 *
 * Rien ici n'est visible du public : le profil n'existe en base qu'à la
 * validation de l'étape 3, et le brouillon disparaît à ce moment-là.
 */
class ProfileDraft extends Model
{
    protected $fillable = [
        'user_id',
        'state',
        'next_step',
    ];

    protected function casts(): array
    {
        return [
            'state' => 'array',
            'next_step' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Le parcours est-il commencé sans être terminé ? */
    public function isInProgress(): bool
    {
        return $this->state !== [] && $this->next_step <= 3;
    }

    /** Nombre d'étapes déjà franchies, pour l'affichage « reprendre ». */
    public function completedCount(): int
    {
        return count($this->state['completed'] ?? []);
    }
}
