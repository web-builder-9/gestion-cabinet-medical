<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    private const REASONS = [
        'Consultation générale', 'Contrôle', "Renouvellement d'ordonnance", 'Fièvre',
        'Douleurs abdominales', 'Certificat médical', 'Vaccination', 'Suivi tension artérielle',
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = CarbonImmutable::instance(fake()->dateTimeBetween('-30 days', '+30 days'))
            ->setTime(fake()->numberBetween(8, 16), fake()->randomElement([0, 30]));

        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => User::factory()->doctor(),
            'starts_at' => $start,
            'ends_at' => $start->addMinutes(30),
            'reason' => fake()->randomElement(self::REASONS),
            'status' => self::plausibleStatus($start),
            'notes' => null,
        ];
    }

    /**
     * Place le rendez-vous à une date précise (durée en minutes).
     */
    public function at(DateTimeInterface $start, int $minutes = 30): static
    {
        $start = CarbonImmutable::instance($start);

        return $this->state(fn (array $attributes) => [
            'starts_at' => $start,
            'ends_at' => $start->addMinutes($minutes),
            'status' => self::plausibleStatus($start),
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => ['status' => AppointmentStatus::Scheduled]);
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => ['status' => AppointmentStatus::Confirmed]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => ['status' => AppointmentStatus::Cancelled]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => ['status' => AppointmentStatus::Completed]);
    }

    /**
     * Passé : surtout terminé, parfois annulé. Futur : prévu ou confirmé.
     */
    private static function plausibleStatus(CarbonImmutable $start): AppointmentStatus
    {
        if ($start->isPast()) {
            return fake()->boolean(85) ? AppointmentStatus::Completed : AppointmentStatus::Cancelled;
        }

        return fake()->boolean(60) ? AppointmentStatus::Confirmed : AppointmentStatus::Scheduled;
    }
}
