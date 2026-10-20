<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use App\Models\Patient;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation de la fiche patient, pour l'ajout comme pour la modification.
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
     * Avant la validation, on remet le téléphone au format « 22 123 456 »
     * (espaces, tirets et indicatif +216 acceptés à la saisie).
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('phone')) {
            $this->merge(['phone' => Patient::formatPhone($this->input('phone'))]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            // Unique y compris parmi les patients archivés (suppression douce).
            'cin' => ['nullable', 'digits:8', Rule::unique(Patient::class)->ignore($this->patient())],
            'birth_date' => ['required', 'date', 'before_or_equal:today', 'after:1900-01-01'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'phone' => ['required', 'string', 'regex:/^\d{2} \d{3} \d{3}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
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
        ];
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
