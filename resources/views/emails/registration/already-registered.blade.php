@component('emails.layout', ['title' => 'Vous avez déjà un compte'])
    <h1 style="margin:0 0 12px;font-size:20px;">Vous avez déjà un compte</h1>

    <p style="margin:0 0 16px;line-height:1.5;">
        Une demande d'inscription vient d'être faite avec cette adresse e-mail,
        mais elle est <strong>déjà associée à un compte {{ config('app.name') }}</strong>.
        Aucun nouveau compte n'a été créé.
    </p>

    <p style="margin:0 0 16px;line-height:1.5;">
        Si c'était vous, connectez-vous simplement :
    </p>

    <p style="margin:0 0 20px;" align="center">
        <a href="{{ $loginUrl }}"
           style="display:inline-block;background:#0B5D3B;color:#ffffff;text-decoration:none;padding:14px 26px;border-radius:8px;font-weight:bold;font-size:16px;">
            Me connecter
        </a>
    </p>

    <p style="margin:0 0 8px;font-size:14px;line-height:1.5;">
        Mot de passe oublié ?
        <a href="{{ $resetUrl }}" style="color:#0B5D3B;">Réinitialisez-le ici</a>.
    </p>

    <p style="margin:16px 0 0;font-size:12px;color:#94a3b8;line-height:1.5;">
        Si vous n'êtes pas à l'origine de cette demande, ignorez ce message :
        votre compte reste inchangé.
    </p>
@endcomponent
