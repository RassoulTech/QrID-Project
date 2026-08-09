<?php

namespace App\Http\Controllers\Auth;

use App\Enums\RegistrationOutcome;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\RegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Affiche le formulaire. Génère un jeton d'idempotence stocké en session
     * et injecté en champ caché (protection anti-double-soumission serveur).
     */
    public function create(Request $request): View
    {
        $token = (string) Str::uuid();
        $request->session()->put('registration.idem', $token);

        return view('auth.register', [
            'prefillEmail' => $request->query('email'),
            'idempotencyToken' => $token,
        ]);
    }

    /**
     * Valide, délègue au service, redirige. AUCUN branchement sur le cas
     * rencontré : la réponse est identique quel que soit l'état de l'adresse.
     */
    public function store(RegisterRequest $request, RegistrationService $service): RedirectResponse
    {
        $data = $request->validated();

        // --- Idempotence : une seconde soumission ne recrée rien.
        $submitted = (string) $request->input('_idem');
        $active = (string) $request->session()->get('registration.idem');

        if ($submitted === '' || $active === '' || ! hash_equals($active, $submitted)) {
            if ($request->session()->has('registration.pending_email')) {
                return redirect()->route('registration.pending');
            }

            return redirect()->route('register');
        }

        $request->session()->forget('registration.idem');

        // --- Rate limiting (IP et adresse).
        $ipKey = 'register-ip:'.hash('sha256', (string) $request->ip());
        $emailKey = 'register-email:'.hash('sha256', $data['email']);

        $limited = RateLimiter::tooManyAttempts($ipKey, config('registration.rate_per_ip'))
            || RateLimiter::tooManyAttempts($emailKey, config('registration.rate_per_email'));

        if ($limited) {
            // Même écran, aucun e-mail : indiscernable des autres cas.
            Log::channel('mail')->info('Demande d\'inscription traitée', [
                'email' => $data['email'],
                'outcome' => RegistrationOutcome::RateLimited->value,
                'detail' => RegistrationOutcome::RateLimited->label(),
            ]);
        } else {
            RateLimiter::hit($ipKey, 3600);
            RateLimiter::hit($emailKey, 3600);

            $service->handle($data, $request->canonicalPhone(), $request->ip());
        }

        $request->session()->put('registration.pending_email', $data['email']);

        // Compteur d'affichage de la page de confirmation. Posé identiquement
        // dans TOUS les cas, y compris ceux où aucun e-mail n'est parti : la
        // page ne doit rien révéler de l'état de l'adresse.
        $request->session()->put('registration.resend_count', 0);
        $request->session()->put('registration.last_sent_at', now()->getTimestamp());

        return redirect()->route('registration.pending');
    }
}
