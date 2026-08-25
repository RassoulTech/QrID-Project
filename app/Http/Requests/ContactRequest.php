<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Le formulaire de contact de la page d'accueil.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LE PIÈGE À ROBOTS N'EST PAS UN CAPTCHA
 * ═══════════════════════════════════════════════════════════════════════
 * Un champ masqué que personne ne voit et qu'aucun humain ne remplit. Les
 * robots, eux, remplissent tout ce qu'ils trouvent : un champ non vide vaut
 * donc rejet.
 *
 * Le choix se défend contre un captcha : celui-ci ferait travailler des
 * clients légitimes — souvent sur un téléphone d'entrée de gamme, avec une
 * connexion lente — pour se protéger d'un problème qui, sur un produit de
 * cette taille, reste marginal. Et il ajouterait une dépendance à Google sur
 * une page qui n'en a aucune.
 *
 * S'il ne suffisait plus, la réponse serait un captcha ; pas avant.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * LE SUJET EST UNE LISTE FERMÉE
 * ═══════════════════════════════════════════════════════════════════════
 * Un champ libre produit « Bonjour » et « Question » cinquante fois, ce qui
 * ne permet ni de trier ni de prioriser. Quatre motifs couvrent ce qu'on
 * reçoit réellement, et le message reste libre.
 */
class ContactRequest extends FormRequest
{
    /**
     * Les motifs proposés — DES CLÉS, PLUS DES LIBELLÉS.
     *
     * La constante portait le texte français, que la vue passait à __(). La
     * phrase française servait donc de clé de traduction : reformuler
     * « Partenariat ou revente » aurait fait disparaître l'anglais sans
     * erreur, sans test rouge, et sans que personne ne le remarque avant de
     * basculer la langue.
     *
     * Les libellés vivent dans lang/*\/landing.php, sous
     * landing.contact.motifs.*. Cette liste garde ce qu'elle doit garder :
     * l'ensemble fermé des valeurs acceptées, et leur ordre d'affichage.
     */
    public const SUJETS = ['information', 'commande', 'assistance', 'partenariat'];

    public function authorize(): bool
    {
        return true;   // formulaire public, ouvert aux visiteurs
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],

            // Facultatif, et c'est voulu : exiger un numéro ferait renoncer
            // ceux qui ne veulent pas être appelés, pour une information dont
            // on n'a pas besoin pour répondre.
            'phone' => ['nullable', 'string', 'max:30'],

            'subject' => ['required', Rule::in(self::SUJETS)],

            /*
             | 20 caractères au minimum. En dessous — « rappelez-moi », « info »
             | — on ne peut rien traiter et il faut réécrire pour demander de
             | quoi il s'agit, ce qui perd tout le monde. La borne haute évite
             | qu'un envoi automatisé remplisse la base.
             */
            'message' => ['required', 'string', 'min:20', 'max:3000'],

            // Le piège : rempli signifie robot. `size:0` refuse toute valeur.
            'site_web' => ['nullable', 'size:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('landing.contact.validation.nom_requis'),
            'name.min' => __('landing.contact.validation.nom_court'),
            'email.required' => __('landing.contact.validation.email_requis'),
            'email.email' => __('landing.contact.validation.email_invalide'),
            'subject.required' => __('landing.contact.validation.motif_requis'),
            'subject.in' => __('landing.contact.validation.motif_inconnu'),
            'message.required' => __('landing.contact.validation.message_requis'),
            'message.min' => __('landing.contact.validation.message_court'),
            'message.max' => __('landing.contact.validation.message_long'),
            'site_web.size' => __('landing.contact.validation.piege'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'name' => trim((string) $this->input('name')),
        ]);
    }
}
