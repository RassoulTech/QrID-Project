<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * LA SUITE SIMULE UN NAVIGATEUR FRANCOPHONE, ET ELLE LE DIT.
     *
     * ═══════════════════════════════════════════════════════════════════
     * SYMFONY POSE « en-us » PAR DÉFAUT, SANS QUE PERSONNE NE L'AIT CHOISI
     * ═══════════════════════════════════════════════════════════════════
     * `Symfony\Component\HttpFoundation\Request::create()` — que tout appel
     * $this->get() finit par emprunter — remplit son tableau $server avec des
     * valeurs par défaut, dont :
     *
     *     'HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5'
     *
     * Tant que la négociation de langue n'existait pas, cet en-tête ne servait
     * à rien et personne ne l'a jamais vu. Du jour où l'application écoute
     * Accept-Language, il devient déterminant : la suite entière se met à
     * demander l'anglais.
     *
     * Les 650 tests de ce dépôt vérifient des messages FRANÇAIS — « Carte
     * publiée », « E-mail ou mot de passe incorrect ». Ils tomberaient tous
     * ensemble, non pas parce que le produit est cassé, mais parce qu'un
     * réglage par défaut d'une bibliothèque tierce aurait décidé à leur place.
     *
     * On pose donc l'en-tête explicitement. Un test qui veut éprouver la
     * négociation le remplace lui-même par withHeader('Accept-Language', …) :
     * la valeur posée ici est un défaut, pas un verrou.
     *
     * `APP_LOCALE=fr` dans phpunit.xml ne suffit pas : le middleware SetLocale
     * écrase la locale de configuration à chaque requête. C'est bien l'en-tête
     * qu'il faut corriger, pas la configuration.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('Accept-Language', 'fr-FR,fr;q=0.9');
    }
}
