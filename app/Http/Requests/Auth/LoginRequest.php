<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            /*
             | Un échec de connexion ne laissait AUCUNE trace côté serveur : face
             | à un utilisateur qui « n'arrive pas à se connecter », il n'y avait
             | rien à lire, et il fallait deviner entre adresse inconnue, mot de
             | passe erroné, compte non confirmé et jeton CSRF perdu.
             |
             | On journalise donc la cause réelle. Le mot de passe n'apparaît
             | jamais, même partiellement ; on note seulement s'il existe un
             | compte pour cette adresse, ce que le journal serveur a le droit de
             | savoir — l'ÉCRAN, lui, reste indifférencié.
             */
            $existe = User::where('email', (string) $this->input('email'))->exists();

            Log::warning('Connexion refusée.', [
                'email' => $this->input('email'),
                'cause' => $existe
                    ? 'mot de passe incorrect'
                    : 'aucun compte pour cette adresse',
                'ip' => $this->ip(),
                'tentatives' => RateLimiter::attempts($this->throttleKey()),
                'at' => now()->toDateTimeString(),
            ]);

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        /*
         | Compte suspendu — contrôlé APRÈS la vérification du mot de passe.
         |
         | L'ordre compte : contrôler avant reviendrait à révéler qu'une
         | adresse existe et qu'elle est suspendue à quiconque la saisit.
         | Ici, seul le propriétaire du mot de passe obtient l'information.
         |
         | La session est détruite dans la foulée : aucune trace d'un compte
         | suspendu ne subsiste côté serveur.
         */
        if (Auth::user()->isBlocked()) {
            Log::warning('Connexion refusée : compte suspendu.', [
                'user_id' => Auth::id(),
                'ip' => $this->ip(),
                'at' => now()->toDateTimeString(),
            ]);

            Auth::guard('web')->logout();
            $this->session()->invalidate();
            $this->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => trans('auth.blocked'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), config('auth.login_attempts', 5))) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
