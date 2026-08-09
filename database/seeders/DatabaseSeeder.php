<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Ordre imposé par les dépendances :
     * modèles et formules d'abord, puis l'administrateur, puis la démonstration.
     *
     * LES DEUX JEUX DE DÉMONSTRATION NE SONT JOUÉS QU'EN LOCAL : jamais de
     * faux clients en production.
     *
     *   DemoSeeder ....... une douzaine de profils soignés, pour la landing
     *                      et les captures commerciales ;
     *   AdminDemoSeeder .. 60 comptes et 2 000 événements, pour mettre les
     *                      écrans d'administration en défaut — pagination,
     *                      N+1, états vides.
     *
     * AdminDemoSeeder passe EN DERNIER : il purge les événements et paiements
     * de ses propres comptes avant de les réécrire, et n'a donc rien à faire
     * avant que le reste soit en place.
     */
    public function run(): void
    {
        $this->call([
            TemplateSeeder::class,
            PlanSeeder::class,
            AdminSeeder::class,
        ]);

        if (app()->environment('local')) {
            $this->call([
                DemoSeeder::class,
                AdminDemoSeeder::class,
            ]);
        }
    }
}
