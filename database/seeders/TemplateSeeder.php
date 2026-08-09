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
    }
}
