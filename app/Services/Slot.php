<?php

namespace App\Services;

use Carbon\CarbonImmutable;

/**
 * Un créneau de l'agenda d'un médecin, avec son état.
 */
final class Slot
{
    public const FREE = 'free';               // réservable

    public const TAKEN = 'taken';             // déjà un rendez-vous actif (RG-12)

    public const UNAVAILABLE = 'unavailable'; // indisponibilité du médecin

    public const PAST = 'past';               // déjà passé

    public function __construct(
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
        public readonly string $state,
    ) {}

    public function isFree(): bool
    {
        return $this->state === self::FREE;
    }

    /**
     * « 10:30 ».
     */
    public function label(): string
    {
        return $this->start->format('H:i');
    }

    /**
     * Identifiant utilisable dans un formulaire (« 2026-11-05 10:30 »).
     */
    public function key(): string
    {
        return $this->start->format('Y-m-d H:i');
    }
}
