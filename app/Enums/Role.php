<?php

namespace App\Enums;

/**
 * Rôle d'un utilisateur. Stocké en texte dans users.role.
 */
enum Role: string
{
    case Doctor = 'doctor';
    case Secretary = 'secretary';
    case Patient = 'patient';

    public function label(): string
    {
        return match ($this) {
            self::Doctor => 'Médecin',
            self::Secretary => 'Secrétaire',
            self::Patient => 'Patient',
        };
    }
}
