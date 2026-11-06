<?php

namespace App\Policies;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\User;

/**
 * Rendez-vous : gérés par la secrétaire et le médecin ; un patient voit les siens.
 * Les actions suivent les transitions de RG-17 (un état final ne change plus).
 * (Les règles de modification par le patient, RG-16, arrivent avec l'espace patient.)
 */
class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isCareStaff();
    }

    public function view(User $user, Appointment $appointment): bool
    {
        return $user->isCareStaff() || $this->isOwn($user, $appointment);
    }

    public function create(User $user): bool
    {
        return $user->isCareStaff();
    }

    /**
     * Déplacer : seulement un rendez-vous encore actif (En attente ou Confirmé).
     */
    public function update(User $user, Appointment $appointment): bool
    {
        return $user->isCareStaff() && $appointment->isActive();
    }

    /**
     * Confirmer une demande prise en ligne (En attente → Confirmé).
     */
    public function confirm(User $user, Appointment $appointment): bool
    {
        return $user->isCareStaff() && $appointment->status->canTransitionTo(AppointmentStatus::Confirmed);
    }

    /**
     * Annuler libère le créneau (→ Annulé).
     */
    public function cancel(User $user, Appointment $appointment): bool
    {
        return $user->isCareStaff() && $appointment->status->canTransitionTo(AppointmentStatus::Cancelled);
    }

    /**
     * Marquer absent (BF-16) : un rendez-vous confirmé dont l'heure est passée.
     */
    public function markNoShow(User $user, Appointment $appointment): bool
    {
        return $user->isCareStaff()
            && $appointment->status->canTransitionTo(AppointmentStatus::NoShow)
            && $appointment->starts_at->isPast();
    }

    /**
     * RG-22 : un rendez-vous n'est jamais supprimé, il est annulé.
     */
    public function delete(User $user, Appointment $appointment): bool
    {
        return false;
    }

    private function isOwn(User $user, Appointment $appointment): bool
    {
        return $user->isPatient() && $appointment->patient?->user_id === $user->id;
    }
}
