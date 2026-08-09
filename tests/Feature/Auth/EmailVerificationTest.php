<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

/**
 * Les routes de vérification d'e-mail de Breeze (verification.notice,
 * verification.verify, verification.send) ont été retirées : dans ce produit,
 * l'adresse est prouvée AVANT la création du compte (double opt-in).
 *
 * Le parcours réel est couvert par Tests\Feature\Auth\RegistrationTest
 * et Tests\Feature\RegistrationTest.
 *
 * Ce fichier est conservé vide (plutôt que supprimé) pour documenter le choix ;
 * il pourra être réactivé si le changement d'adresse e-mail est ajouté un jour.
 */
class EmailVerificationTest extends TestCase
{
    public function test_email_verification_is_handled_by_the_double_optin_flow(): void
    {
        $this->assertTrue(true);
    }
}
