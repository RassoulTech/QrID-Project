Bonjour {{ $name }},

@if ($rang === 1)
Votre carte est enregistrée, mais elle n'est pas encore en ligne : son lien ne répond donc à personne. Il ne manque qu'un clic pour la publier.
@else
Votre carte est toujours enregistrée sans être publiée. Si quelque chose vous a arrêté — un champ qui ne convient pas, une photo qui ne passe pas, un doute sur le rendu — répondez simplement à ce message : nous regardons avec vous.
@endif

Publier ne coûte rien pendant votre essai gratuit, et reste réversible : vous pouvez retirer votre carte à tout moment.

Publier ma carte :
{{ $activateUrl }}

C'est notre {{ $rang === 1 ? 'premier' : 'second et dernier' }} rappel à ce sujet.

—
{{ config('app.name') }}
