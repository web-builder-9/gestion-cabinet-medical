<?php

namespace App\Http\Requests;

use App\Enums\BloodType;
use App\Models\MedicalRecord;
use App\Models\Patient;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Mise à jour du dossier médical d'un patient (médecin uniquement).
 */
class UpdateMedicalRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->record());
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'blood_type' => ['nullable', Rule::enum(BloodType::class)],
            'allergies' => ['nullable', 'string', 'max:2000'],
            'medical_history' => ['nullable', 'string', 'max:5000'],
            'current_treatments' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Le dossier du patient de l'URL. Chaque patient en a un (créé avec la
     * fiche) ; par sécurité, on en prépare un nouveau s'il manquait.
     */
    public function record(): MedicalRecord
    {
        /** @var Patient $patient */
        $patient = $this->route('patient');

        return $patient->medicalRecord ?? $patient->medicalRecord()->make();
    }
}
