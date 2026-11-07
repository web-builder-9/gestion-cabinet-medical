<?php

namespace App\Enums;

/**
 * État d'un rendez-vous (énumération « EtatRendezVous », diagramme d'états).
 */
enum AppointmentStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Confirmed => 'Confirmé',
            self::Cancelled => 'Annulé',
            self::Completed => 'Terminé',
            self::NoShow => 'Absent',
        };
    }

    /**
     * RG-12 : seuls « En attente » et « Confirmé » occupent un créneau.
     */
    public function blocksSlot(): bool
    {
        return $this === self::Pending || $this === self::Confirmed;
    }

    /**
     * RG-17 : Terminé, Annulé et Absent sont des états finaux.
     */
    public function isFinal(): bool
    {
        return ! $this->blocksSlot();
    }

    /**
     * RG-17 : transitions autorisées depuis cet état.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Pending, self::Cancelled, self::Completed, self::NoShow],
            self::Cancelled, self::Completed, self::NoShow => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    /**
     * Valeurs des états qui occupent un créneau (pour les requêtes).
     *
     * @return list<string>
     */
    public static function activeValues(): array
    {
        return [self::Pending->value, self::Confirmed->value];
    }
}
