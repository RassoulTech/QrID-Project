<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un message envoyé depuis le formulaire de contact de la page d'accueil.
 *
 * La base est la SOURCE DE VÉRITÉ, l'e-mail n'est qu'une alerte : voir la
 * migration. Un message perdu, c'est un client qui a écrit et qui n'aura
 * jamais de réponse, sans savoir pourquoi.
 */
class ContactMessage extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'handled_at' => 'datetime',
        ];
    }

    /** L'auteur, s'il avait un compte au moment de l'envoi. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function estTraite(): bool
    {
        return $this->handled_at !== null;
    }

    /** Les messages se lisent du plus récent au plus ancien, toujours. */
    public function scopeRecents($query)
    {
        return $query->latest('created_at');
    }

    public function scopeEnAttente($query)
    {
        return $query->whereNull('handled_at');
    }
}
