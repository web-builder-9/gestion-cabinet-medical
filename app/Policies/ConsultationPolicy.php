<?php

namespace App\Policies;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\User;

/**
 * Consultations : comptes rendus et ordonnances.
 *   - RG-21 : lus par les médecins du cabinet, et en lecture seule par le patient
 *     concerné ; ni la secrétaire ni l'administrateur n'y ont accès.
 *   - RG-19 : une consultation par rendez-vous, enregistrée par son médecin.
 *   - RG-20 : seul le médecin auteur la modifie.
 *   - RG-22 : jamais supprimée.
 */
class ConsultationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isDoctor();
    }

    public function view(User $user, Consultation $consultation): bool
    {
        return $user->isDoctor()
            || ($user->isPatient() && $consultation->appointment->patient?->user_id === $user->id);
    }

    /**
     * Enregistrer la consultation d'un rendez-vous : son médecin, le jour venu,
     * pour un rendez-vous confirmé qui n'a pas encore de compte rendu (RG-17 à RG-19).
     */
    public function create(User $user, Appointment $appointment): bool
    {
        return $user->isDoctor()
            && $appointment->doctor_id === $user->doctor?->id
            && $appointment->status === AppointmentStatus::Confirmed
            && $appointment->starts_at->startOfDay()->lte(now())
            && ! $appointment->consultation()->exists();
    }

    public function update(User $user, Consultation $consultation): bool
    {
        return $user->isDoctor() && $consultation->appointment->doctor_id === $user->doctor?->id;
    }

    public function delete(User $user, Consultation $consultation): bool
    {
        return false;
    }
}
