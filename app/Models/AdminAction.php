<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Journal d'audit des actions sensibles. Écriture seule : jamais modifié,
 * jamais supprimé. Survit à la suppression du compte administrateur.
 */
class AdminAction extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'admin_id',
        'action',
        'target_type',
        'target_id',
        'reason',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'target_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Journalise une action sensible.
     *
     * AdminAction::log('suspend_profile', $profile, 'Contenu inapproprié');
     */
    public static function log(string $action, ?Model $target = null, ?string $reason = null): self
    {
        return static::create([
            'admin_id' => auth()->id(),
            'action' => $action,
            'target_type' => $target ? $target::class : null,
            'target_id' => $target?->getKey(),
            'reason' => $reason,
            'created_at' => now(),
        ]);
    }
}
