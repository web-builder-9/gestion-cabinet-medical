<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Scheduled = 'scheduled';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Prévu',
            self::Confirmed => 'Confirmé',
            self::Cancelled => 'Annulé',
            self::Completed => 'Terminé',
        };
    }

    /**
     * Un rendez-vous annulé libère le créneau : il ne compte pas
     * dans la détection des conflits d'horaire.
     */
    public function blocksSlot(): bool
    {
        return $this !== self::Cancelled;
    }

    /**
     * Valeurs des statuts qui occupent un créneau (utilisé dans les requêtes).
     *
     * @return list<string>
     */
    public static function blockingValues(): array
    {
        $blocking = array_filter(self::cases(), fn (self $status) => $status->blocksSlot());

        return array_values(array_map(fn (self $status) => $status->value, $blocking));
    }
}
