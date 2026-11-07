<?php

namespace App\Policies;

use App\Models\MedicalRecord;
use App\Models\User;

/**
 * Dossier médical : données de santé, réservées aux médecins du cabinet (RG-21).
 * Ni la secrétaire, ni l'administrateur n'y ont accès.
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
