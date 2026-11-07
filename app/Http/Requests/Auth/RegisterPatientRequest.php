<?php

namespace App\Http\Requests\Auth;

use App\Enums\Gender;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Inscription d'un patient (BF-03, maquette 2).
 * RG-02 : e-mail unique ; RG-04 : mot de passe ; RG-05 : téléphone unique.
 */
class RegisterPatientRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('phone')) {
            $this->merge(['phone' => PhoneNumber::format($this->input('phone'))]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'last_name' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'phone' => ['required', 'regex:'.PhoneNumber::PATTERN, Rule::unique(User::class)],
            'birth_date' => ['required', 'date', 'before_or_equal:today', 'after:1900-01-01'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'password' => ['required', 'confirmed', Password::defaults()],
            // Loi organique n° 2004-63 : accord du patient.
            'data_consent' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Cette adresse e-mail a déjà un compte. Connectez-vous ou réinitialisez votre mot de passe.',
            // Le secrétariat a déjà créé un dossier avec ce numéro : il peut envoyer l'accès en ligne.
            'phone.unique' => 'Ce numéro correspond déjà à un dossier du cabinet : demandez au secrétariat de vous envoyer l\'accès à votre espace patient.',
            'phone.regex' => 'Le numéro de téléphone doit contenir 8 chiffres (ex. : 22 123 456).',
            'password.letters' => '8 caractères minimum, dont au moins une lettre et un chiffre.',
            'password.numbers' => '8 caractères minimum, dont au moins une lettre et un chiffre.',
            'password.min' => '8 caractères minimum, dont au moins une lettre et un chiffre.',
            'birth_date.before_or_equal' => 'La date de naissance ne peut pas être dans le futur.',
            'data_consent.accepted' => 'Votre accord pour le traitement de vos données est nécessaire pour créer un compte.',
        ];
    }
}
