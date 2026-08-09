<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Template extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'preview_path',
        'is_premium',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_premium' => 'boolean',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function profiles(): HasMany
    {
        return $this->hasMany(Profile::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Modèles accessibles sans abonnement premium. */
    public function scopeFree($query)
    {
        return $query->where('is_premium', false);
    }

    /**
     * Le modèle proposé par défaut à l'étape 2 du parcours de création.
     *
     * Repli sur le premier modèle actif : une base sans défaut désigné ne doit
     * pas casser la création de profil. Le repli est explicite et ordonné par
     * identifiant, donc reproductible — contrairement à l'ancien comportement
     * qui dépendait de l'ordre naturel de la table.
     */
    public static function parDefaut(): ?self
    {
        return static::query()->active()->where('is_default', true)->first()
            ?? static::query()->active()->orderBy('id')->first();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
