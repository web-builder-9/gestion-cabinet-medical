<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\Specialty;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Création d'un compte du personnel par l'administrateur (RG-03).
 */
class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

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
            'phone' => ['nullable', 'regex:'.PhoneNumber::PATTERN, Rule::unique(User::class)],
            // RG-01 : un seul rôle, parmi ceux du personnel.
            'role' => ['required', Rule::enum(Role::class)->only(Role::staff())],
            // RG-06 : un médecin est rattaché à une spécialité.
            'specialty_id' => ['nullable', 'required_if:role,doctor', Rule::exists(Specialty::class, 'id')],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'specialty_id.required_if' => 'Choisissez la spécialité du médecin.',
            'phone.regex' => 'Le numéro de téléphone doit contenir 8 chiffres (ex. : 22 123 456).',
        ];
    }
}
