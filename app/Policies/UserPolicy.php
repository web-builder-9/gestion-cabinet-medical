<?php

namespace App\Policies;

use App\Models\User;

/**
 * Gestion des comptes du personnel : réservée au médecin.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isDoctor();
    }

    public function create(User $user): bool
    {
        return $user->isDoctor();
    }

    /**
     * Le médecin modifie les comptes du personnel. Les comptes patients
     * seront gérés avec l'espace patient, pas ici.
     */
    public function update(User $user, User $account): bool
    {
        return $user->isDoctor() && $account->isStaff();
    }

    /**
     * Personne ne change son propre rôle : le seul médecin du cabinet
     * pourrait sinon se retirer ses droits et ne plus pouvoir les récupérer.
     */
    public function updateRole(User $user, User $account): bool
    {
        return $this->update($user, $account) && $user->isNot($account);
    }

    /**
     * Pas de suppression de compte : les rendez-vous et consultations
     * doivent garder leur médecin.
     */
    public function delete(User $user, User $account): bool
    {
        return false;
    }
}
