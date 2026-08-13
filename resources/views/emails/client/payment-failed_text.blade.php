Bonjour {{ $name }},

AUCUNE SOMME N'A ÉTÉ PRÉLEVÉE. Votre paiement de {{ $montant }} FCFA pour la formule {{ $formule }} n'est pas allé à son terme, et votre abonnement n'a pas été modifié.

Cela arrive le plus souvent pour une raison simple : solde insuffisant au moment de l'opération, code de confirmation non saisi à temps, ou page fermée avant la fin. Vous pouvez réessayer immédiatement.

Réessayer le paiement :
{{ $retryUrl }}

Si une somme apparaissait malgré tout sur votre compte, répondez à ce message : nous la retrouvons et nous la traitons.

—
{{ config('app.name') }}
