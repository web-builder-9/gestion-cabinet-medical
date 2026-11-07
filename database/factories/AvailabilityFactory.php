<?php

namespace Database\Factories;

use App\Enums\DayOfWeek;
use App\Models\Availability;
use App\Models\Doctor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Availability>
 */
class AvailabilityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'doctor_id' => Doctor::factory(),
            'day_of_week' => DayOfWeek::Monday,
            'start_time' => '08:00',
            'end_time' => '12:00',
            'slot_minutes' => 30,
        ];
    }

    public function on(DayOfWeek $day, string $start, string $end, int $slotMinutes = 30): static
    {
        return $this->state(fn () => [
            'day_of_week' => $day,
            'start_time' => $start,
            'end_time' => $end,
            'slot_minutes' => $slotMinutes,
        ]);
    }
}
