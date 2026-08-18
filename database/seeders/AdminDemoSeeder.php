<?php

namespace Database\Seeders;

use App\Models\AdminAction;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Profile;
use App\Models\ProfileEvent;
use App\Models\Subscription;
use App\Models\Template;
use App\Models\User;
use App\Support\AdminActionType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * JEU DE DONNÉES DE L'ESPACE ADMINISTRATEUR.
 *
 * Il ne sert pas à « remplir » les écrans : il sert à les mettre en défaut.
 * Une liste de quinze lignes propres ne révèle ni un N+1, ni une pagination
 * cassée, ni un état vide oublié. Les volumes demandés — 60 comptes, 80
 * paiements, 2 000 événements — sont là pour que ces défauts se voient.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * REJOUABLE SANS DOUBLON
 * ═══════════════════════════════════════════════════════════════════════
 * Deux mécanismes, pas un :
 *
 *   · les lignes ayant une clé naturelle (compte par e-mail, profil par
 *     slug) passent par updateOrCreate ;
 *   · les lignes qui n'en ont pas — événements, entrées de journal — sont
 *     PURGÉES pour les comptes de démonstration avant d'être réécrites.
 *     Sans cela, trois exécutions donneraient 6 000 événements et une
 *     tendance trois fois trop haute.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * REPRODUCTIBLE
 * ═══════════════════════════════════════════════════════════════════════
 * Le générateur aléatoire est amorcé sur une graine fixe. Deux exécutions
 * donnent exactement les mêmes chiffres : une capture d'écran reste valable,
 * et « le graphique a changé » devient un signal au lieu d'un bruit de fond.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * COHÉRENCE — les comptes tombent juste
 * ═══════════════════════════════════════════════════════════════════════
 * 60 profils = 45 publiés + 10 brouillons + 5 désactivés
 * 45 publiés = 25 abonnements actifs + 12 essais + 8 expirés
 * 48 abonnements = ces 45 + 3 annulés (sur des brouillons)
 * 12 comptes n'ont aucun abonnement : ils se sont inscrits sans aller au bout.
 *
 * UNE NUANCE À CONNAÎTRE : les 8 profils à abonnement expiré sont publiés
 * mais PAS visibles du public — isPubliclyVisible() exige un abonnement
 * actif. C'est un état réel en production, et le seul moyen d'avoir un écran
 * où « publié » et « en ligne » ne sont pas synonymes. Sans eux, personne ne
 * verrait jamais ce cas.
 */
class AdminDemoSeeder extends Seeder
{
    /** Marqueur des comptes de démonstration. Sert à purger sans toucher au reste. */
    private const DOMAINE = 'qrid-demo.sn';

    private const GRAINE = 20260805;

    private const MOT_DE_PASSE = 'password';

    // Volumes — repris tels quels du cahier des charges.
    private const NB_COMPTES = 60;

    private const NB_PUBLIES = 45;

    private const NB_BROUILLONS = 10;

    private const NB_DESACTIVES = 5;

    private const NB_ACTIFS = 25;

    private const NB_ESSAIS = 12;

    private const NB_EXPIRES = 8;

    private const NB_ANNULES = 3;

    private const NB_PAIEMENTS = 80;

    private const NB_EVENEMENTS = 2000;

    private const NB_JOURS_EVENEMENTS = 90;

    private const NB_ENTREES_JOURNAL = 30;

    private CarbonImmutable $maintenant;

    public function run(): void
    {
        mt_srand(self::GRAINE);
        $this->maintenant = CarbonImmutable::now();

        $plans = $this->plans();
        $modeles = $this->modeles();
        $administrateurs = $this->administrateurs();

        $comptes = $this->comptes();
        $profils = $this->profils($comptes, $modeles);

        $this->purger($comptes);

        $abonnements = $this->abonnements($comptes, $plans);
        $this->paiements($comptes, $abonnements, $plans);
        $this->evenements($profils);
        $this->journal($administrateurs, $comptes, $profils, $plans);

        $this->bilan();
    }

