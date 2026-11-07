<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\AccountInvitation;
use Illuminate\Support\Facades\Password;

/**
 * Envoie l'invitation à choisir son mot de passe (lien « Mot de passe oublié »
 * de Laravel, valable 24 heures).
 */
class AccountInvitations
{
    /**
     * Retourne false si le compte n'a pas d'adresse e-mail.
     */
    public function send(User $user): bool
    {
        if ($user->email === null) {
            return false;
        }

        $token = Password::broker()->createToken($user);
        $user->notify(new AccountInvitation($token));

        return true;
    }
}
