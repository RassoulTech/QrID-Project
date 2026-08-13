Bonjour {{ $name }},

Le mot de passe de votre compte a été modifié le {{ $date }}.

SI C'EST BIEN VOUS, il n'y a rien à faire : ce message est une simple confirmation.

SI CE N'EST PAS VOUS, votre compte est en danger. Demandez immédiatement un nouveau mot de passe pour reprendre la main :
{{ $resetUrl }}
@if ($ip)

Adresse IP à l'origine de la modification : {{ $ip }}
@endif

Ce message de sécurité est envoyé à chaque changement de mot de passe et ne peut pas être désactivé.

—
{{ config('app.name') }}
