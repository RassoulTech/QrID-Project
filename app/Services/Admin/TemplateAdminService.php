<?php

namespace App\Services\Admin;

use App\Models\AdminAction;
use App\Models\Template;
use App\Support\AdminActionType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Modèles de carte — activation, duplication, désignation du défaut.
 */
class TemplateAdminService
{
    /**
     * Interrupteur d'activation.
     *
     * REFUS de désactiver le modèle par défaut : le parcours de création
     * n'aurait plus rien à proposer à l'étape 2, et l'écran se casserait pour
     * tous les clients — sans que l'administration voie le lien avec le
     * bouton qu'elle vient de basculer.
     */
    public function basculer(Template $modele): Template
    {
        if ($modele->is_active && $modele->is_default) {
            throw new RuntimeException(
                'Le modèle par défaut ne peut pas être désactivé. Désignez d\'abord un autre modèle par défaut.'
            );
        }

        $modele->forceFill(['is_active' => ! $modele->is_active])->save();

        AdminAction::log(
            AdminActionType::MODELE_ACTIVE,
            $modele,
            $modele->is_active ? 'Modèle activé' : 'Modèle désactivé'
        );

        return $modele;
    }

    /**
     * Duplication — point de départ d'une variante.
     *
     * La copie naît INACTIVE et jamais par défaut : un modèle apparu tout seul
     * dans le parcours des clients, sans que personne l'ait relu, serait une
     * mise en production accidentelle.
     */
    public function dupliquer(Template $modele): Template
    {
        $copie = Template::create([
            'name' => $this->nomDeCopie($modele->name),
            'slug' => $this->slugLibre($modele->slug),
            'preview_path' => $modele->preview_path,
            'is_premium' => $modele->is_premium,
            'is_active' => false,
        ]);

        AdminAction::log(AdminActionType::MODELE_DUPLIQUE, $copie, "Copie de « {$modele->name} »");

        return $copie;
    }

    /**
     * Désigne le modèle par défaut. Un seul à la fois.
     *
     * L'unicité est tenue ICI, pas par un index : `is_default = false` sur
     * plusieurs lignes rendrait un index unique impossible sur MySQL.
     */
    public function definirParDefaut(Template $modele): Template
    {
        if (! $modele->is_active) {
            throw new RuntimeException(
                'Un modèle inactif ne peut pas devenir le modèle par défaut. Activez-le d\'abord.'
            );
        }

        DB::transaction(function () use ($modele) {
            Template::query()->where('is_default', true)->update(['is_default' => false]);

            $modele->forceFill(['is_default' => true])->save();

            AdminAction::log(AdminActionType::MODELE_PAR_DEFAUT, $modele, "« {$modele->name} » devient le modèle par défaut");
        });

        return $modele;
    }

    private function nomDeCopie(string $nom): string
    {
        return Str::limit($nom, 230, '').' (copie)';
    }

    /** Suffixe numérique jusqu'à trouver un slug libre. */
    private function slugLibre(string $base): string
    {
        $racine = Str::slug(Str::limit($base, 200, ''));
        $candidat = $racine.'-copie';
        $n = 2;

        while (Template::where('slug', $candidat)->exists()) {
            $candidat = $racine.'-copie-'.$n++;
        }

        return $candidat;
    }
}
