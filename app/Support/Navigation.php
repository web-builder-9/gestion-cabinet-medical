<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Menus de chaque rôle (maquettes) : « chaque utilisateur ne voit que le menu
 * de son rôle » (cahier des charges, interfaces à prévoir).
 *
 * Le menu n'est qu'une présentation : la vraie protection reste le middleware
 * de rôle sur les routes et les Policies sur chaque action.
 */
class Navigation
{
    /**
     * Sous-titre affiché sous le logo.
     */
    public static function areaName(User $user): string
    {
        return match ($user->role) {
            Role::Secretary => 'Secrétariat',
            Role::Doctor => 'Espace médecin',
            Role::Admin => 'Administration',
            Role::Patient => 'Espace patient · '.$user->full_name,
        };
    }

    /**
     * Liens du menu principal. Un lien vers une page pas encore développée
     * (route absente) n'est pas affiché.
     *
     * @return list<array{label: string, route: string, icon: string, active: string}>
     */
    public static function items(User $user): array
    {
        $items = match ($user->role) {
            Role::Secretary => [
                ['Tableau de bord', 'dashboard', 'dashboard', 'dashboard'],
                ['Patients', 'patients.index', 'users', 'patients.*'],
                ['Rendez-vous', 'appointments.index', 'calendar', 'appointments.*'],
            ],
            Role::Doctor => [
                ['Mon agenda', 'agenda', 'calendar', 'agenda'],
                ['Mes disponibilités', 'availabilities.index', 'clock', 'availabilities.*'],
                ['Dossiers patients', 'patients.index', 'users', 'patients.*'],
                ['Consultations', 'consultations.index', 'consultation', 'consultations.*'],
            ],
            Role::Admin => [
                ['Utilisateurs', 'admin.users.index', 'users', 'admin.users.*'],
                ['Spécialités et paramètres', 'admin.settings', 'settings', 'admin.settings*'],
            ],
            Role::Patient => [
                ['Prendre rendez-vous', 'booking.create', 'plus', 'booking.*'],
                ['Mes rendez-vous', 'dashboard', 'calendar', 'dashboard'],
            ],
        };

        $items = array_map(fn (array $item) => array_combine(['label', 'route', 'icon', 'active'], $item), $items);

        return array_values(array_filter($items, fn (array $item) => Route::has($item['route'])));
    }
}
