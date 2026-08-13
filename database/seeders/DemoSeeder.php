<?php

namespace Database\Seeders;

use App\Enums\VarianteCarte;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Profile;
use App\Models\ProfileEvent;
use App\Models\SocialLink;
use App\Models\Subscription;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Jeu de démonstration réaliste : noms sénégalais, entreprises locales,
 * numéros +221, abonnements et événements variés.
 *
 * Sert aux captures d'écran commerciales et au test des listes admin.
 * Mot de passe commun : « password ».
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $templates = Template::all();
        $trial = Plan::where('slug', 'essai-gratuit')->firstOrFail();
        $monthly = Plan::where('slug', 'mensuel')->firstOrFail();
        $yearly = Plan::where('slug', 'annuel')->firstOrFail();

        $people = [
            // Les deux premiers alimentent la landing (hero et section sombre).
            // Leurs valeurs reprennent celles de la maquette validée.
            ['Mouhamed', 'Dione', 'Développeur web', 'DigiGeek', 'Thiès, Sénégal', '773831364'],
            ['Aïssatou', 'Fall', 'Consultante RH', null, 'Dakar, Sénégal', '760000000'],

            ['Awa', 'Ndiaye', 'Consultante en gestion', 'Cabinet Teranga Conseil', 'Dakar, Plateau', '770000001'],
            ['Moussa', 'Diop', 'Agent immobilier', 'Sénégal Immo', 'Dakar, Almadies', '770000002'],
            ['Fatou', 'Sall', 'Avocate', 'Cabinet Sall & Associés', 'Dakar, Point E', '760000003'],
            ['Ibrahima', 'Fall', 'Commercial B2B', 'Sonatel Business', 'Dakar, Mermoz', '780000004'],
            ['Aminata', 'Bâ', 'Architecte', 'Atelier Baobab', 'Saint-Louis', '750000005'],
            ['Cheikh', 'Gueye', 'Expert-comptable', 'Cabinet Gueye Audit', 'Dakar, Sacré-Cœur', '770000006'],
            ['Mariama', 'Sow', 'Formatrice en marketing', 'Digital Sunu Academy', 'Thiès', '760000007'],
            ['Ousmane', 'Diallo', 'Courtier en assurance', 'Assur Sénégal', 'Dakar, Liberté 6', '780000008'],
            ['Ndèye', 'Faye', 'Créatrice de mode', 'Atelier Ndèye Couture', 'Dakar, Ouakam', '770000009'],
            ['Alioune', 'Sarr', 'Transitaire', 'Sarr Logistique', 'Dakar, Port', '700000010'],
            ['Khady', 'Camara', 'Pharmacienne', 'Pharmacie de la Corniche', 'Dakar, Fann', '760000011'],
            ['Modou', 'Ndour', 'Photographe professionnel', 'Studio Ndour', 'Mbour', '780000012'],
        ];

        foreach ($people as $index => [$first, $last, $job, $company, $address, $phone]) {
            $email = Str::slug($first.'.'.$last).'@exemple.sn';

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => "{$first} {$last}",
                    'phone' => '+221'.$phone,
                    'role' => User::ROLE_USER,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now()->subDays(rand(5, 90)),
                ]
            );

            // Répartition volontairement variée pour tester les listes admin :
            // essai, actif mensuel, actif annuel, expiré, en attente.
            $case = $index % 5;

            [$plan, $status, $startsAt, $endsAt] = match ($case) {
                0 => [$trial,   Subscription::STATUS_ACTIVE,  now()->subDays(3),  now()->addDays(12)],
                1 => [$monthly, Subscription::STATUS_ACTIVE,  now()->subDays(8),  now()->addDays(22)],
                2 => [$yearly,  Subscription::STATUS_ACTIVE,  now()->subDays(40), now()->addDays(325)],
                3 => [$monthly, Subscription::STATUS_EXPIRED, now()->subDays(45), now()->subDays(15)],
                default => [$monthly, Subscription::STATUS_PENDING, null, null],
            };

            $subscription = Subscription::updateOrCreate(
                ['user_id' => $user->id, 'plan_id' => $plan->id],
                ['starts_at' => $startsAt, 'ends_at' => $endsAt, 'status' => $status]
            );

            // Paiement associé, sauf pour l'essai gratuit.
            if ($plan->price_fcfa > 0) {
                Payment::updateOrCreate(
                    ['provider_ref' => 'DEMO-'.$user->id.'-'.$plan->id],
                    [
                        'user_id' => $user->id,
                        'subscription_id' => $subscription->id,
                        'provider' => 'paydunya',
                        'method' => ['wave', 'orange_money', 'free_money'][$index % 3],
                        'amount_fcfa' => $plan->price_fcfa,
                        'status' => $status === Subscription::STATUS_PENDING
                            ? Payment::STATUS_PENDING
                            : Payment::STATUS_SUCCESS,
                        'payload' => ['demo' => true],
                    ]
                );
            }

            // Un paiement échoué de temps en temps, pour tester l'affichage.
            if ($case === 3) {
                Payment::updateOrCreate(
                    ['provider_ref' => 'DEMO-FAIL-'.$user->id],
                    [
                        'user_id' => $user->id,
                        'subscription_id' => $subscription->id,
                        'provider' => 'paydunya',
                        'method' => 'orange_money',
                        'amount_fcfa' => $plan->price_fcfa,
                        'status' => Payment::STATUS_FAILED,
                        'payload' => ['message' => 'Solde insuffisant', 'code' => '51'],
                    ]
                );
            }

            // Profil : publié sauf pour les abonnements en attente (brouillon).
            $isPublished = $status !== Subscription::STATUS_PENDING;

            $profile = Profile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'slug' => Str::slug("{$first}-{$last}"),
                    'first_name' => $first,
                    'last_name' => $last,
                    'job_title' => $job,
                    'company' => $company,
                    'bio' => "{$job} basé{$this->e($first)} à ".explode(',', $address)[0]
                        .'. Plus de '.rand(3, 15)." ans d'expérience au service des entreprises sénégalaises.",
                    'phone' => '+221'.$phone,
                    'whatsapp' => '+221'.$phone,
                    'public_email' => $email,
                    'website' => ($company && $index % 3 === 0) ? 'https://'.Str::slug($company).'.sn' : null,
                    'address' => $address,
                    'template_id' => $templates->get($index % max($templates->count(), 1))?->id,
                    'primary_color' => VarianteCarte::DEFAUT->value,
                    'is_active' => $isPublished,
                ]
            );

            // Liens sociaux
            $profile->socialLinks()->delete();
            $platforms = [['linkedin', 'https://linkedin.com/in/'], ['facebook', 'https://facebook.com/']];

            foreach ($platforms as $position => [$platform, $base]) {
                SocialLink::create([
                    'profile_id' => $profile->id,
                    'platform' => $platform,
                    'url' => $base.Str::slug("{$first}-{$last}"),
                    'position' => $position,
                ]);
            }

            // Événements de consultation, répartis sur 30 jours.
            if ($isPublished) {
                $profile->events()->delete();
                $events = [];

                foreach (range(1, rand(15, 60)) as $i) {
                    $events[] = [
                        'profile_id' => $profile->id,
                        'type' => ['view', 'view', 'scan', 'save'][rand(0, 3)],
                        'ip_hash' => hash('sha256', '196.1.'.rand(1, 254).'.'.rand(1, 254).config('app.key')),
                        'user_agent' => 'Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36',
                        'referer' => null,
                        'created_at' => now()->subDays(rand(0, 30))->subMinutes(rand(0, 1440)),
                    ];
                }

                ProfileEvent::insert($events); // insertion en masse : une seule requête
            }
        }

        $this->command?->info(count($people).' profils de démonstration créés (mot de passe : password).');
    }

    /** Accord féminin sommaire pour les bios de démonstration. */
    private function e(string $firstName): string
    {
        return in_array($firstName, ['Awa', 'Fatou', 'Aminata', 'Mariama', 'Ndèye', 'Khady', 'Aïssatou']) ? 'e' : '';
    }
}
