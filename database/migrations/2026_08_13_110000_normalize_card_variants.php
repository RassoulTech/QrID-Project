<?php

use App\Enums\VarianteCarte;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * RAMÈNE TOUS LES PROFILS AUX DEUX VARIANTES.
 *
 * L'ancien nuancier proposait cinq teintes : vert, nuit, ambre, océan,
 * grenat. Tout ce qui n'est pas l'une des deux variantes bascule sur la
 * VERTE, celle de la marque.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * AUCUNE CONTRAINTE DE BASE N'EST POSÉE, ET C'EST DÉLIBÉRÉ
 * ═══════════════════════════════════════════════════════════════════════
 * Un CHECK ou un ENUM sur la colonne semblerait plus rigoureux. Il coûterait
 * pourtant deux choses qu'on ne veut pas payer :
 *
 *   · une migration bloquante le jour où une troisième variante existera,
 *     alors que le besoin viendra du commerce et devra aller vite ;
 *   · une exception de base de données au moment de l'écriture, là où
 *     VarianteCarte::depuis() dégrade proprement en lecture.
 *
 * La règle est tenue à deux endroits qui suffisent : la validation du
 * formulaire refuse toute autre valeur en entrée, et l'affichage retombe sur
 * la variante par défaut en sortie. Entre les deux, une donnée héritée ne
 * casse jamais la page publique d'un client.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * RETOUR EN ARRIÈRE IMPOSSIBLE, ET C'EST HONNÊTE
 * ═══════════════════════════════════════════════════════════════════════
 * Les teintes d'origine ne sont conservées nulle part : les restaurer
 * supposerait de deviner laquelle chaque profil portait. Un down() qui
 * inventerait des couleurs serait pire que pas de down() du tout.
 */
return new class extends Migration
{
    public function up(): void
    {
        $autorisees = array_column(VarianteCarte::cases(), 'value');

        $touches = DB::table('profiles')
            ->where(function ($q) use ($autorisees) {
                $q->whereNotIn('primary_color', $autorisees)
                    ->orWhereNull('primary_color');
            })
            ->update(['primary_color' => VarianteCarte::DEFAUT->value]);

        if ($touches > 0) {
            echo "  Profils ramenés à la variante verte : {$touches}\n";
        }
    }

    public function down(): void
    {
        // Voir l'en-tête : les teintes d'origine ne sont pas récupérables.
    }
};
