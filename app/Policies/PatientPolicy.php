<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

/**
 * Fiche administrative du patient (identité, coordonnées).
 * Laravel associe automatiquement cette Policy au modèle Patient.
 */
class PatientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Le personnel voit toutes les fiches ; un patient voit uniquement la sienne.
     */
    public function view(User $user, Patient $patient): bool
    {
        return $user->isStaff() || $this->isOwnRecord($user, $patient);
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, Patient $patient): bool
    {
        return $user->isStaff();
    }

    /**
     * Suppression (douce) réservée au médecin.
     */
    public function delete(User $user, Patient $patient): bool
    {
        return $user->isDoctor();
    }

    public function restore(User $user, Patient $patient): bool
    {
        return $user->isDoctor();
    }

    /**
     * Suppression définitive interdite à tous : on garde l'historique médical.
     */
    public function forceDelete(User $user, Patient $patient): bool
    {
        return false;
    }

    private function isOwnRecord(User $user, Patient $patient): bool
    {
        // user()->is() compare les clés sans requête SQL supplémentaire.
        return $user->isPatient() && $patient->user()->is($user);
    }
}
