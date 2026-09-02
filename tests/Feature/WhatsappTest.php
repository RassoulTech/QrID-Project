<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Support\Whatsapp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * LES MESSAGES WHATSAPP.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * CE QUE CE FICHIER PROTÈGE
 * ═══════════════════════════════════════════════════════════════════════════
 * Trois choses, et la troisième est la plus importante :
 *
 *   1. Un lien mal formé ne s'ouvre pas — wa.me n'accepte que des chiffres,
 *      et un « + » suffit à produire une erreur WhatsApp. La personne conclut
 *      que le bouton est cassé, et elle a raison.
 *
 *   2. Une variable non remplacée reste affichée telle quelle. « Voici ma
 *      carte :url » part alors sans adresse, et le message ne sert à rien.
 *
 *   3. AUCUNE DONNÉE NON PUBLIQUE ne doit entrer dans un message. Le texte
 *      voyage dans une URL que le navigateur retient et que WhatsApp Web
 *      affiche. C'est la règle posée par AideContextuelle ; elle se perdrait
 *      au premier gabarit ajouté sans y penser.
 */
class WhatsappTest extends TestCase
{
    use RefreshDatabase;

    private function carte(array $attributs = []): Profile
    {
        return Profile::factory()->create(array_merge([
            'first_name' => 'Awa',
            'last_name' => 'Ndiaye',
            'slug' => 'awa-ndiaye',
            'phone' => '+221773831364',
            'whatsapp' => null,
        ], $attributs));
    }

    // =======================================================================
    // LA FORME DU LIEN
    // =======================================================================

    /**
     * wa.me N'ACCEPTE QUE DES CHIFFRES.
     *
     * Le numéro est stocké au format international, « + » compris. Le passer
     * tel quel produit une adresse que WhatsApp refuse d'ouvrir.
     */
    public function test_the_link_keeps_only_digits_from_the_number(): void
    {
        $lien = Whatsapp::lien('+221 77 383 13 64', 'Bonjour');

        $this->assertStringStartsWith('https://wa.me/221773831364?text=', $lien);
        $this->assertStringNotContainsString('+', explode('?', $lien)[0]);
    }

    /**
     * SANS NUMÉRO, le lien ouvre le sélecteur de contacts — c'est ce qu'il
     * faut pour un bouton « Partager », où l'on ne sait pas encore à qui
     * l'on écrit.
     */
    public function test_a_share_link_carries_no_recipient(): void
    {
        $lien = Whatsapp::lien(null, 'Bonjour');

        $this->assertStringStartsWith('https://wa.me/?text=', $lien);
    }

    /** Le texte est encodé : un espace ou un retour à la ligne casserait l'URL. */
    public function test_the_message_is_url_encoded(): void
    {
        $lien = Whatsapp::lien(null, "Deux lignes\net une espace");

        $this->assertStringNotContainsString(' ', $lien);
        $this->assertStringContainsString('%0A', $lien);
    }

    // =======================================================================
    // LES VARIABLES
    // =======================================================================

    /**
     * UNE VARIABLE NON REMPLACÉE PART DANS LE MESSAGE.
     *
     * C'est la panne la plus visible et la plus embarrassante : le client
     * envoie « voici ma carte :url » à un prospect.
     */
    public function test_no_placeholder_survives_in_a_share_message(): void
    {
        $carte = $this->carte();
        $lien = Whatsapp::partageDeLaCarte($carte, 'https://qrid.sn/awa-ndiaye');

        $texte = urldecode(explode('text=', $lien)[1]);

        $this->assertStringNotContainsString(':nom', $texte);
        $this->assertStringNotContainsString(':url', $texte);
        $this->assertStringContainsString('Awa Ndiaye', $texte);
        $this->assertStringContainsString('https://qrid.sn/awa-ndiaye', $texte);
    }

    public function test_the_qr_message_also_carries_its_link(): void
    {
        $carte = $this->carte();
        $texte = urldecode(explode('text=', Whatsapp::partageDuQrCode($carte, 'https://qrid.sn/awa-ndiaye'))[1]);

        $this->assertStringNotContainsString(':url', $texte);
        $this->assertStringContainsString('https://qrid.sn/awa-ndiaye', $texte);
    }

    public function test_the_invitation_carries_the_product_link(): void
    {
        $texte = urldecode(explode('text=', Whatsapp::invitation('https://qrid.sn'))[1]);

        $this->assertStringNotContainsString(':url', $texte);
        $this->assertStringContainsString('https://qrid.sn', $texte);
    }

    // =======================================================================
    // LA CONFIDENTIALITÉ
    // =======================================================================

    /**
     * LE MESSAGE ADRESSÉ AU TITULAIRE NE PORTE RIEN DU VISITEUR.
     *
     * Il ne s'est pas identifié. Glisser quoi que ce soit sur lui serait une
     * invention, et une invention qui voyage hors de l'application.
     */
    public function test_the_owner_contact_message_carries_nothing_private(): void
    {
        $carte = $this->carte([
            'public_email' => 'awa@exemple.test',
            'whatsapp' => '+221771112233',
        ]);

        $texte = urldecode(explode('text=', (string) Whatsapp::contactDuTitulaire($carte))[1]);

        $this->assertStringNotContainsString('awa@exemple.test', $texte,
            "L'adresse e-mail est entrée dans un message qui part hors de l'application.");
        $this->assertStringNotContainsString((string) $carte->id, $texte);
        $this->assertStringContainsString('Awa', $texte);
    }

