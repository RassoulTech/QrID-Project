<?php

namespace App\Console\Commands;

use App\Services\DiscordNotifier;
use App\Services\RapportQuotidien;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * LE RÉCAPITULATIF DU SOIR.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * IL PART TOUS LES JOURS, MÊME QUAND IL N'Y A RIEN À DIRE
 * ═══════════════════════════════════════════════════════════════════════
 * C'est la règle la plus importante de cette commande, et la moins évidente.
 *
 * Un récapitulatif qui se tait les jours creux rend l'absence de message
 * AMBIGUË : personne ne peut plus distinguer « rien ne s'est passé » de
 * « l'automatisation est cassée ». Et c'est toujours la seconde qu'on découvre
 * trop tard — on s'habitue au silence, puis on constate un mois plus tard que
 * le planificateur ne tourne plus.
 *
 * Le message d'une journée vide est donc COURT, mais il existe. Sa seule
 * fonction est de prouver que la chaîne fonctionne.
 *
 * C'est le même raisonnement qui a fait retirer le voyant rouge permanent de
 * GitHub Actions : un signal qu'on apprend à ignorer ne signale plus rien.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LES ALERTES PASSENT DEVANT LES CHIFFRES
 * ═══════════════════════════════════════════════════════════════════════
 * Un paiement bloqué ou un e-mail qui ne part pas appelle une action ce soir ;
 * le nombre d'inscriptions se lit demain. La couleur de l'embed change avec,
 * pour que la distinction se voie avant même d'avoir lu.
 */
class SendDailyReport extends Command
{
    protected $signature = 'report:daily
                            {--dry-run : Affiche le récapitulatif sans l\'envoyer}';

    protected $description = 'Envoie le récapitulatif quotidien sur Discord.';

    public function handle(RapportQuotidien $rapport, DiscordNotifier $discord): int
    {
        $simulation = (bool) $this->option('dry-run');

        $alertes = $rapport->alertes();
        $chiffres = $rapport->chiffres();
        $etat = $rapport->etat();
        $vide = $rapport->journeeVide();

        $titre = $rapport->jour()->translatedFormat('l j F Y');

        $description = $this->description($alertes, $vide);
        $champs = $vide && $alertes === [] ? [] : $this->champs($chiffres, $etat);

        $this->afficher($titre, $description, $champs);

        if ($simulation) {
            $this->newLine();
            $this->info('Simulation — rien n\'a été envoyé.');

            return self::SUCCESS;
        }

        if (! DiscordNotifier::estConfigure()) {
            /*
             | On ÉCHOUE bruyamment plutôt que de rendre un succès trompeur.
             |
             | Une commande qui rend SUCCESS sans rien envoyer donnerait, dans
             | un journal de planificateur, exactement la même ligne qu'un
             | envoi réussi. Le jour où l'on cherchera pourquoi le salon est
             | muet, cette ligne fera perdre une heure.
             */
            $this->error('DISCORD_WEBHOOK_URL n\'est pas configuré : aucun envoi.');

            return self::FAILURE;
        }

        $parti = $discord->envoyer(
            titre: 'Récapitulatif — '.$titre,
            description: $description,
            champs: $champs,
            couleur: $alertes === [] ? DiscordNotifier::COULEUR_OK : DiscordNotifier::COULEUR_ALERTE,
            pied: config('app.name'),
        );

        Log::info('Récapitulatif quotidien', [
            'envoye' => $parti,
            'alertes' => count($alertes),
            'journee_vide' => $vide,
        ]);

        if (! $parti) {
            $this->error('Le récapitulatif n\'a pas pu être envoyé. Voir les journaux.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Récapitulatif envoyé.');

        return self::SUCCESS;
    }

    // -----------------------------------------------------------------------

    /**
     * La description, en tête de l'embed.
     *
     * @param  array<int, string>  $alertes
     */
    private function description(array $alertes, bool $vide): string
    {
        if ($alertes !== []) {
            // Une puce par alerte : trois lignes courtes se lisent d'un coup
            // d'œil, un paragraphe de trois phrases ne se lit pas.
            return "**À traiter**\n".collect($alertes)
                ->map(fn (string $a) => '• '.$a)
                ->implode("\n");
        }

        if ($vide) {
            return 'Aucune activité aujourd\'hui. Tout fonctionne.';
        }

        return 'Rien à signaler.';
    }

    /**
     * Les champs de l'embed : les chiffres du jour, puis l'état courant.
     *
     * @param  array<string, array{libelle:string, valeur:int, veille:int, unite:?string}>  $chiffres
     * @param  array<string, int>  $etat
     * @return array<int, array{name:string, value:string, inline:bool}>
     */
    private function champs(array $chiffres, array $etat): array
    {
        $champs = [];

        foreach ($chiffres as $chiffre) {
            $champs[] = [
                'name' => $chiffre['libelle'],
                'value' => $this->valeur($chiffre),
                'inline' => true,
            ];
        }

        $champs[] = [
            'name' => 'État actuel',
            'value' => sprintf(
                "%s abonnements actifs\n%s essais en cours\n%s cartes en ligne",
                number_format($etat['abonnements_actifs'], 0, ',', ' '),
                number_format($etat['essais'], 0, ',', ' '),
                number_format($etat['cartes_en_ligne'], 0, ',', ' '),
            ),
            'inline' => false,
        ];

        return $champs;
    }

    /**
     * La valeur, suivie de la comparaison à la veille.
     *
     * LA COMPARAISON EST EN VALEUR ABSOLUE, jamais en pourcentage. Passer de 1
     * à 2 inscriptions n'est pas « +100 % » : sur de petits nombres, le
     * pourcentage impressionne sans rien dire. « 2 (hier 1) » se lit sans
     * interprétation.
     *
     * @param  array{libelle:string, valeur:int, veille:int, unite:?string}  $chiffre
     */
    private function valeur(array $chiffre): string
    {
        $format = fn (int $n) => number_format($n, 0, ',', ' ')
            .($chiffre['unite'] ? ' '.$chiffre['unite'] : '');

        $ecart = $chiffre['valeur'] - $chiffre['veille'];

        $tendance = match (true) {
            $ecart > 0 => ' ▲',
            $ecart < 0 => ' ▼',
            default => '',
        };

        return '**'.$format($chiffre['valeur']).'**'.$tendance
            ."\nhier : ".$format($chiffre['veille']);
    }

    /**
     * Le même contenu, à l'écran.
     *
     * Ce n'est pas un doublon de confort : c'est ce qui rend `--dry-run`
     * utile, et ce qu'on lit dans les journaux du planificateur quand le salon
     * Discord reste muet.
     *
     * @param  array<int, array{name:string, value:string, inline:bool}>  $champs
     */
    private function afficher(string $titre, string $description, array $champs): void
    {
        $this->newLine();
        $this->line('<comment>Récapitulatif — '.$titre.'</comment>');
        $this->newLine();
        $this->line(strip_tags(str_replace(['**', '• '], ['', ' - '], $description)));

        if ($champs === []) {
            return;
        }

        $this->newLine();
        $this->table(
            ['Indicateur', 'Valeur'],
            collect($champs)->map(fn (array $c) => [
                $c['name'],
                str_replace(["\n", '**'], [' · ', ''], $c['value']),
            ])->all()
        );
    }
}
