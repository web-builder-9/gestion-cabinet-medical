<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\DayOfWeek;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Setting;
use Carbon\CarbonImmutable;

/**
 * Agenda d'un médecin par jour ou par semaine (maquette 11, BF-19) : position
 * de chaque rendez-vous et des périodes sans consultation sur une grille horaire.
 *
 * Les positions sont en pixels : top = minutes depuis le début de la grille × PX_PER_MINUTE.
 */
class WeekAgenda
{
    /** Hauteur d'une minute sur la grille : un créneau de 30 min fait 48 px. */
    public const PX_PER_MINUTE = 1.6;

    /**
     * @param  list<CarbonImmutable>  $days
     * @return array{hours: list<string>, height: float, count: int, columns: list<array<string, mixed>>}
     */
    public function for(Doctor $doctor, array $days): array
    {
        $settings = Setting::current();

        // La grille couvre les horaires du cabinet (samedi compris).
        $gridStart = min(array_filter([$settings->time('opening_time'), $settings->time('saturday_opening_time')]));
        $gridEnd = max(array_filter([$settings->time('closing_time'), $settings->time('saturday_closing_time')]));

        $from = $days[0]->startOfDay();
        $to = end($days)->endOfDay();

        $availabilities = $doctor->availabilities()->orderBy('start_time')->get()->groupBy(fn ($a) => $a->day_of_week->value);
        $unavailabilities = $doctor->unavailabilities()->where('starts_at', '<', $to)->where('ends_at', '>', $from)->get();
        $appointments = $doctor->appointments()->with('patient.user')->during($from, $to)->orderBy('starts_at')->get();

        $columns = [];

        foreach ($days as $day) {
            $top = fn (CarbonImmutable $time) => $this->minutes($day, $gridStart, $time) * self::PX_PER_MINUTE;
            $at = fn (string $time) => CarbonImmutable::parse($day->format('Y-m-d').' '.$time);
            $weekday = DayOfWeek::fromDate($day);

            [$opening, $closing] = $weekday === DayOfWeek::Saturday
                ? [$settings->time('saturday_opening_time'), $settings->time('saturday_closing_time')]
                : [$settings->time('opening_time'), $settings->time('closing_time')];
            $closed = $weekday === null || $opening === null;

            // Périodes grisées : hors disponibilités du médecin, puis indisponibilités.
            $shaded = [];
            $cursor = $gridStart;
            $ranges = $closed ? collect() : $availabilities->get($weekday->value, collect());

            foreach ($ranges as $range) {
                if ($range->startLabel() > $cursor) {
                    $shaded[] = $this->period($top, $at($cursor), $at($range->startLabel()), $cursor === $gridStart ? '' : "Pause · {$cursor} – {$range->startLabel()}");
                }
                $cursor = max($cursor, $range->endLabel());
            }

            if ($cursor < $gridEnd) {
                $label = match (true) {
                    $closed => 'Cabinet fermé',
                    $ranges->isEmpty() => 'Pas de consultation',
                    $cursor >= $closing => 'Cabinet fermé',
                    default => '',
                };
                $shaded[] = $this->period($top, $at($cursor), $at($gridEnd), $label);
            }

            foreach ($unavailabilities as $unavailability) {
                $start = $unavailability->starts_at->max($at($gridStart));
                $end = $unavailability->ends_at->min($at($gridEnd));

                if ($start->lt($end)) {
                    $shaded[] = $this->period($top, $start, $end, trim('Indisponible · '.$unavailability->reason, ' ·'));
                }
            }

            // Rendez-vous du jour ; un annulé recouvert par un rendez-vous actif n'est pas affiché.
            $ofDay = $appointments->filter(fn (Appointment $a) => $a->starts_at->isSameDay($day));
            $blocks = $ofDay
                ->reject(fn (Appointment $a) => ! $a->isActive() && $ofDay->contains(
                    fn (Appointment $other) => $other->isActive() && $other->starts_at->lt($a->ends_at) && $other->ends_at->gt($a->starts_at)
                ))
                ->map(fn (Appointment $a) => [
                    'appointment' => $a,
                    'top' => $top($a->starts_at),
                    'height' => max($a->starts_at->diffInMinutes($a->ends_at) * self::PX_PER_MINUTE - 4, 20),
                ])
                ->values()
                ->all();

            $columns[] = ['date' => $day, 'shaded' => $shaded, 'blocks' => $blocks];
        }

        $hours = [];
        for ($hour = CarbonImmutable::parse($gridStart); $hour->format('H:i') < $gridEnd; $hour = $hour->addHour()) {
            $hours[] = $hour->format('H:i');
        }

        return [
            'hours' => $hours,
            'height' => $this->minutes($from, $gridStart, CarbonImmutable::parse($from->format('Y-m-d').' '.$gridEnd)) * self::PX_PER_MINUTE,
            'count' => $appointments->reject(fn (Appointment $a) => $a->status === AppointmentStatus::Cancelled)->count(),
            'columns' => $columns,
        ];
    }

    /**
     * Minutes entre le début de la grille ce jour-là et $time.
     */
    private function minutes(CarbonImmutable $day, string $gridStart, CarbonImmutable $time): float
    {
        return CarbonImmutable::parse($day->format('Y-m-d').' '.$gridStart)->diffInMinutes($time, false);
    }

    /**
     * @return array{top: float, height: float, label: string}
     */
    private function period(callable $top, CarbonImmutable $start, CarbonImmutable $end, string $label): array
    {
        return ['top' => $top($start), 'height' => $top($end) - $top($start), 'label' => $label];
    }
}
