<?php

namespace App\Policies;

use App\Models\Setting;
use App\Models\User;

/**
 * Paramètres du cabinet et statistiques : administrateur (BF-26, BF-27).
 */
class SettingPolicy
{
    public function update(User $user, Setting $setting): bool
    {
        return $user->isAdmin();
    }
}
