<?php

namespace App\Enums;

/**
 * Rôle d'un utilisateur (énumération « Role » du diagramme de classes).
 * RG-01 : chaque compte possède un seul rôle.
 */
enum Role: string
{
    case Patient = 'patient';
    case Doctor = 'doctor';
    case Secretary = 'secretary';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Patient => 'Patient',
            self::Doctor => 'Médecin',
            self::Secretary => 'Secrétaire',
            self::Admin => 'Administrateur',
        };
    }

    /**
     * Libellé au pluriel (onglets de la page Utilisateurs).
     */
    public function pluralLabel(): string
    {
        return match ($this) {
            self::Patient => 'Patients',
            self::Doctor => 'Médecins',
            self::Secretary => 'Secrétaires',
            self::Admin => 'Administrateurs',
        };
    }

    /**
     * Rôles du personnel, dont les comptes sont créés par l'administrateur (RG-03).
     *
     * @return list<self>
     */
    public static function staff(): array
    {
        return [self::Doctor, self::Secretary, self::Admin];
    }
}
