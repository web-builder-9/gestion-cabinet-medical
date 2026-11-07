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
 * Modification d'un compte du personnel par l'administrateur.
 */
class UpdateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->account());
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
        $canChangeRole = $this->user()->can('updateRole', $this->account());
        // Sur son propre compte, le rôle ne change pas : on valide avec le rôle actuel.
        $role = $canChangeRole ? $this->input('role') : $this->account()->role->value;

        return [
            'last_name' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:100'],
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique(User::class)->ignore($this->account()),
            ],
            'phone' => ['nullable', 'regex:'.PhoneNumber::PATTERN, Rule::unique(User::class)->ignore($this->account())],
            'role' => $canChangeRole
                ? ['required', Rule::enum(Role::class)->only(Role::staff())]
                : ['prohibited'],
            'specialty_id' => [
                Rule::requiredIf($role === Role::Doctor->value),
                'nullable',
                Rule::exists(Specialty::class, 'id'),
            ],
            // Laisser vide pour garder le mot de passe actuel.
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'role.prohibited' => 'Vous ne pouvez pas modifier votre propre rôle.',
            'specialty_id.required' => 'Choisissez la spécialité du médecin.',
            'phone.regex' => 'Le numéro de téléphone doit contenir 8 chiffres (ex. : 22 123 456).',
        ];
    }

    /**
     * Le compte modifié, injecté par la route (/personnel/{user}/edit).
     */
    public function account(): User
    {
        return $this->route('user');
    }
}
