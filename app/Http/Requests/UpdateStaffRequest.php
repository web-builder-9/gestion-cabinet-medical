<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Modification d'un compte du personnel par le médecin.
 */
class UpdateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->account());
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique(User::class)->ignore($this->account()),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            // Le rôle est obligatoire… sauf sur son propre compte, où il est interdit.
            'role' => $this->user()->can('updateRole', $this->account())
                ? ['required', Rule::enum(Role::class)->only([Role::Doctor, Role::Secretary])]
                : ['prohibited'],
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
