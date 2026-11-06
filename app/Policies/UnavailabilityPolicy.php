<?php

namespace App\Policies;

use App\Models\Unavailability;
use App\Models\User;

/**
 * Indisponibilités ponctuelles (BF-18) : chaque médecin déclare et retire les siennes.
 */
class UnavailabilityPolicy
{
    public function create(User $user): bool
    {
        return $user->isDoctor() && $user->doctor !== null;
    }

    public function delete(User $user, Unavailability $unavailability): bool
    {
        return $this->create($user) && $unavailability->doctor_id === $user->doctor->id;
    }
}
