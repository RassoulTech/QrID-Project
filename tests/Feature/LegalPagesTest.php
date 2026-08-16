<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * LES TROIS PAGES LÉGALES — obligatoires avant toute vente.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CE QUE CES TESTS PROTÈGENT
 * ═══════════════════════════════════════════════════════════════════════
 * Une mention légale absente expose. Une mention légale FAUSSE expose
 * davantage : elle engage sur des informations erronées, et personne ne s'en
 * aperçoit avant le jour où quelqu'un les vérifie — un client mécontent, un
 * agrégateur de paiement, une autorité.
 *
 * Ces pages ont longtemps été une trame à trous portant, en clair et sous les
 * yeux des clients, la mention « à compléter ». Ces tests garantissent
 * qu'elles ne le redeviennent pas.
 */
class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string}> */
    public static function pages(): array
    {
        return [
            'conditions générales' => ['legal.conditions'],
            'confidentialité' => ['legal.confidentialite'],
            'mentions légales' => ['legal.mentions'],
        ];
    }

    private function html(string $route): string
    {
        return $this->get(route($route))->assertOk()->getContent();
    }

    #[DataProvider('pages')]
    public function test_the_page_renders(string $route): void
    {
        $this->assertNotSame('', trim($this->html($route)));
    }

    /**
     * AUCUN AVEU D'INACHÈVEMENT SOUS LES YEUX D'UN CLIENT.
     *
     * Le bloc « À compléter » s'adressait à l'équipe et se lisait par les
     * clients. Sur une page qui engage, il détruit exactement la confiance
     * qu'elle est censée établir : quelqu'un qui vérifie à qui il achète y
     * lisait que le vendeur n'avait pas fini de se présenter.
     */
    #[DataProvider('pages')]
    public function test_no_placeholder_reaches_the_reader(string $route): void
    {
        $texte = mb_strtolower(strip_tags($this->html($route)));

        foreach (['à compléter', 'a completer', 'lorem', 'xxx', 'trame'] as $marqueur) {
            $this->assertStringNotContainsString(
                $marqueur,
                $texte,
                "La page « {$route} » porte encore un texte de remplissage."
            );
        }
    }

    /**
     * LES MENTIONS LÉGALES PORTENT L'IDENTITÉ RÉELLE.
     *
     * RCCM, NINEA, adresse, responsable de publication : ce sont les quatre
     * éléments qu'on vient y chercher, et les quatre qui manquaient.
     */
    public function test_the_legal_notice_carries_the_real_identity(): void
    {
        $html = $this->html('legal.mentions');

        foreach ([
            config('legal.editeur.denomination'),
            config('legal.editeur.rccm'),
            config('legal.editeur.ninea'),
            config('legal.editeur.adresse'),
            config('legal.editeur.responsable'),
            config('legal.hebergeur.nom'),
        ] as $mention) {
            $this->assertStringContainsString(
                $mention,
                $html,
                "Les mentions légales ne portent plus : {$mention}"
            );
        }
    }

    /**
     * LES DONNÉES PERSONNELLES DU GÉRANT NE SONT JAMAIS PUBLIÉES.
     *
     * Le numéro de carte d'identité et la date de naissance figurent sur les
     * documents d'immatriculation. Ils ne sont exigés par aucune mention
     * légale, et les publier sur un site ouvert reviendrait à les exposer à
     * quiconque voudrait usurper cette identité.
     */
    #[DataProvider('pages')]
    public function test_no_personal_identity_document_is_ever_published(string $route): void
    {
        $html = $this->html($route);

        foreach (['1935200200613', '1 935 2002 00613', '16/11/2002', 'Pikine'] as $donnee) {
            $this->assertStringNotContainsString(
                $donnee,
                $html,
                "La page « {$route} » publie une donnée personnelle du gérant : {$donnee}"
            );
        }
    }

    /**
     * LES CONDITIONS DISENT CE QU'ON VEND ET À QUEL PRIX.
     *
     * Vendre un abonnement sans annoncer la monnaie, le moyen de paiement et
     * l'absence de reconduction automatique est précisément ce qui produit
     * les litiges qu'on cherche à éviter.
     */
    public function test_the_terms_state_what_is_sold_and_how_it_is_paid(): void
    {
        $texte = mb_strtolower(strip_tags($this->html('legal.conditions')));

        foreach (['francs cfa', 'wave', 'orange money', 'free money', 'essai', 'rétractation'] as $point) {
            $this->assertStringContainsString(
                $point,
                $texte,
                "Les conditions générales ne traitent plus de : {$point}"
            );
        }

        // L'absence de reconduction automatique est ce qu'un client vérifie en
        // premier, et ce qui provoque le plus de réclamations quand il l'ignore.
        $this->assertStringContainsString('reconduction automatique', $texte);
    }

    /** La politique de confidentialité cite la loi et l'autorité compétentes. */
    public function test_the_privacy_policy_names_the_applicable_law(): void
    {
        $texte = strip_tags($this->html('legal.confidentialite'));

        $this->assertStringContainsString('2008-12', $texte, 'La loi sénégalaise applicable n\'est plus citée.');
        $this->assertStringContainsString('Commission de protection des données personnelles', $texte);
    }

    /**
     * AUCUNE COORDONNÉE BANCAIRE N'EST COLLECTÉE, et la page le dit.
     *
     * C'est vrai — les paiements passent par l'opérateur — et c'est ce qui
     * rassure le plus quelqu'un qui hésite à payer sur un site qu'il découvre.
     */
    public function test_the_privacy_policy_states_no_bank_details_are_kept(): void
    {
        $texte = mb_strtolower(strip_tags($this->html('legal.confidentialite')));

        $this->assertStringContainsString('aucune coordonnée bancaire', $texte);
    }

    /** Les trois pages portent une date de mise à jour. */
    #[DataProvider('pages')]
    public function test_each_page_is_dated(string $route): void
    {
        $this->assertStringContainsString(
            config('legal.mise_a_jour'),
            $this->html($route),
            "La page « {$route} » ne porte pas de date : on ne saura pas quelles conditions s'appliquaient quand."
        );
    }

    /** Les trois pages sont atteignables depuis le pied de page public. */
    public function test_the_three_pages_are_reachable_from_the_landing(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        foreach (['legal.conditions', 'legal.confidentialite', 'legal.mentions'] as $route) {
            $this->assertStringContainsString(route($route), $html);
        }
    }
}
