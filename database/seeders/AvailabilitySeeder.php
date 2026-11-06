<?php

namespace Database\Seeders;

use App\Enums\DayOfWeek;
use App\Models\Doctor;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Semaine type de chaque médecin (maquette 12) et quelques indisponibilités.
 * Les plages restent dans les horaires du cabinet (RG-10) : 08:00-17:00, samedi 08:00-12:00.
 */
class AvailabilitySeeder extends Seeder
{
    public function run(): void
    {
        $weekdays = [DayOfWeek::Monday, DayOfWeek::Tuesday, DayOfWeek::Wednesday, DayOfWeek::Thursday, DayOfWeek::Friday];

        $schedules = [
            // Dr Ben Ali : tous les jours, matin et après-midi, samedi matin.
            'medecin@cabinet.test' => [
                ...$this->ranges($weekdays, '08:00', '12:00'),
                ...$this->ranges($weekdays, '14:00', '17:00'),
                ...$this->ranges([DayOfWeek::Saturday], '08:00', '12:00'),
            ],
            // Dr Saidi : matins du lundi au samedi, après-midi mardi et jeudi.
            'm.saidi@cabinet.test' => [
                ...$this->ranges([...$weekdays, DayOfWeek::Saturday], '08:00', '12:00'),
                ...$this->ranges([DayOfWeek::Tuesday, DayOfWeek::Thursday], '14:00', '17:00'),
            ],
            // Dr Ammar : lundi, mercredi et vendredi.
            'l.ammar@cabinet.test' => [
                ...$this->ranges([DayOfWeek::Monday, DayOfWeek::Wednesday, DayOfWeek::Friday], '08:00', '12:00'),
                ...$this->ranges([DayOfWeek::Monday, DayOfWeek::Wednesday], '14:00', '17:00'),
            ],
        ];

        foreach ($schedules as $email => $ranges) {
            $doctor = Doctor::whereHas('user', fn ($q) => $q->where('email', $email))->firstOrFail();
            $doctor->availabilities()->createMany($ranges);
        }

        // Indisponibilités du Dr Ben Ali (maquette 12) : un après-midi de congé et une formation.
        $benAli = Doctor::whereHas('user', fn ($q) => $q->where('email', 'medecin@cabinet.test'))->firstOrFail();
        $nextFriday = CarbonImmutable::today()->addWeek()->startOfWeek()->addDays(4);

        $benAli->unavailabilities()->createMany([
            ['starts_at' => $nextFriday->setTime(14, 0), 'ends_at' => $nextFriday->setTime(17, 0), 'reason' => 'Congé'],
            ['starts_at' => $nextFriday->addWeeks(5)->setTime(8, 0), 'ends_at' => $nextFriday->addWeeks(5)->addDays(1)->setTime(12, 0), 'reason' => 'Formation médicale'],
        ]);
    }

    /**
     * Une plage identique pour plusieurs jours.
     *
     * @param  list<DayOfWeek>  $days
     * @return list<array<string, mixed>>
     */
    private function ranges(array $days, string $start, string $end, int $slotMinutes = 30): array
    {
        return array_map(fn (DayOfWeek $day) => [
            'day_of_week' => $day,
            'start_time' => $start,
            'end_time' => $end,
            'slot_minutes' => $slotMinutes,
        ], $days);
    }
}
