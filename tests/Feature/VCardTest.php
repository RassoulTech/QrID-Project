<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Profile;
use App\Models\SocialLink;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * « ENREGISTRER LE CONTACT » — la fiche vCard.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * CE QU'ELLE PROTÈGE
 * ═══════════════════════════════════════════════════════════════════════
 * C'est le geste qui termine le parcours : on scanne un QR, on regarde une
 * carte, et on GARDE le contact. Sans lui, le visiteur devrait recopier un
 * numéro à la main — ce que personne ne fait. La carte est vue puis oubliée,
 * et le scan n'aura servi à rien.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * POURQUOI CES TESTS-LÀ
 * ═══════════════════════════════════════════════════════════════════════
 * Un vCard mal formé n'émet AUCUNE erreur. Le téléphone ouvre le fichier,
 * n'enregistre rien ou enregistre un nom amputé, et se referme. Rien n'est
 * journalisé, ni chez nous ni chez le visiteur. Les tests portent donc sur ce
 * qui casse en silence : l'échappement, l'encodage, les fins de ligne et les
 * gardes d'accès.
 */
class VCardTest extends TestCase
{
    use RefreshDatabase;

    private Profile $profile;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $plan = Plan::factory()->create(['slug' => 'standard', 'price_fcfa' => 2500, 'duration_days' => 30]);
        $user = User::factory()->create();

        Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'ends_at' => now()->addDays(20),
        ]);

        $this->profile = Profile::factory()->for($user)->create([
            'slug' => 'awa-ndiaye',
            'first_name' => 'Awa',
            'last_name' => 'Ndiaye',
            'job_title' => 'Consultante en gestion',
            'company' => 'Teranga Conseil',
            'phone' => '+221770000001',
            'whatsapp' => '+221770000001',
            'public_email' => 'awa@exemple.sn',
            'cover_path' => null,
            'is_active' => true,
        ]);
    }

    // =======================================================================
    // CE QUE LE TÉLÉPHONE REÇOIT
    // =======================================================================

    /**
     * L'EN-TÊTE DÉPEND DE L'APPAREIL, et ce n'est pas une préférence.
     *
     * ═══════════════════════════════════════════════════════════════════
     * POURQUOI CE TEST A CHANGÉ D'AVIS DEUX FOIS
     * ═══════════════════════════════════════════════════════════════════
     * Il a d'abord exigé « attachment », au motif que sans lui Android
     * afficherait la fiche en texte brut. Puis « inline », au motif que
     * « attachment » faisait télécharger sur iPhone. Les deux raisonnements
     * étaient justes sur le risque et faux sur le remède.
     *
     * Le fait, mesuré : dès qu'une réponse porte un Content-Disposition
     * AVEC un nom de fichier, Safari sur iOS la range dans Fichiers.
     * « inline » ne change que l'endroit du rangement, pas la décision.
     * Sans aucune disposition, Safari se fie au type MIME, reconnaît
     * text/vcard et propose « Ajouter aux contacts ».
     *
     * Sur ordinateur, le téléchargement reste ce qu'on attend : un
     * navigateur de bureau n'a pas d'application Contacts à ouvrir.
     *
     * Le même en-tête produit donc deux comportements système opposés.
     * C'est le seul endroit du produit où l'appareil est reniflé, et c'est
     * pour cela.
     */
    public function test_a_phone_gets_no_disposition_so_contacts_opens_it(): void
    {
        foreach ([
            'iPhone' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1',
            'Android' => 'Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36 Chrome/120 Mobile Safari/537.36',
        ] as $appareil => $agent) {
            $reponse = $this->withHeader('User-Agent', $agent)
                ->get(route('profile.vcard', 'awa-ndiaye'))
                ->assertOk();

            $this->assertStringContainsString('text/vcard', (string) $reponse->headers->get('Content-Type'));
            $this->assertNull(
                $reponse->headers->get('Content-Disposition'),
                $appareil.' reçoit une disposition : la fiche sera rangée dans les fichiers au lieu des contacts.'
            );
        }
    }

    /**
     * L'ADRESSE N'A PAS D'EXTENSION, et c'est ce qui décide sur iPhone.
     *
     * ═══════════════════════════════════════════════════════════════════
     * CE QUE TROIS CORRECTIONS D'EN-TÊTE N'AVAIENT PAS VU
     * ═══════════════════════════════════════════════════════════════════
     * iOS regarde D'ABORD l'extension de l'adresse, avant le type MIME. Une
     * URL en « .vcf » est traitée comme un document à télécharger, quels que
     * soient les en-têtes — ce qui explique que ni « attachment », ni
     * « inline », ni l'absence de disposition n'aient rien changé : on
     * discutait d'un en-tête que Safari ne consultait jamais.
     *
     * Ce test garde l'adresse propre. Si quelqu'un remet une extension pour
     * « faire plus clair », le téléchargement revient et personne ne
     * comprendra pourquoi.
     */
    public function test_the_contact_route_carries_no_file_extension(): void
    {
        $this->assertStringEndsWith(
            '/contact',
            route('profile.vcard', 'awa-ndiaye'),
            "L'adresse porte une extension : iOS téléchargera au lieu d'ouvrir les contacts."
        );

        // L'ancienne adresse reste servie : des cartes sont déjà imprimées.
        $this->get('/p/awa-ndiaye/contact.vcf')->assertOk();
    }

    /** SUR ORDINATEUR, le fichier reste la bonne réponse — et il est nommé. */
    public function test_a_desktop_browser_gets_a_named_file(): void
    {
        $reponse = $this->withHeader(
            'User-Agent',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36'
        )->get(route('profile.vcard', 'awa-ndiaye'))->assertOk();

        $disposition = (string) $reponse->headers->get('Content-Disposition');

        $this->assertStringContainsString('attachment', $disposition);
        $this->assertStringContainsString('awa-ndiaye.vcf', $disposition);
    }

    /** Les coordonnées attendues sont toutes là. */
    public function test_it_carries_the_contact_details(): void
    {
        $fiche = $this->get(route('profile.vcard', 'awa-ndiaye'))->getContent();

        $this->assertStringContainsString('BEGIN:VCARD', $fiche);
        $this->assertStringContainsString('VERSION:3.0', $fiche);
        $this->assertStringContainsString('FN:Awa Ndiaye', $fiche);
        $this->assertStringContainsString('N:Ndiaye;Awa;;;', $fiche);
        $this->assertStringContainsString('TITLE:Consultante en gestion', $fiche);
        $this->assertStringContainsString('ORG:Teranga Conseil', $fiche);
        $this->assertStringContainsString('EMAIL;TYPE=INTERNET:awa@exemple.sn', $fiche);
        $this->assertStringContainsString('END:VCARD', $fiche);
    }

    /**
     * Le numéro part au format international.
     *
     * C'est ce qui rend la fiche appelable depuis l'étranger, ou depuis un
     * téléphone dont l'indicatif par défaut n'est pas le 221.
     */
    public function test_the_phone_number_keeps_its_country_code(): void
    {
        $this->assertStringContainsString(
            'TEL;TYPE=CELL,VOICE:+221770000001',
            $this->get(route('profile.vcard', 'awa-ndiaye'))->getContent()
        );
    }

    /**
     * Un WhatsApp identique au téléphone n'est pas répété.
     *
     * Sinon la fiche enregistrée montre deux fois le même numéro, et le
     * visiteur croit s'être trompé.
     */
    public function test_it_does_not_repeat_whatsapp_when_it_equals_the_phone(): void
    {
        $fiche = $this->get(route('profile.vcard', 'awa-ndiaye'))->getContent();

        $this->assertSame(1, substr_count($fiche, '+221770000001'));
    }

    // =======================================================================
    // CE QUI CASSE EN SILENCE
    // =======================================================================

    /**
     * LE TEST LE PLUS IMPORTANT DU FICHIER — la virgule dans une raison sociale.
     *
     * Dans un vCard, la virgule sépare deux valeurs d'un même champ. Non
     * échappée, « Cabinet Sall, Diop & Associés » s'enregistre amputé après
     * « Sall » — et rien, nulle part, ne le signale. Le point-virgule, lui,
     * sépare les composantes : il déplacerait carrément les champs suivants.
     */
    public function test_commas_and_semicolons_never_split_a_value(): void
    {
        $this->profile->update([
            'company' => 'Cabinet Sall, Diop & Associés',
            'job_title' => 'Avocate ; associée',
        ]);

        $fiche = $this->get(route('profile.vcard', 'awa-ndiaye'))->getContent();

        $this->assertStringContainsString('ORG:Cabinet Sall\\, Diop & Associés', $fiche);
        $this->assertStringContainsString('TITLE:Avocate \\; associée', $fiche);
    }

    /** Une bio sur plusieurs lignes ne doit pas casser la structure du fichier. */
    public function test_a_multiline_bio_becomes_an_escaped_single_line(): void
    {
        $this->profile->update(['bio' => "Première ligne\nSeconde ligne"]);

        $fiche = $this->get(route('profile.vcard', 'awa-ndiaye'))->getContent();

        $this->assertStringContainsString('NOTE:Première ligne\\nSeconde ligne', $fiche);
        $this->assertStringNotContainsString("NOTE:Première ligne\nSeconde", $fiche);
    }

    /**
     * Les accents survivent au trajet.
     *
     * « Aïssatou » et « Thiès » sont la norme ici, pas l'exception : un
     * encodage abîmé rendrait la moitié des fiches fausses.
     */
    public function test_accented_names_survive(): void
    {
        $this->profile->update(['first_name' => 'Aïssatou', 'address' => 'Thiès, Sénégal']);

        $fiche = $this->get(route('profile.vcard', 'awa-ndiaye'))->getContent();

        $this->assertStringContainsString('Aïssatou', $fiche);
        $this->assertStringContainsString('Thiès', $fiche);
        // La virgule de l'adresse est échappée : sans cela, « Sénégal »
        // deviendrait une seconde valeur du champ.
        $this->assertStringContainsString('ADR;TYPE=WORK:;;Thiès\\, Sénégal;;;;', $fiche);
    }

    /** La RFC impose des CRLF ; un lecteur strict rejette des LF seuls. */
    public function test_lines_end_with_crlf(): void
    {
        $fiche = $this->get(route('profile.vcard', 'awa-ndiaye'))->getContent();

        $this->assertStringContainsString("BEGIN:VCARD\r\nVERSION:3.0\r\n", $fiche);
    }

    // =======================================================================
    // LA PHOTO — UTILE, JAMAIS INDISPENSABLE
    // =======================================================================

    /**
     * La photo est EMBARQUÉE, pas liée.
     *
     * Une PHOTO;VALUE=URI obligerait le téléphone à retélécharger l'image plus
     * tard : elle disparaîtrait le jour où le profil est dépublié. Un contact
     * enregistré doit survivre à la carte dont il vient.
     */
    public function test_the_photo_is_embedded_when_it_exists(): void
    {
        Storage::disk('public')->put('couvertures/awa.jpg', 'contenu-binaire-fictif');
        $this->profile->update(['cover_path' => 'couvertures/awa.jpg']);

        $fiche = $this->get(route('profile.vcard', 'awa-ndiaye'))->getContent();

        $this->assertStringContainsString('PHOTO;ENCODING=b;TYPE=JPEG:', $fiche);
        $this->assertStringContainsString(base64_encode('contenu-binaire-fictif'), $fiche);
    }

    /**
     * CONTAINMENT — une photo manquante coûte le portrait, jamais la fiche.
     *
     * Le cas est réel : `FILESYSTEM_DISK=local` efface les photos à chaque
     * déploiement (risque n° 3 du plan). Le jour où cela arrive, les contacts
     * doivent continuer de s'enregistrer.
     */
    public function test_a_missing_photo_never_breaks_the_download(): void
    {
        $this->profile->update(['cover_path' => 'couvertures/effacee-par-un-deploiement.jpg']);

        $fiche = $this->get(route('profile.vcard', 'awa-ndiaye'))->assertOk()->getContent();

        $this->assertStringContainsString('FN:Awa Ndiaye', $fiche);
        $this->assertStringNotContainsString('PHOTO', $fiche);
    }

    // =======================================================================
    // LES GARDES D'ACCÈS
    // =======================================================================

    /**
     * LA FICHE N'EST PAS UNE SECONDE PORTE.
     *
     * Un profil dépublié ne doit pas laisser fuir ses coordonnées par une
     * autre adresse : ce serait contourner l'abonnement en changeant d'URL.
     * Une 404 et non un 403, pour ne rien révéler de son existence.
     */
    public function test_an_invisible_profile_yields_a_404(): void
    {
        $this->profile->update(['is_active' => false]);

        $this->get(route('profile.vcard', 'awa-ndiaye'))->assertNotFound();
    }

    /** Un slug inconnu ne révèle rien de plus. */
    public function test_an_unknown_slug_yields_a_404(): void
    {
        $this->get(route('profile.vcard', 'personne-de-ce-nom'))->assertNotFound();
    }

    // =======================================================================
    // LE CHEMIN DEPUIS LA CARTE
    // =======================================================================

    /**
     * Le bouton existe sur la page publique et mène à la fiche.
     *
     * Il a manqué pendant des semaines alors que le README le promettait et
     * que la maquette de la page d'accueil le dessinait : le bouton était une
     * IMAGE de bouton. Ce test empêche d'y revenir.
     */
    public function test_the_public_page_offers_the_button(): void
    {
        $html = $this->get(route('profile.public', 'awa-ndiaye'))->assertOk()->getContent();

        /*
         | ON VISE LE LIEN, PAS LA FORMULATION.
         |
         | Le libellé a changé de « Enregistrer le contact » à « Enregistrer »
         | en suivant la maquette, et ce test tombait — alors que le bouton
         | fonctionnait. Un test qui casse à chaque retouche de vocabulaire
         | finit par être neutralisé, et c'est alors le vrai défaut qui passe.
         |
         | Ce qui compte est qu'un LIEN DIRECT existe vers la route de
         | téléchargement : c'est lui qui casse sur iOS quand on le remplace
         | par un appel JavaScript.
         */
        $this->assertStringContainsString('href="'.route('profile.vcard', 'awa-ndiaye').'"', $html);
        $this->assertStringContainsString('Enregistrer', $html);
    }

    /** Les réseaux du profil voyagent avec la fiche. */
    public function test_social_links_travel_with_the_card(): void
    {
        SocialLink::factory()->create([
            'profile_id' => $this->profile->id,
            'url' => 'https://linkedin.com/in/awa-ndiaye',
        ]);

        $this->assertStringContainsString(
            'URL:https://linkedin.com/in/awa-ndiaye',
            $this->get(route('profile.vcard', 'awa-ndiaye'))->getContent()
        );
    }

    /** La fiche porte l'adresse de la carte : le contact garde le chemin du retour. */
    public function test_it_points_back_to_the_public_card(): void
    {
        $this->assertStringContainsString(
            'SOURCE:'.route('profile.public', 'awa-ndiaye'),
            $this->get(route('profile.vcard', 'awa-ndiaye'))->getContent()
        );
    }
}
