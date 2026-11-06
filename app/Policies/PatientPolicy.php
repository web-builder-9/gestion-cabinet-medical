<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

/**
 * Dossier administratif du patient (identité, coordonnées).
 * Laravel associe automatiquement cette Policy au modèle Patient.
 */
class PatientPolicy
{
    /**
     * BF-12 : la secrétaire gère les dossiers ; le médecin les consulte (BF-20).
     */
    public function viewAny(User $user): bool
    {
        return $user->isCareStaff();
    }

    /**
     * Le personnel voit tous les dossiers ; un patient uniquement le sien.
     */
    public function view(User $user, Patient $patient): bool
    {
        return $user->isCareStaff() || $this->isOwn($user, $patient);
    }

    public function create(User $user): bool
    {
        return $user->isCareStaff();
    }

    public function update(User $user, Patient $patient): bool
    {
        return $user->isCareStaff();
    }

    /**
     * Archivage (suppression logique, RG-22) réservé au médecin.
     */
    public function delete(User $user, Patient $patient): bool
    {
        return $user->isDoctor();
    }

    public function viewArchived(User $user): bool
    {
        return $user->isDoctor();
    }

    public function restore(User $user, Patient $patient): bool
    {
        return $user->isDoctor();
    }

    /**
     * RG-22 : aucune suppression physique.
     */
    public function forceDelete(User $user, Patient $patient): bool
    {
        return false;
    }

    private function isOwn(User $user, Patient $patient): bool
    {
        return $user->isPatient() && $patient->user_id === $user->id;
    }
}
