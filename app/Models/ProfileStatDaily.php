<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * UN AGRÉGAT JOURNALIER : un profil, un jour, quatre compteurs.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * IL N'EST JAMAIS ÉCRIT À LA MAIN
 * ═══════════════════════════════════════════════════════════════════════
 * Ces lignes sont produites par `app:agreger-statistiques`, qui lit les
 * événements bruts de la veille. Écrire ici depuis un contrôleur ferait
 * diverger l'agrégat de sa source, et la divergence ne se verrait qu'au
 * moment où quelqu'un compare deux chiffres qui devraient être égaux.
 *
 * LE TOTAL EST STOCKÉ, alors qu'il pourrait être calculé. C'est délibéré :
 * les pages de tête classent par total, et une colonne indexable évite un
 * tri sur une expression. Trois entiers de plus par ligne coûtent moins que
 * le filesort qu'ils suppriment.
 */
class ProfileStatDaily extends Model
{
    protected $table = 'profile_stats_daily';

    protected $fillable = ['profile_id', 'jour', 'vues', 'scans', 'saves', 'total'];

    protected function casts(): array
    {
        return [
            'jour' => 'date',
            'vues' => 'integer',
            'scans' => 'integer',
            'saves' => 'integer',
            'total' => 'integer',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
