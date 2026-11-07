<?php

namespace App\Policies;

use App\Models\Availability;
use App\Models\User;

/**
 * Disponibilités (BF-17) : chaque médecin gère les siennes (RG-09).
 */
class AvailabilityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isDoctor() && $user->doctor !== null;
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Availability $availability): bool
    {
        return $this->viewAny($user) && $availability->doctor_id === $user->doctor->id;
    }

    public function delete(User $user, Availability $availability): bool
    {
        return $this->update($user, $availability);
    }
}
