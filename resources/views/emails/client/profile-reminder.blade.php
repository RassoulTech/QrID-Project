@component('emails.layout', ['title' => 'Votre carte attend d\'être publiée'])
    <h1 style="margin:0 0 12px;font-size:20px;">Bonjour {{ $name }},</h1>

    @if ($rang === 1)
        <p style="margin:0 0 16px;line-height:1.5;">
            Votre carte est enregistrée, mais elle n'est pas encore en ligne :
            son lien ne répond donc à personne. Il ne manque qu'un clic pour la
            publier.
        </p>
    @else
        <p style="margin:0 0 16px;line-height:1.5;">
            Votre carte est toujours enregistrée sans être publiée. Si quelque
            chose vous a arrêté — un champ qui ne convient pas, une photo qui
            ne passe pas, un doute sur le rendu — répondez simplement à ce
            message : nous regardons avec vous.
        </p>
    @endif

    <p style="margin:0 0 16px;line-height:1.5;">
        Publier ne coûte rien pendant votre essai gratuit, et reste réversible :
        vous pouvez retirer votre carte à tout moment.
    </p>

    @include('emails.partials.bouton', ['url' => $activateUrl, 'libelle' => 'Publier ma carte'])

    @include('emails.partials.lien-brut', ['url' => $activateUrl])

    <p style="margin:0;font-size:12px;color:#94a3b8;line-height:1.5;">
        C'est notre {{ $rang === 1 ? 'premier' : 'second et dernier' }} rappel à ce sujet.
    </p>
@endcomponent
