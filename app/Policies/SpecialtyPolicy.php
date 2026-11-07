<?php

namespace App\Policies;

use App\Models\Specialty;
use App\Models\User;

/**
 * Spécialités : gérées par l'administrateur (BF-25).
 */
class SpecialtyPolicy
{
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Specialty $specialty): bool
    {
        return $user->isAdmin();
    }

    /**
     * RG-07 : une spécialité attribuée à au moins un médecin ne peut pas être supprimée.
     */
    public function delete(User $user, Specialty $specialty): bool
    {
        return $user->isAdmin() && $specialty->isDeletable();
    }
}
