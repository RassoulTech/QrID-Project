@component('emails.layout', ['title' => 'Confirmez votre inscription'])
    <h1 style="margin:0 0 12px;font-size:20px;">Bonjour {{ $name }},</h1>

    <p style="margin:0 0 16px;line-height:1.5;">
        Plus qu'une étape pour activer votre présence professionnelle numérique
        et démarrer votre essai gratuit de 15 jours : confirmez votre adresse e-mail.
    </p>

    <p style="margin:0 0 24px;" align="center">
        <a href="{{ $verifyUrl }}"
           style="display:inline-block;background:#0B5D3B;color:#ffffff;text-decoration:none;padding:14px 26px;border-radius:8px;font-weight:bold;font-size:16px;">
            Confirmer mon inscription
        </a>
    </p>

    <p style="margin:0 0 8px;font-size:13px;color:#64748b;line-height:1.5;">
        Ce lien est valable {{ $ttlMinutes }} minutes. Tant que vous ne l'avez pas
        ouvert, aucun compte n'est créé.
    </p>

    <p style="margin:0 0 16px;font-size:13px;color:#64748b;word-break:break-all;">
        Si le bouton ne s'affiche pas, copiez ce lien dans votre navigateur :<br>
        <a href="{{ $verifyUrl }}" style="color:#0B5D3B;">{{ $verifyUrl }}</a>
    </p>

    <p style="margin:0;font-size:12px;color:#94a3b8;line-height:1.5;">
        Si vous n'êtes pas à l'origine de cette demande, ignorez simplement ce message.
    </p>
@endcomponent
