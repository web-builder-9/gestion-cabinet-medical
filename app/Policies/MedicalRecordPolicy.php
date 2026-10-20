<?php

namespace App\Policies;

use App\Models\MedicalRecord;
use App\Models\User;

/**
 * Dossier médical : données de santé, accessibles au médecin uniquement
 * (ni la secrétaire, ni le patient via l'application).
 */
class MedicalRecordPolicy
{
    public function view(User $user, MedicalRecord $medicalRecord): bool
    {
        return $user->isDoctor();
    }

    public function create(User $user): bool
    {
        return $user->isDoctor();
    }

    public function update(User $user, MedicalRecord $medicalRecord): bool
    {
        return $user->isDoctor();
    }
}
