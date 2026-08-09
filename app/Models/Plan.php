<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'price_fcfa',
        'duration_days',
        'features',
        'is_active',
    ];

    /*
     | PÉRIODICITÉ — vue de l'écran d'administration.
     |
     | Le schéma stocke une DURÉE EN JOURS, pas une périodicité. C'est le bon
     | choix : « ends_at = starts_at + N jours » est direct, alors qu'un enum
     | obligerait à traduire à chaque calcul.
     |
     | Mais l'écran des paramètres demande « Mensuel / Annuel ». Cette table
     | fait la traduction dans les deux sens, à un seul endroit. Une durée hors
     | catalogue — 14 jours d'essai, par exemple — reste parfaitement valide en
     | base et s'affiche telle quelle.
     */
    public const PERIODICITES = [
        7 => 'Hebdomadaire',
        30 => 'Mensuel',
        90 => 'Trimestriel',
        365 => 'Annuel',
    ];

    protected function casts(): array
    {
        return [
            'price_fcfa' => 'integer',    // JAMAIS de float sur de l'argent
            'duration_days' => 'integer',
            'features' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /** « Mensuel », ou « 14 jours » pour une durée hors catalogue. */
    public function periodicite(): string
    {
        return self::PERIODICITES[$this->duration_days] ?? $this->duration_days.' jours';
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isFree(): bool
    {
        return $this->price_fcfa === 0;
    }

    /** Prix formaté pour l'affichage : « 25 000 FCFA ». */
    public function formattedPrice(): string
    {
        return $this->isFree()
            ? 'Gratuit'
            : number_format($this->price_fcfa, 0, ',', ' ').' FCFA';
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
