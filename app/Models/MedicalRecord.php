<?php

namespace App\Models;

use App\Enums\BloodType;
use Database\Factories\MedicalRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dossier médical d'un patient (1-1). Données sensibles : accès médecin uniquement.
 */
#[Fillable(['blood_type', 'allergies', 'medical_history', 'current_treatments'])]
class MedicalRecord extends Model
{
    /** @use HasFactory<MedicalRecordFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'blood_type' => BloodType::class,
        ];
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
