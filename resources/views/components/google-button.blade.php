{{--
  x-google-button — connexion par Google.

      <x-google-button />
      <x-google-button label="S'inscrire avec Google" />

  IL NE S'AFFICHE PAS TANT QUE LES CLÉS MANQUENT. Un bouton qui mène à une
  page d'erreur Google est pire que pas de bouton du tout : l'utilisateur en
  conclut que le service est cassé, non qu'il est en cours de configuration.

  LE LOGO EST INTÉGRÉ EN SVG, jamais chargé depuis un serveur Google. Deux
  raisons : la page ne doit dépendre d'aucun tiers pour s'afficher, et une
  image distante prévient Google de chaque visite sur l'écran de connexion,
  même sans clic. Ce sont les couleurs officielles, seule forme autorisée par
  les règles d'identité de Google.

  C'EST UN LIEN, PAS UN FORMULAIRE : le protocole OAuth commence par une
  navigation en GET. Un bouton de formulaire aurait exigé un jeton CSRF pour
  une requête qui n'écrit rien.
--}}
@props(['label' => 'Continuer avec Google'])

@if (\App\Http\Controllers\Auth\GoogleController::estDisponible())
    <div class="oauth">
        <div class="oauth__ou"><span>ou</span></div>

        <a href="{{ route('auth.google') }}" class="oauth__btn" rel="nofollow">
            <svg class="oauth__logo" viewBox="0 0 18 18" aria-hidden="true">
                <path fill="#4285F4" d="M17.64 9.2c0-.64-.06-1.25-.16-1.84H9v3.48h4.84a4.14 4.14 0 0 1-1.8 2.72v2.26h2.92c1.7-1.57 2.68-3.88 2.68-6.62"/>
                <path fill="#34A853" d="M9 18c2.43 0 4.47-.8 5.96-2.18l-2.92-2.26c-.8.54-1.84.86-3.04.86-2.34 0-4.32-1.58-5.03-3.7H.96v2.33A9 9 0 0 0 9 18"/>
                <path fill="#FBBC05" d="M3.97 10.72a5.4 5.4 0 0 1 0-3.44V4.95H.96a9 9 0 0 0 0 8.1z"/>
                <path fill="#EA4335" d="M9 3.58c1.32 0 2.5.45 3.44 1.35l2.58-2.59C13.46.89 11.43 0 9 0A9 9 0 0 0 .96 4.95l3.01 2.33C4.68 5.16 6.66 3.58 9 3.58"/>
            </svg>

            <span>{{ $label }}</span>
        </a>
    </div>
@endif
