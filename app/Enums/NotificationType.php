<?php

namespace App\Enums;

/**
 * Type d'e-mail envoyé au patient (énumération « TypeNotification »).
 */
enum NotificationType: string
{
    case Confirmation = 'confirmation';
    case Modification = 'modification';
    case Cancellation = 'cancellation';
    case Reminder = 'reminder';

    public function label(): string
    {
        return match ($this) {
            self::Confirmation => 'Confirmation',
            self::Modification => 'Modification',
            self::Cancellation => 'Annulation',
            self::Reminder => 'Rappel',
        };
    }
}
