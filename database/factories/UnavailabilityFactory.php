<?php

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\Unavailability;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unavailability>
 */
class UnavailabilityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = CarbonImmutable::today()->addDays(fake()->numberBetween(3, 20))->setTime(8, 0);

        return [
            'doctor_id' => Doctor::factory(),
            'starts_at' => $start,
            'ends_at' => $start->setTime(17, 0),
            'reason' => fake()->randomElement(['Congé', 'Formation médicale', 'Réunion à l\'hôpital']),
        ];
    }
}