    /** Le WhatsApp du titulaire prime sur son téléphone, s'il en a un. */
    public function test_the_owners_whatsapp_number_wins_over_the_phone(): void
    {
        $carte = $this->carte(['whatsapp' => '+221771112233']);

        $this->assertStringStartsWith(
            'https://wa.me/221771112233?text=',
            (string) Whatsapp::contactDuTitulaire($carte),
        );
    }

    /**
     * SANS NUMÉRO, PAS DE BOUTON.
     *
     * Un lien vers une conversation avec un numéro inexistant est pire que
     * pas de lien : quelqu'un qui voulait joindre le titulaire rencontre un
     * second problème.
     */
    public function test_no_link_when_the_owner_left_no_number(): void
    {
        $carte = $this->carte(['phone' => null, 'whatsapp' => null]);

        $this->assertNull(Whatsapp::contactDuTitulaire($carte));
    }

    // =======================================================================
    // LE GARDE-FOU DES CLÉS
    // =======================================================================

    /**
     * Une catégorie inconnue est une faute de programmation : elle casse
     * tout de suite plutôt que de produire un message vide en production.
     */
    public function test_an_unknown_category_fails_loudly(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Whatsapp::texte('promotion.soldes');
    }

    /**
     * CHAQUE GABARIT EXISTE DANS LES DEUX LANGUES.
     *
     * Un gabarit ajouté en français seulement laisse l'anglophone recevoir
     * la clé brute — « whatsapp.partage.carte » — sans qu'aucune erreur ne
     * le signale.
     */
    public function test_every_template_exists_in_both_languages(): void
    {
        $fr = require lang_path('fr/messages-whatsapp.php');
        $en = require lang_path('en/messages-whatsapp.php');

        $aplatir = function (array $tableau, string $prefixe = '') use (&$aplatir): array {
            $cles = [];
            foreach ($tableau as $cle => $valeur) {
                $chemin = $prefixe === '' ? (string) $cle : $prefixe.'.'.$cle;
                $cles = array_merge($cles, is_array($valeur) ? $aplatir($valeur, $chemin) : [$chemin]);
            }

            return $cles;
        };

        $this->assertSame($aplatir($fr), $aplatir($en),
            'Les deux fichiers de gabarits ne portent pas les mêmes clés.');
    }

    /**
     * AUCUN LIBELLÉ NU NE DOIT PORTER LE NOM D'UN FICHIER DE LANGUE.
     *
     * ═══════════════════════════════════════════════════════════════════
     * LE PIÈGE, RENCONTRÉ EN VRAI
     * ═══════════════════════════════════════════════════════════════════
     * `__('WhatsApp')` est une traduction « nue » : sans point, Laravel la
     * cherche d'abord comme un FICHIER de langue. Le jour où
     * `lang/fr/whatsapp.php` est apparu, cet appel a cessé de rendre le mot
     * pour rendre le TABLEAU entier des gabarits — et la page d'accueil est
     * tombée sur « htmlspecialchars(): array given ».
     *
     * Le pire tient à la casse. Sur Windows, où le système de fichiers ne
     * la distingue pas, `whatsapp.php` répond à « WhatsApp » ; sur Linux,
     * non. Le même dépôt donne donc une page cassée d'un côté et intacte
     * de l'autre — c'est-à-dire un défaut qu'on ne reproduit pas.
     *
     * La comparaison est faite SANS TENIR COMPTE DE LA CASSE, précisément
     * pour attraper ce que Linux laisserait passer et que Windows casse.
     */
    public function test_no_bare_label_shadows_a_translation_file(): void
    {
        $fichiers = array_map(
            fn ($chemin) => mb_strtolower(pathinfo($chemin, PATHINFO_FILENAME)),
            glob(lang_path('fr/*.php')) ?: [],
        );

        $sources = array_merge(
            glob(resource_path('views/**/*.blade.php')) ?: [],
            glob(resource_path('views/**/**/*.blade.php')) ?: [],
            glob(app_path('**/*.php')) ?: [],
        );

        $collisions = [];

        foreach ($sources as $chemin) {
            $contenu = (string) file_get_contents($chemin);

            if (! preg_match_all("/__\(\s*'([A-Za-z][A-Za-z0-9 _-]*)'/", $contenu, $trouves)) {
                continue;
            }

            foreach ($trouves[1] as $libelle) {
                if (in_array(mb_strtolower($libelle), $fichiers, true)) {
                    $collisions[] = $libelle.'  ('.basename($chemin).')';
                }
            }
        }

        $this->assertSame([], array_values(array_unique($collisions)),
            "Ces libellés portent le nom d'un fichier de langue : Laravel rendra ".
            'le TABLEAU du fichier au lieu du mot. Renommez le fichier, ou '.
            'donnez au libellé une clé avec un point.');
    }

    /** Et chaque catégorie utilisée est déclarée dans la classe. */
    public function test_every_template_category_is_declared(): void
    {
        $categories = array_keys(require lang_path('fr/messages-whatsapp.php'));

        foreach ($categories as $categorie) {
            $this->assertContains($categorie, Whatsapp::CATEGORIES,
                "La catégorie « {$categorie} » a des gabarits mais n'est pas déclarée ".
                'dans Whatsapp::CATEGORIES : aucun appel ne pourra les utiliser.');
        }
    }
}
