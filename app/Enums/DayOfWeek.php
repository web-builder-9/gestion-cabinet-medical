<?php

namespace App\Enums;

use DateTimeInterface;

/**
 * Jour de la semaine d'une disponibilité (énumération « JourSemaine »).
 * Numérotation ISO-8601 : 1 = lundi … 6 = samedi (le cabinet est fermé le dimanche).
 */
enum DayOfWeek: int
{
    case Monday = 1;
    case Tuesday = 2;
    case Wednesday = 3;
    case Thursday = 4;
    case Friday = 5;
    case Saturday = 6;

    public function label(): string
    {
        return match ($this) {
            self::Monday => 'Lundi',
            self::Tuesday => 'Mardi',
            self::Wednesday => 'Mercredi',
            self::Thursday => 'Jeudi',
            self::Friday => 'Vendredi',
            self::Saturday => 'Samedi',
        };
    }

    /**
     * Jour d'une date, ou null pour un dimanche.
     */
    public static function fromDate(DateTimeInterface $date): ?self
    {
        return self::tryFrom((int) $date->format('N'));
    }
}
