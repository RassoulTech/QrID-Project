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
                'duration_days' => 14,
                'features' => [
                    'Profil professionnel complet',
                    'QR Code illimité',
                    'Statistiques de consultation',
                    'Sans carte physique',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Standard',
                'slug' => 'standard',
                'price_fcfa' => 3500,
                'duration_days' => 90,
                'features' => [
                    'Carte PVC offerte à la première activation',
                    'Profil professionnel complet',
                    'QR Code illimité',
                    'Statistiques de consultation',
                    'Support WhatsApp',
                ],
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }

        /*
         |---------------------------------------------------------------------
         | LES ANCIENNES FORMULES SONT RETIRÉES DU CATALOGUE, PAS SUPPRIMÉES
         |---------------------------------------------------------------------
         | « mensuel » et « annuel » ne se vendent plus. Les EFFACER casserait
         | les abonnements en cours, qui pointent sur leur ligne, et priverait
         | l'historique des paiements du nom de ce qui a été acheté — une pièce
         | comptable ne se réécrit pas.
         |
         | is_active = false les sort des écrans de vente sans toucher au passé.
         | C'est la règle de migration : les abonnements en cours conservent
         | leur date de fin, aucune modification rétroactive.
         |
         | L'ANNUEL ÉTAIT DEVENU UN PIÈGE. À 25 000 FCFA pour 365 jours, il
         | revenait à 68,5 FCFA par jour contre 38,9 pour le trimestriel —
         | soit 76 % plus cher. Au nouveau tarif, une année vaut 14 200 FCFA.
         | Le laisser en vente aurait fait payer 10 800 FCFA de trop à qui
         | croyait faire une économie.
         */
        Plan::whereIn('slug', ['mensuel', 'annuel'])->update(['is_active' => false]);
    }
}