    // =======================================================================
    // RÉFÉRENTIELS
    // =======================================================================

    /**
     * Les formules doivent exister : PlanSeeder tourne avant. On échoue fort
     * plutôt que de créer des formules fantômes qui fausseraient les montants.
     *
     * @return array{essai:Plan, mensuel:Plan, annuel:Plan}
     */
    private function plans(): array
    {
        return [
            'essai' => Plan::where('slug', 'essai-gratuit')->firstOrFail(),
            'standard' => Plan::where('slug', 'standard')->firstOrFail(),
            'standard' => Plan::where('slug', 'standard')->firstOrFail(),
        ];
    }

    /** @return Collection<int, Template> */
    private function modeles()
    {
        $modeles = Template::orderBy('id')->get();

        // Le premier modèle devient le modèle par défaut si aucun ne l'est :
        // l'écran « Gestion des modèles » doit pouvoir montrer son badge.
        if ($modeles->isNotEmpty() && ! Template::where('is_default', true)->exists()) {
            $modeles->first()->forceFill(['is_default' => true])->save();
        }

        return $modeles;
    }

    /**
     * Deux administrateurs, et deux profils d'usage distincts : celui qui
     * décide et celui qui traite au quotidien. Le journal d'audit n'a
     * d'intérêt que s'il distingue plusieurs auteurs.
     *
     * @return array<int, User>
     */
    private function administrateurs(): array
    {
        $definitions = [
            ['Awa Diop', 'awa.diop@'.self::DOMAINE],
            ['Ousmane Bâ', 'ousmane.ba@'.self::DOMAINE],
        ];

        return collect($definitions)->map(fn (array $d) => User::updateOrCreate(
            ['email' => $d[1]],
            [
                'name' => $d[0],
                'password' => Hash::make(self::MOT_DE_PASSE),
                'role' => User::ROLE_ADMIN,
                'email_verified_at' => $this->maintenant->subMonths(7),
                'phone' => '+2217700000'.random_int(10, 99),
            ]
        ))->all();
    }

    // =======================================================================
    // LES 60 COMPTES
    // =======================================================================

