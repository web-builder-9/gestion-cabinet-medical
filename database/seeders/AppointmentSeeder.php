<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Agenda de démonstration : deux semaines avant et après aujourd'hui.
 *
 * Les créneaux de chaque jour sont tirés SANS remise parmi les créneaux
 * libres, donc l'agenda généré ne contient aucun conflit d'horaire.
 */
class AppointmentSeeder extends Seeder
{
    /** Nombre de rendez-vous créés pour aujourd'hui (tableau de bord). */
    public const TODAY_COUNT = 8;

    public function run(): void
    {
        $doctor = User::query()->doctors()->firstOrFail();
        $patientIds = Patient::query()->pluck('id');
        $today = CarbonImmutable::today();

        for ($offset = -14; $offset <= 14; $offset++) {
            $day = $today->addDays($offset);

            // Pas de rendez-vous le week-end, sauf aujourd'hui pour la démo.
            if ($day->isWeekend() && $offset !== 0) {
                continue;
            }

            $count = $offset === 0 ? self::TODAY_COUNT : fake()->numberBetween(3, 8);

            foreach ($this->slotsFor($day)->random($count) as $start) {
                Appointment::factory()->at($start)->create([
                    'patient_id' => $patientIds->random(),
                    'doctor_id' => $doctor->id,
                ]);
            }
        }
    }

    /**
     * Créneaux de 30 minutes : 08:00-12:00 et 14:00-18:00 (16 créneaux).
     *
     * @return Collection<int, CarbonImmutable>
     */
    private function slotsFor(CarbonImmutable $day): Collection
    {
        $morning = collect(range(0, 7))->map(fn (int $i) => $day->setTime(8, 0)->addMinutes(30 * $i));
        $afternoon = collect(range(0, 7))->map(fn (int $i) => $day->setTime(14, 0)->addMinutes(30 * $i));

        return $morning->concat($afternoon);
    }
}
