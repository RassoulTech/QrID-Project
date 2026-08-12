<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Classique',
                'slug' => 'classique',
                'preview_path' => null,
                'is_premium' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Moderne',
                'slug' => 'moderne',
                'preview_path' => null,
                'is_premium' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Minimal',
                'slug' => 'minimal',
                'preview_path' => null,
                'is_premium' => false,
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            // updateOrCreate : le seeder est rejouable sans créer de doublon.
            Template::updateOrCreate(['slug' => $template['slug']], $template);
        }

        /*
         | UN MODÈLE PAR DÉFAUT, TOUJOURS.
         |
         | Sur une base neuve — c'est-à-dire en production au premier
         | déploiement — aucun modèle ne portait ce drapeau. Le parcours de
         | création continuait de fonctionner, grâce au repli de
         | Template::parDefaut() sur le premier modèle actif, mais l'écran
         | d'administration n'affichait aucun badge « Par défaut ». Un réglage
         | annoncé par l'interface et absent des données.
         |
         | LA CONDITION COMPTE AUTANT QUE L'ACTION : on ne désigne « Classique »
         | que si AUCUN modèle n'est déjà par défaut. Sans elle, ce seeder
         | tournant à chaque démarrage du conteneur écraserait, à chaque
         | déploiement, le choix fait depuis l'administration.
         */
        if (! Template::where('is_default', true)->exists()) {
            Template::where('slug', 'classique')->update(['is_default' => true]);
        }
    }
}
