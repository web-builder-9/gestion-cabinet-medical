<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Carbon\CarbonImmutable;

/**
 * Statistiques d'activité d'un mois (BF-27, maquette 17).
 */
class Statistics
{
    /**
     * @return array{appointments: int, completed: int, no_shows: int, cancellations: int, no_show_rate: float}
     */
    public function forMonth(CarbonImmutable $month): array
    {
        // Nombre de rendez-vous du mois par état : ['completed' => 251, 'no_show' => 9, …]
        $counts = Appointment::query()
            ->whereBetween('starts_at', [$month->startOfMonth(), $month->endOfMonth()])
            ->toBase()
            ->pluck('status')
            ->countBy();

        $count = fn (AppointmentStatus $status) => (int) ($counts[$status->value] ?? 0);

        $completed = $count(AppointmentStatus::Completed);
        $noShows = $count(AppointmentStatus::NoShow);
        $seen = $completed + $noShows;

        return [
            'appointments' => (int) $counts->sum(),
            'completed' => $completed,
            'no_shows' => $noShows,
            'cancellations' => $count(AppointmentStatus::Cancelled),
            // Taux d'absence : parmi les rendez-vous qui ont eu lieu (terminés ou absents).
            'no_show_rate' => $seen > 0 ? round(100 * $noShows / $seen, 1) : 0.0,
        ];
    }
}
