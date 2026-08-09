<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Pages légales — OBLIGATOIRES avant toute vente.
 *
 * Le contenu ci-dessous est une trame minimale. Il DOIT être relu et complété
 * par un juriste avant l'ouverture commerciale : la vente d'un abonnement au
 * Sénégal impose des mentions précises (identité du vendeur, prix TTC, droit
 * de rétractation, traitement des données personnelles).
 */
class LegalController extends Controller
{
    private const UPDATED_AT = '28 juillet 2026';

    public function conditions(): View
    {
        return view('legal.page', [
            'title' => 'Conditions générales',
            'updatedAt' => self::UPDATED_AT,
            'blocks' => [
                ['heading' => 'Objet',
                    'text' => "Les présentes conditions régissent l'utilisation de la plateforme d'identité professionnelle numérique ".config('app.name').', éditée depuis Thiès, Sénégal.'],
                ['heading' => 'Accès au service',
                    'text' => "L'accès nécessite la création d'un compte avec une adresse e-mail valide. Chaque compte est personnel et sa confidentialité relève de son titulaire."],
                ['heading' => 'Abonnement et paiement',
                    'text' => "Le service est proposé après une période d'essai gratuite de 15 jours. Les montants sont exprimés en francs CFA et réglés par Wave, Orange Money ou Free Money. L'abonnement est sans engagement de durée."],
                ['heading' => 'Résiliation',
                    'text' => "L'abonnement peut être interrompu à tout moment depuis l'espace client. Le profil reste accessible jusqu'au terme de la période réglée."],
                ['heading' => 'Responsabilité',
                    'text' => "L'utilisateur est seul responsable des informations publiées sur son profil et garantit en détenir les droits."],
                ['heading' => 'À compléter',
                    'text' => 'Ce document est une trame. Faites-le relire par un juriste avant la mise en vente.'],
            ],
        ]);
    }

    public function confidentialite(): View
    {
        return view('legal.page', [
            'title' => 'Politique de confidentialité',
            'updatedAt' => self::UPDATED_AT,
            'blocks' => [
                ['heading' => 'Données collectées',
                    'text' => 'Compte : nom, adresse e-mail, numéro de téléphone. Profil : les informations professionnelles que vous choisissez de publier.'],
                ['heading' => 'Finalité',
                    'text' => 'Ces données servent à faire fonctionner votre profil public, à gérer votre abonnement et à vous contacter au sujet du service.'],
                ['heading' => 'Statistiques de consultation',
                    'text' => 'Les consultations de votre profil sont comptabilisées. Les adresses IP ne sont jamais conservées en clair : seule une empreinte irréversible est stockée.'],
                ['heading' => 'Conservation',
                    'text' => 'Vos données sont conservées tant que votre compte existe. Les paiements sont conservés au-delà, en tant que pièces comptables.'],
                ['heading' => 'Vos droits',
                    'text' => 'Vous pouvez consulter, corriger ou supprimer vos données depuis votre espace client, ou en écrivant à '.config('mail.from.address').'.'],
                ['heading' => 'À compléter',
                    'text' => 'Ce document est une trame. Faites-le relire par un juriste avant la mise en vente.'],
            ],
        ]);
    }

    public function mentions(): View
    {
        return view('legal.page', [
            'title' => 'Mentions légales',
            'updatedAt' => self::UPDATED_AT,
            'blocks' => [
                ['heading' => 'Éditeur',
                    'text' => config('app.name').' — Thiès, Sénégal. Raison sociale, forme juridique et numéro d\'identification à compléter.'],
                ['heading' => 'Contact',
                    'text' => config('mail.from.address').' — support disponible sur WhatsApp.'],
                ['heading' => 'Hébergement',
                    'text' => "Nom et adresse de l'hébergeur à compléter avant la mise en ligne."],
                ['heading' => 'Propriété intellectuelle',
                    'text' => "L'ensemble des éléments de la plateforme est protégé. Les contenus publiés par les utilisateurs restent leur propriété."],
                ['heading' => 'À compléter',
                    'text' => 'Ce document est une trame. Faites-le relire par un juriste avant la mise en vente.'],
            ],
        ]);
    }
}