    /**
     * Noms et prénoms sénégalais, numéros +221 au format canonique, dates
     * d'inscription réparties sur six mois.
     *
     * LES DATES NE SONT PAS UNIFORMES. Une inscription par jour, régulière,
     * donnerait un histogramme parfaitement plat — c'est-à-dire un graphique
     * qui ne prouve rien. La répartition ci-dessous accélère sur les mois
     * récents, comme le fait une acquisition qui démarre.
     *
     * @return array<int, User>
     */
    private function comptes(): array
    {
        $prenoms = [
            'Mamadou', 'Aminata', 'Ibrahima', 'Fatou', 'Ousmane', 'Awa', 'Cheikh', 'Ndèye',
            'Moussa', 'Khady', 'Abdoulaye', 'Sokhna', 'Modou', 'Adama', 'Alioune', 'Mariama',
            'Babacar', 'Coumba', 'Serigne', 'Rokhaya', 'Pape', 'Astou', 'Malick', 'Bineta',
            'Lamine', 'Dieynaba', 'Idrissa', 'Seynabou', 'Souleymane', 'Marème',
        ];

        $noms = [
            'Ndiaye', 'Diop', 'Fall', 'Sow', 'Ba', 'Sarr', 'Gueye', 'Faye', 'Sy', 'Cissé',
            'Diallo', 'Mbaye', 'Kane', 'Thiam', 'Seck', 'Camara', 'Diouf', 'Niang', 'Touré', 'Sane',
        ];

        $comptes = [];

        for ($i = 0; $i < self::NB_COMPTES; $i++) {
            $prenom = $prenoms[$i % count($prenoms)];
            $nom = $noms[intdiv($i, 3) % count($noms)];

            // L'index dans l'adresse garantit l'unicité ET la rejouabilité :
            // c'est la clé naturelle sur laquelle updateOrCreate s'appuie.
            $email = Str::slug($prenom.'.'.$nom, '.').'.'.($i + 1).'@'.self::DOMAINE;

            $comptes[] = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $prenom.' '.$nom,
                    'password' => Hash::make(self::MOT_DE_PASSE),
                    'role' => User::ROLE_USER,
                    'phone' => $this->numeroSenegalais($i),
                    'email_verified_at' => $this->dateInscription($i)->addHours(random_int(1, 30)),
                    'created_at' => $this->dateInscription($i),
                    'updated_at' => $this->dateInscription($i),
                    'is_blocked' => false,
                    'theme' => 'light',
                ]
            );
        }

        return $comptes;
    }

    /**
     * Numéro mobile sénégalais valide : +221 puis 77/78/76/70, puis 7 chiffres.
     * Les préfixes sont ceux réellement attribués aux opérateurs — un numéro
     * en +22112… ne passerait pas la règle SenegalPhone du produit.
     */
    private function numeroSenegalais(int $i): string
    {
        $prefixes = ['77', '78', '76', '70'];

        return '+221'.$prefixes[$i % 4].str_pad((string) (1000000 + $i * 7331), 7, '0', STR_PAD_LEFT);
    }

    /** Six mois, densité croissante vers le présent. */
    private function dateInscription(int $i): CarbonImmutable
    {
        // Racine carrée : les index élevés se tassent près d'aujourd'hui.
        $part = sqrt(($i + 1) / self::NB_COMPTES);
        $jours = (int) round(180 * (1 - $part));

        return $this->maintenant->subDays($jours)->subHours(random_int(0, 23));
    }

    // =======================================================================
    // LES 60 PROFILS
    // =======================================================================

    /**
     * @param  array<int, User>  $comptes
     * @return array<int, Profile>
     */
    private function profils(array $comptes, $modeles): array
    {
        $metiers = [
            ['Consultante en gestion', 'Cabinet Teranga Conseil'],
            ['Agent immobilier', 'Sénégal Immo'],
            ['Développeur web', 'DigiGeek'],
            ['Avocate au barreau', 'Cabinet Ndiaye & Associés'],
            ['Architecte', 'Atelier Sahel'],
            ['Médecin généraliste', 'Clinique de la Corniche'],
            ['Comptable agréé', 'Fiduciaire du Cap-Vert'],
            ['Photographe', null],
            ['Formatrice en marketing', 'Institut Baobab'],
            ['Ingénieur agronome', 'AgroSen'],
            ['Coiffeuse', 'Salon Diamniadio'],
            ['Traducteur assermenté', null],
        ];

        $villes = [
            'Dakar, Plateau', 'Dakar, Almadies', 'Thiès', 'Saint-Louis', 'Ziguinchor',
            'Touba', 'Mbour', 'Kaolack', 'Rufisque', 'Diourbel',
        ];

        $profils = [];

        foreach ($comptes as $i => $compte) {
            [$prenom, $nom] = explode(' ', $compte->name, 2);
            [$metier, $entreprise] = $metiers[$i % count($metiers)];

            $etat = $this->etatDuProfil($i);
            $modele = $modeles[$i % max(1, $modeles->count())];

            $profils[] = Profile::updateOrCreate(
                ['user_id' => $compte->id],
                [
                    'slug' => Str::slug($prenom.'-'.$nom).'-'.($i + 1),
                    'first_name' => $prenom,
                    'last_name' => $nom,
                    'job_title' => $metier,
                    'company' => $entreprise,
                    'bio' => null,
                    'phone' => $compte->phone,
                    'whatsapp' => $compte->phone,
                    'public_email' => $compte->email,
                    'address' => $villes[$i % count($villes)],
                    'template_id' => $modele?->id,
                    'is_active' => $etat === Profile::ETAT_PUBLIE,
                    'created_at' => $compte->created_at?->addHours(2),
                    'updated_at' => $compte->created_at?->addHours(2),
                ]
            );

            // deactivated_at n'est pas assignable en masse : il ne s'écrit que
            // par le service d'administration. Ici, forceFill assumé.
            $profils[$i]->forceFill($etat === Profile::ETAT_DESACTIVE
                ? [
                    'deactivated_at' => $this->maintenant->subDays(random_int(3, 40)),
                    'deactivated_reason' => $this->motifDeDesactivation($i),
                ]
                : ['deactivated_at' => null, 'deactivated_reason' => null]
            )->save();
        }

        return $profils;
    }

    private function etatDuProfil(int $i): string
    {
        return match (true) {
            $i < self::NB_PUBLIES => Profile::ETAT_PUBLIE,
            $i < self::NB_PUBLIES + self::NB_BROUILLONS => Profile::ETAT_BROUILLON,
            default => Profile::ETAT_DESACTIVE,
        };
    }

    private function motifDeDesactivation(int $i): string
    {
        $motifs = [
            'Coordonnées professionnelles non vérifiables après relance.',
            'Signalement d\'un tiers : usurpation de titre professionnel.',
            'Photo de profil ne respectant pas les conditions d\'utilisation.',
            'Numéro de téléphone injoignable depuis plus de deux mois.',
            'Demande du titulaire, en attente de correction de son diplôme.',
        ];

        return $motifs[$i % count($motifs)];
    }

    // =======================================================================
    // PURGE DES LIGNES SANS CLÉ NATURELLE
    // =======================================================================

    /**
     * Événements, paiements et journal n'ont pas de clé naturelle stable. On
     * les efface pour les seuls comptes de démonstration avant de les
     * réécrire — les données réelles d'un poste de développement ne sont pas
     * touchées.
     *
     * @param  array<int, User>  $comptes
     */
    private function purger(array $comptes): void
    {
        $ids = collect($comptes)->pluck('id');
        $profilIds = Profile::whereIn('user_id', $ids)->pluck('id');

        ProfileEvent::whereIn('profile_id', $profilIds)->delete();
        Payment::whereIn('user_id', $ids)->forceDelete();
        Subscription::whereIn('user_id', $ids)->delete();

        AdminAction::whereIn('admin_id', User::where('email', 'like', '%@'.self::DOMAINE)->pluck('id'))->delete();
    }

    // =======================================================================
    // LES 48 ABONNEMENTS
    // =======================================================================

    /**
     * @param  array<int, User>  $comptes
     * @return array<int, Subscription> indexé par rang de compte
     */
    private function abonnements(array $comptes, array $plans): array
    {
        $abonnements = [];

        foreach ($comptes as $i => $compte) {
            $definition = $this->definitionAbonnement($i, $plans);

            if ($definition === null) {
                continue;   // 12 comptes sans abonnement, volontairement
            }

            [$plan, $statut, $debut, $fin] = $definition;

            $abonnements[$i] = Subscription::create([
                'user_id' => $compte->id,
                'plan_id' => $plan->id,
                'starts_at' => $debut,
                'ends_at' => $fin,
                'status' => $statut,
                'created_at' => $debut,
                'updated_at' => $debut,
            ]);
        }

        return $abonnements;
    }

    /** @return array{Plan, string, CarbonImmutable, CarbonImmutable}|null */
    private function definitionAbonnement(int $i, array $plans): ?array
    {
        $bornesActifs = self::NB_ACTIFS;                          // 0..24
        $bornesEssais = $bornesActifs + self::NB_ESSAIS;          // 25..36
        $bornesExpires = $bornesEssais + self::NB_EXPIRES;        // 37..44
        $bornesAnnules = self::NB_PUBLIES + self::NB_ANNULES;     // 45..47

        // Abonnements actifs : moitié mensuels, moitié annuels.
        if ($i < $bornesActifs) {
            $plan = $i % 2 === 0 ? $plans['standard'] : $plans['standard'];
            $debut = $this->maintenant->subDays(random_int(5, $plan->duration_days - 2));

            return [$plan, Subscription::STATUS_ACTIVE, $debut, $debut->addDays($plan->duration_days)];
        }

        // Essais en cours : formule gratuite, échéance proche.
        if ($i < $bornesEssais) {
            $plan = $plans['essai'];
            $debut = $this->maintenant->subDays(random_int(1, $plan->duration_days - 1));

            return [$plan, Subscription::STATUS_ACTIVE, $debut, $debut->addDays($plan->duration_days)];
        }

        // Expirés : profil toujours publié, mais hors ligne pour le public.
        if ($i < $bornesExpires) {
            $plan = $plans['standard'];
            $debut = $this->maintenant->subDays(random_int(45, 150));

            return [$plan, Subscription::STATUS_EXPIRED, $debut, $debut->addDays($plan->duration_days)];
        }

        // Annulés : portés par des brouillons, jamais par un profil publié.
        if ($i >= self::NB_PUBLIES && $i < $bornesAnnules) {
            $plan = $plans['standard'];
            $debut = $this->maintenant->subDays(random_int(60, 170));

            return [$plan, Subscription::STATUS_CANCELLED, $debut, $debut->addDays($plan->duration_days)];
        }

        return null;
    }

    // =======================================================================
    // LES 80 PAIEMENTS
    // =======================================================================

    /**
     * Répartition : 50 réussis, 18 en attente, 12 échoués.
     *
     * LES RÉUSSIS SONT RATTACHÉS À UN ABONNEMENT — c'est la règle de cohérence
     * qui compte : un encaissement sans contrepartie serait, en production, le
     * pire état possible. Les paiements en attente et échoués n'en ont pas,
     * et c'est exact : rien n'a été accordé.
     *
     * LES MONTANTS VIENNENT DE LA FORMULE, jamais d'un tirage. Un montant
     * inventé rendrait le chiffre d'affaires de la vue d'ensemble
     * invérifiable — on ne pourrait plus le recouper avec les abonnements.
     *
     * @param  array<int, User>  $comptes
     * @param  array<int, Subscription>  $abonnements
     */
    private function paiements(array $comptes, array $abonnements, array $plans): void
    {
        $payants = collect($abonnements)->filter(
            fn (Subscription $a) => $a->plan_id !== $plans['essai']->id
        );

        $moyens = [Payment::METHOD_WAVE, Payment::METHOD_ORANGE_MONEY, Payment::METHOD_FREE_MONEY];
        $compteur = 0;

        // ---- 50 réussis, adossés aux abonnements payants (avec renouvellements)
        $rangs = $payants->keys()->all();

        for ($n = 0; $n < 50; $n++) {
            $rang = $rangs[$n % count($rangs)];
            $abonnement = $abonnements[$rang];
            $plan = $plan ?? null;
            $plan = Plan::find($abonnement->plan_id);

            // Les passages suivants sur le même abonnement sont des
            // renouvellements : ils sont datés d'une période plus tôt.
            $cycles = intdiv($n, count($rangs));
            $date = CarbonImmutable::parse($abonnement->starts_at)
                ->subDays($cycles * $plan->duration_days)
                ->addMinutes(random_int(1, 600));

            $this->creerPaiement(
                $comptes[$rang], $abonnement, $plan->price_fcfa,
                $moyens[$compteur % 3], Payment::STATUS_SUCCESS, $date, ++$compteur, $plan->slug
            );
        }

        // ---- 18 en attente — le gisement de l'écran « vérification manuelle »
        for ($n = 0; $n < 18; $n++) {
            $rang = $rangs[$n % count($rangs)];
            $plan = $n % 2 === 0 ? $plans['standard'] : $plans['standard'];

            $this->creerPaiement(
                $comptes[$rang], null, $plan->price_fcfa,
                $moyens[$compteur % 3], Payment::STATUS_PENDING,
                // Plus de 24 h pour une partie : c'est ce qui déclenche
                // l'encadré d'alerte de la vue d'ensemble.
                $this->maintenant->subHours(random_int(2, 96)), ++$compteur, $plan->slug
            );
        }

        // ---- 12 échoués
        for ($n = 0; $n < 12; $n++) {
            $rang = $rangs[$n % count($rangs)];
            $plan = $plans['standard'];

            $this->creerPaiement(
                $comptes[$rang], null, $plan->price_fcfa,
                $moyens[$compteur % 3], Payment::STATUS_FAILED,
                $this->maintenant->subDays(random_int(1, 60)), ++$compteur, $plan->slug
            );
        }
    }

    private function creerPaiement(
        User $client, ?Subscription $abonnement, int $montant, string $moyen,
        string $statut, CarbonImmutable $date, int $rang, string $planSlug
    ): void {
        Payment::create([
            'user_id' => $client->id,
            'subscription_id' => $abonnement?->id,
            'provider' => 'fake',
            // Référence unique et LISIBLE : c'est elle qu'on lit à voix haute
            // au téléphone quand un client réclame.
            'provider_ref' => sprintf('QR-%s-%04d', $date->format('ym'), $rang),
            'method' => $moyen,
            'amount_fcfa' => $montant,
            'status' => $statut,
            'payload' => ['plan_slug' => $planSlug],
            'created_at' => $date,
            'updated_at' => $date,
        ]);
    }

    // =======================================================================
    // LES 2 000 ÉVÉNEMENTS
    // =======================================================================

    /**
     * DISTRIBUTION RÉALISTE, et c'est tout l'enjeu de ce bloc.
     *
     * Un tirage uniforme sur 90 jours donne une courbe plate : un graphique
     * qui ne permet ni de repérer une anomalie, ni de juger si l'échelle est
     * correcte. On applique donc trois effets observés sur du trafic réel :
     *
     *   · creux du week-end — une carte professionnelle se scanne en réunion ;
     *   · croissance douce vers le présent ;
     *   · quelques pics isolés, qui simulent un salon ou une publication.
     *
     * Répartition par type : 70 % de vues, 22 % de scans, 8 % d'enregistrements.
     * L'entonnoir est volontairement resserré — on regarde beaucoup, on scanne
     * moins, on enregistre rarement.
     *
     * Insertion PAR LOTS de 500 : 2 000 insertions unitaires prendraient
     * plusieurs minutes et décourageraient de rejouer le seeder.
     *
     * @param  array<int, Profile>  $profils
     */
    private function evenements(array $profils): void
    {
        // Seuls les profils publiés reçoivent du trafic : un brouillon n'a
        // pas d'URL publique, il ne peut pas être vu.
        $publies = collect($profils)->filter(fn (Profile $p) => $p->is_active)->values();

        if ($publies->isEmpty()) {
            return;
        }

        $poids = $this->poidsParJour();
        $total = array_sum($poids);

        $lot = [];
        $ecrits = 0;

        for ($n = 0; $n < self::NB_EVENEMENTS; $n++) {
            $jour = $this->tirerJour($poids, $total);

            $date = $this->maintenant
                ->subDays(self::NB_JOURS_EVENEMENTS - 1 - $jour)
                ->startOfDay()
                // Heures ouvrées surtout : 8 h – 20 h.
                ->addHours(random_int(8, 20))
                ->addMinutes(random_int(0, 59));

            // Les profils les plus anciens concentrent le trafic : une carte
            // publiée hier n'a pas encore été vue trois cents fois.
            $profil = $publies[$this->indexPondere($publies->count())];

            $lot[] = [
                'profile_id' => $profil->id,
                'type' => $this->typeEvenement($n),
                'ip_hash' => hash('sha256', 'demo-'.$n),
                'user_agent' => 'Mozilla/5.0 (Linux; Android 13)',
                'referer' => null,
                'created_at' => $date,
            ];

            if (count($lot) === 500) {
                ProfileEvent::insert($lot);
                $ecrits += count($lot);
                $lot = [];
            }
        }

        if ($lot !== []) {
            ProfileEvent::insert($lot);
        }
    }

    /** @return array<int, float> poids par jour, index 0 = le plus ancien */
    private function poidsParJour(): array
    {
        $poids = [];

        for ($j = 0; $j < self::NB_JOURS_EVENEMENTS; $j++) {
            $date = $this->maintenant->subDays(self::NB_JOURS_EVENEMENTS - 1 - $j);

            $base = 0.5 + 0.9 * ($j / self::NB_JOURS_EVENEMENTS);   // croissance
            $base *= $date->isWeekend() ? 0.38 : 1.0;               // creux du week-end

            // Trois pics isolés, à des dates fixes pour rester reproductibles.
            if (in_array($j, [21, 52, 77], true)) {
                $base *= 3.4;
            }

            $poids[$j] = $base;
        }

        return $poids;
    }

    /** Tirage pondéré : le jour lourd sort plus souvent. */
    private function tirerJour(array $poids, float $total): int
    {
        $cible = (mt_rand() / mt_getrandmax()) * $total;
        $cumul = 0.0;

        foreach ($poids as $jour => $p) {
            $cumul += $p;

            if ($cumul >= $cible) {
                return $jour;
            }
        }

        return array_key_last($poids);
    }

    /** Loi décroissante : les premiers profils captent l'essentiel du trafic. */
    private function indexPondere(int $n): int
    {
        $r = mt_rand() / mt_getrandmax();

        return min($n - 1, (int) floor($n * $r * $r));
    }

    private function typeEvenement(int $n): string
    {
        $rang = $n % 100;

        return match (true) {
            $rang < 70 => ProfileEvent::TYPE_VIEW,
            $rang < 92 => ProfileEvent::TYPE_SCAN,
            default => ProfileEvent::TYPE_SAVE,
        };
    }

    // =======================================================================
    // LES 30 ENTRÉES DE JOURNAL
    // =======================================================================

    /**
     * Les onze types d'action du catalogue sont représentés : le filtre « Type
     * d'action » de l'écran doit avoir quelque chose à filtrer sur chacun.
     *
     * Les motifs sont RÉDIGÉS, pas générés. Un journal rempli de « Lorem
     * ipsum » ne permet pas de juger si la colonne est assez large, ni si le
     * texte long se coupe correctement.
     *
     * @param  array<int, User>  $administrateurs
     * @param  array<int, User>  $comptes
     * @param  array<int, Profile>  $profils
     */
    private function journal(array $administrateurs, array $comptes, array $profils, array $plans): void
    {
        $modeles = Template::orderBy('id')->get();

        $scenarios = [
            [AdminActionType::BLOCAGE_COMPTE, User::class, 'Activité suspecte : douze tentatives de connexion depuis des adresses distinctes.'],
            [AdminActionType::DEBLOCAGE_COMPTE, User::class, 'Identité confirmée par pièce justificative. Blocage levé après vérification téléphonique.'],
            [AdminActionType::DESACTIVATION_PROFIL, Profile::class, 'Signalement d\'un tiers : usurpation de titre professionnel non justifiée.'],
            [AdminActionType::REACTIVATION_PROFIL, Profile::class, 'Diplôme transmis et vérifié auprès de l\'établissement. Sanction levée.'],
            [AdminActionType::PROLONGATION_ABONNEMENT, Subscription::class, '+15 jour(s) — Geste commercial suite à l\'indisponibilité du service le 12 du mois.'],
            [AdminActionType::VERIFICATION_PAIEMENT, Payment::class, 'Réclamation client : débit constaté sans activation — résultat : paiement confirmé et abonnement ouvert.'],
            [AdminActionType::VERIFICATION_PAIEMENT, Payment::class, 'Double débit signalé par le client — résultat : paiement déjà encaissé, aucun changement.'],
            [AdminActionType::MODELE_ACTIVE, Template::class, 'Modèle activé'],
            [AdminActionType::MODELE_DUPLIQUE, Template::class, 'Copie de « Classique » pour préparer la variante à bandeau vertical.'],
            [AdminActionType::MODELE_PAR_DEFAUT, Template::class, '« Moderne » devient le modèle proposé par défaut.'],
            [AdminActionType::PLAN_CREE, Plan::class, '« Annuel » — 25 000 FCFA, Annuel'],
            [AdminActionType::PLAN_MODIFIE, Plan::class, '« Mensuel » — prix : 3 000 FCFA → 2 500 FCFA'],
        ];

        $cibles = [
            User::class => collect($comptes),
            Profile::class => collect($profils),
            Subscription::class => Subscription::orderBy('id')->get(),
            Payment::class => Payment::orderBy('id')->get(),
            Template::class => $modeles,
            Plan::class => collect($plans)->values(),
        ];

        $lignes = [];

        for ($n = 0; $n < self::NB_ENTREES_JOURNAL; $n++) {
            [$action, $type, $motif] = $scenarios[$n % count($scenarios)];

            $pool = $cibles[$type];
            $cible = $pool->isEmpty() ? null : $pool[$n % $pool->count()];

            $lignes[] = [
                'admin_id' => $administrateurs[$n % count($administrateurs)]->id,
                'action' => $action,
                'target_type' => $cible ? $type : null,
                'target_id' => $cible?->getKey(),
                'reason' => $motif,
                // Réparties sur 45 jours, ordre décroissant : la première page
                // du journal montre bien les entrées les plus récentes.
                'created_at' => $this->maintenant->subDays((int) ($n * 1.5))->subHours(random_int(0, 20)),
            ];
        }

        AdminAction::insert($lignes);
    }

    // =======================================================================
    // BILAN
    // =======================================================================

    /**
     * Le seeder DIT ce qu'il a produit. Annoncer 2 000 événements et en
     * écrire 1 850 sans que rien ne le signale rendrait tout écran de
     * statistiques suspect sans qu'on sache pourquoi.
     */
    private function bilan(): void
    {
        $ids = User::where('email', 'like', '%@'.self::DOMAINE)->pluck('id');
        $profilIds = Profile::whereIn('user_id', $ids)->pluck('id');

        $lignes = [
            'Administrateurs' => User::whereIn('id', $ids)->where('role', User::ROLE_ADMIN)->count(),
            'Comptes clients' => User::whereIn('id', $ids)->where('role', User::ROLE_USER)->count(),
            'Profils publiés' => Profile::whereIn('id', $profilIds)->published()->count(),
            'Profils brouillons' => Profile::whereIn('id', $profilIds)->draft()->count(),
            'Profils désactivés' => Profile::whereIn('id', $profilIds)->deactivated()->count(),
            'Abonnements actifs' => Subscription::whereIn('user_id', $ids)->where('status', Subscription::STATUS_ACTIVE)->count(),
            'Abonnements expirés' => Subscription::whereIn('user_id', $ids)->where('status', Subscription::STATUS_EXPIRED)->count(),
            'Abonnements annulés' => Subscription::whereIn('user_id', $ids)->where('status', Subscription::STATUS_CANCELLED)->count(),
            'Paiements réussis' => Payment::whereIn('user_id', $ids)->where('status', Payment::STATUS_SUCCESS)->count(),
            'Paiements en attente' => Payment::whereIn('user_id', $ids)->where('status', Payment::STATUS_PENDING)->count(),
            'Paiements échoués' => Payment::whereIn('user_id', $ids)->where('status', Payment::STATUS_FAILED)->count(),
            'Événements de profil' => ProfileEvent::whereIn('profile_id', $profilIds)->count(),
            'Entrées de journal' => AdminAction::whereIn('admin_id', $ids)->count(),
        ];

        $ca = (int) Payment::whereIn('user_id', $ids)->where('status', Payment::STATUS_SUCCESS)->sum('amount_fcfa');

        $this->command?->newLine();
        $this->command?->info('AdminDemoSeeder — volumes obtenus');
        $this->command?->table(['Élément', 'Total'], collect($lignes)
            ->map(fn ($v, $k) => [$k, number_format($v, 0, ',', ' ')])
            ->values()
            ->push(['Chiffre d\'affaires', number_format($ca, 0, ',', ' ').' FCFA'])
            ->all());
        $this->command?->line('  Mot de passe commun : '.self::MOT_DE_PASSE);
    }
}
