<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Essai gratuit',
                'slug' => 'essai-gratuit',
                'price_fcfa' => 0,
                'duration_days' => 15,
                'features' => [
                    'Profil professionnel complet',
                    'QR Code illimité',
                    'Statistiques de consultation',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Mensuel',
                'slug' => 'mensuel',
                'price_fcfa' => 2500,
                'duration_days' => 30,
                'features' => [
                    'Profil professionnel complet',
                    'QR Code illimité',
                    'Statistiques de consultation',
                    'Support WhatsApp',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Annuel',
                'slug' => 'annuel',
                'price_fcfa' => 25000,
                'duration_days' => 365,
                'features' => [
                    'Tout ce que contient la formule mensuelle',
                    'Deux mois offerts',
                    'Priorité sur le support',
                ],
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
