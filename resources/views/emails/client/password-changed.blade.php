@component('emails.layout', ['title' => 'Votre mot de passe a été modifié'])
    <h1 style="margin:0 0 12px;font-size:20px;">Bonjour {{ $name }},</h1>

    <p style="margin:0 0 20px;line-height:1.5;">
        Le mot de passe de votre compte a été modifié le <strong>{{ $date }}</strong>.
    </p>

    <p style="margin:0 0 20px;line-height:1.5;">
        <strong>Si c'est bien vous</strong>, il n'y a rien à faire : ce message
        est une simple confirmation.
    </p>

    <p style="margin:0 0 8px;line-height:1.5;">
        <strong>Si ce n'est pas vous</strong>, votre compte est en danger.
        Demandez immédiatement un nouveau mot de passe pour reprendre la main :
    </p>

    @include('emails.partials.bouton', [
        'url' => $resetUrl,
        'libelle' => 'Reprendre le contrôle de mon compte',
        'ton' => 'sombre',
    ])

    @include('emails.partials.lien-brut', ['url' => $resetUrl])

    @if ($ip)
        <p style="margin:0 0 12px;font-size:12px;color:#64748b;">
            Adresse IP à l'origine de la modification : {{ $ip }}
        </p>
    @endif

    <p style="margin:0;font-size:12px;color:#94a3b8;line-height:1.5;">
        Ce message de sécurité est envoyé à chaque changement de mot de passe et
        ne peut pas être désactivé.
    </p>
@endcomponent
