<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    public const REASONS = [
        'Consultation générale', 'Contrôle', "Renouvellement d'ordonnance", 'Fièvre',
        'Douleurs abdominales', 'Certificat médical', 'Vaccination', 'Suivi tension artérielle',
        'Toux persistante', 'Bilan annuel',
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
            'doctor_id' => Doctor::factory(),
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

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => ['status' => AppointmentStatus::Pending]);
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

    public function noShow(): static
    {
        return $this->state(fn (array $attributes) => ['status' => AppointmentStatus::NoShow]);
    }

    /**
     * Passé : surtout terminé, parfois annulé ou absent. Futur : en attente ou confirmé.
     */
    public static function plausibleStatus(CarbonImmutable $start): AppointmentStatus
    {
        if ($start->isPast()) {
            return fake()->randomElement([
                ...array_fill(0, 8, AppointmentStatus::Completed),
                AppointmentStatus::Cancelled,
                AppointmentStatus::NoShow,
            ]);
        }

        return fake()->boolean(70) ? AppointmentStatus::Confirmed : AppointmentStatus::Pending;
    }
}
