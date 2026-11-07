<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use App\Models\Patient;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation du dossier patient (BF-12), pour l'ajout comme pour la modification.
 * Nom, prénom, téléphone et e-mail vont dans users ; le reste dans patients.
 */
class PatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        $patient = $this->patient();

        return $patient
            ? $this->user()->can('update', $patient)
            : $this->user()->can('create', Patient::class);
    }

    /**
     * Avant la validation, le téléphone est remis au format « 22 123 456 »
     * (espaces, tirets et indicatif +216 acceptés à la saisie).
     */
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
        $account = $this->patient()?->user;

        return [
            'last_name' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date', 'before_or_equal:today', 'after:1900-01-01'],
            'gender' => ['required', Rule::enum(Gender::class)],
            // RG-05 : le téléphone identifie le patient, il est unique.
            'phone' => ['required', 'regex:'.PhoneNumber::PATTERN, Rule::unique(User::class)->ignore($account)],
            // L'accès à l'espace patient passe par un e-mail d'invitation (maquette 9).
            'email' => ['nullable', 'required_if_accepted:portal_access', 'email', 'max:255', Rule::unique(User::class)->ignore($account)],
            // Unique y compris parmi les patients archivés.
            'cin' => ['nullable', 'digits:8', Rule::unique(Patient::class)->ignore($this->patient())],
            'address' => ['nullable', 'string', 'max:255'],
            // Loi organique n° 2004-63 : accord du patient, recueilli à la création.
            'data_consent' => $this->patient() ? ['nullable'] : ['accepted'],
            'portal_access' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'birth_date.before_or_equal' => 'La date de naissance ne peut pas être dans le futur.',
            'birth_date.after' => 'La date de naissance doit être postérieure au 1er janvier 1900.',
            'phone.regex' => 'Le numéro de téléphone doit contenir 8 chiffres (ex. : 22 123 456).',
            'phone.unique' => 'Ce numéro appartient déjà à un autre patient : vérifiez qu\'il n\'a pas déjà un dossier.',
            'data_consent.accepted' => 'L\'accord du patient pour le traitement de ses données est obligatoire.',
            'email.required_if_accepted' => 'L\'adresse e-mail est nécessaire pour créer l\'accès à l\'espace patient.',
        ];
    }

    /**
     * Champs enregistrés dans le compte (users).
     *
     * @return array<string, mixed>
     */
    public function accountData(): array
    {
        return $this->safe()->only(['last_name', 'first_name', 'phone', 'email']);
    }

    /**
     * Champs enregistrés dans le dossier (patients).
     *
     * @return array<string, mixed>
     */
    public function patientData(): array
    {
        return $this->safe()->only(['birth_date', 'gender', 'cin', 'address']);
    }

    /**
     * Le patient modifié, ou null lors d'un ajout.
     */
    public function patient(): ?Patient
    {
        $patient = $this->route('patient');

        return $patient instanceof Patient ? $patient : null;
    }
}
