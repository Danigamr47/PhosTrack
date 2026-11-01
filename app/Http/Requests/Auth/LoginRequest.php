<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\Connexion;
use App\Models\User;
use App\Models\JournalActivite;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'matricule' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'matricule.required' => 'Le matricule est obligatoire.',
            'password.required' => 'Le mot de passe est obligatoire.',
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

        $identifiants = [
    'matricule' => trim($this->input('matricule')),
    'password' => $this->input('password'),
    'actif' => true,
    ];

    if (! Auth::attempt($identifiants, $this->boolean('remember'))) {
    RateLimiter::hit($this->throttleKey());

    $existant = User::where('matricule', $identifiants['matricule'])->first();
    if ($existant) {
        JournalActivite::enregistrerAction($existant, 'Échec de connexion', 'Utilisateur', $existant->id);
    }

    throw ValidationException::withMessages([
        'matricule' => 'Matricule ou mot de passe incorrect, ou compte désactivé.',
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
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $secondes = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'matricule' => "Trop de tentatives. Réessayez dans {$secondes} secondes.",
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
     public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->input('matricule')).'|'.$this->ip());
    }
}
