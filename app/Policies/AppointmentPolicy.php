<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

/**
 * Rendez-vous : gérés par le médecin et la secrétaire.
 * Un patient pourra consulter les siens (espace patient, plus tard).
 */
class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        $patient = $appointment->patient;

        return $user->isPatient() && $patient !== null && $patient->user()->is($user);
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $user->isStaff();
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->isStaff();
    }
}
