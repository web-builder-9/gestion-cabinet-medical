<?php

namespace App\Exceptions;

use App\Enums\AppointmentStatus;
use RuntimeException;

/**
 * Levée quand un changement d'état ne respecte pas RG-17
 * (ex. : rouvrir un rendez-vous terminé).
 */
class InvalidStatusTransition extends RuntimeException
{
    public function __construct(
        public readonly AppointmentStatus $from,
        public readonly AppointmentStatus $to,
    ) {
        parent::__construct("Un rendez-vous « {$from->label()} » ne peut pas passer à « {$to->label()} ».");
    }
}
