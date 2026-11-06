<?php

namespace App\Http\Requests\Doctor;

use App\Models\Unavailability;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Déclaration d'une indisponibilité ponctuelle : congé, formation… (BF-18).
 */
class UnavailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Unavailability::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Format des champs « date et heure » du navigateur : 2026-11-16T08:00
            'starts_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'ends_at' => ['required', 'date_format:Y-m-d\TH:i', 'after:starts_at', 'after:now'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ends_at.after' => 'La fin doit être après le début, et dans le futur.',
        ];
    }
}
