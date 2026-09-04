<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialLink extends Model
{
    use HasFactory;

    /**
     * MODIFIER UN LIEN MET À JOUR SON PROFIL.
     *
     * ═══════════════════════════════════════════════════════════════════
     * CE N'EST PAS UNE COQUETTERIE : C'EST L'INVALIDATION DU CACHE
     * ═══════════════════════════════════════════════════════════════════
     * La carte publique met son rendu en cache, et la clé porte
     * `profile.updated_at`. Sans cette ligne, ajouter un lien WhatsApp ne
     * changerait pas cette date : le visiteur continuerait de voir l'ancienne
     * carte jusqu'à l'expiration, et le porteur croirait son ajout perdu.
     *
     * `$touches` fait remonter la modification à la relation nommée. Elle
     * coûte une écriture de plus sur un geste rare — modifier ses liens — et
     * évite d'avoir à se souvenir de purger quoi que ce soit.
     */
    protected $touches = ['profile'];

    /** Plateformes reconnues, pour la validation et l'affichage. */
    public const PLATFORMS = [
        'linkedin' => 'LinkedIn',
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'tiktok' => 'TikTok',
        'x' => 'X',
        'youtube' => 'YouTube',
        'website' => 'Site web',
    ];

    protected $fillable = [
        'profile_id',
        'platform',
        'url',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function getPlatformLabelAttribute(): string
    {
        return self::PLATFORMS[$this->platform] ?? ucfirst($this->platform);
    }
}
