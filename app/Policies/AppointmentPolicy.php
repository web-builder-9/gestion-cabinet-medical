<?php

namespace App\Policies;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\User;

/**
 * Rendez-vous : gérés par la secrétaire et le médecin ; un patient voit les siens
 * et peut les modifier ou les annuler en ligne jusqu'au délai fixé (RG-16).
 * Les actions suivent les transitions de RG-17 (un état final ne change plus).
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
     * Déplacer : seulement un rendez-vous encore actif (En attente ou Confirmé) ;
     * pour le patient, le sien et avant le délai (RG-16).
     */
    public function update(User $user, Appointment $appointment): bool
    {
        if ($user->isPatient()) {
            return $this->isOwn($user, $appointment) && $appointment->canBeChangedOnline();
        }

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
     * Annuler libère le créneau (→ Annulé) ; pour le patient, le sien et avant le délai (RG-16).
     */
    public function cancel(User $user, Appointment $appointment): bool
    {
        if ($user->isPatient()) {
            return $this->isOwn($user, $appointment) && $appointment->canBeChangedOnline();
        }

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
