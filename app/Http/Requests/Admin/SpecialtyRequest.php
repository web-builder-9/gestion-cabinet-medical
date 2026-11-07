<?php

namespace App\Http\Requests\Admin;

use App\Models\Specialty;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Ajout ou modification d'une spécialité (BF-25). Le libellé est unique.
 */
class SpecialtyRequest extends FormRequest
{
    /**
     * Erreurs rangées à part : la page contient aussi le formulaire des paramètres.
     */
    protected $errorBag = 'specialty';

    public function authorize(): bool
    {
        $specialty = $this->route('specialty');

        return $specialty instanceof Specialty
            ? $this->user()->can('update', $specialty)
            : $this->user()->can('create', Specialty::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique(Specialty::class)->ignore($this->route('specialty'))],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['name' => 'spécialité'];
    }
}
