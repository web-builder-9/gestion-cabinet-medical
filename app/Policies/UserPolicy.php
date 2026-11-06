<?php

namespace App\Policies;

use App\Models\User;

/**
 * Comptes du personnel : gérés par l'administrateur uniquement (RG-03, BF-23, BF-24).
 * Les comptes patients sont créés par le patient lui-même ou par la secrétaire.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $account): bool
    {
        return $user->isAdmin() && ! $account->isPatient();
    }

    /**
     * Personne ne change son propre rôle : le seul administrateur pourrait
     * sinon se retirer ses droits et ne plus pouvoir les récupérer.
     */
    public function updateRole(User $user, User $account): bool
    {
        return $this->update($user, $account) && $user->isNot($account);
    }

    /**
     * Désactiver ou réactiver un compte (RG-02) ; jamais le sien.
     * La règle RG-08 (médecin avec des rendez-vous à venir) est vérifiée
     * par le contrôleur, qui doit expliquer pourquoi c'est refusé.
     */
    public function toggleActive(User $user, User $account): bool
    {
        return $this->update($user, $account) && $user->isNot($account);
    }

    /**
     * RG-22 : aucune suppression de compte ; on le désactive.
     */
    public function delete(User $user, User $account): bool
    {
        return false;
    }
}
