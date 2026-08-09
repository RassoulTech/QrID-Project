@component('emails.layout', ['title' => 'Réinitialisation de votre mot de passe'])
    <h1 style="margin:0 0 12px;font-size:20px;">Réinitialisation de mot de passe</h1>

    <p style="margin:0 0 16px;line-height:1.5;">
        Vous avez demandé la réinitialisation du mot de passe de votre compte
        {{ config('app.name') }}. Cliquez sur le bouton ci-dessous pour en choisir un nouveau.
    </p>

    <p style="margin:0 0 24px;" align="center">
        <a href="{{ $resetUrl }}"
           style="display:inline-block;background:#0B5D3B;color:#ffffff;text-decoration:none;padding:14px 26px;border-radius:8px;font-weight:bold;font-size:16px;">
            Choisir un nouveau mot de passe
        </a>
    </p>

    <p style="margin:0 0 8px;font-size:13px;color:#64748b;line-height:1.5;">
        Ce lien est valable {{ $ttlMinutes }} minutes.
    </p>

    <p style="margin:0 0 16px;font-size:13px;color:#64748b;word-break:break-all;">
        Si le bouton ne s'affiche pas, copiez ce lien dans votre navigateur :<br>
        <a href="{{ $resetUrl }}" style="color:#0B5D3B;">{{ $resetUrl }}</a>
    </p>

    <p style="margin:0;font-size:12px;color:#94a3b8;line-height:1.5;">
        Si vous n'êtes pas à l'origine de cette demande, ignorez ce message :
        votre mot de passe restera inchangé.
    </p>
@endcomponent
