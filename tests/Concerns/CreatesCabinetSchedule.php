<?php

namespace Tests\Concerns;

use App\Enums\DayOfWeek;
use App\Models\Availability;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Données communes aux tests des rendez-vous : on se place le lundi 2 novembre 2026
 * à 07:00 ; le Dr Ben Ali consulte du lundi au vendredi de 08:00 à 12:00 (créneaux de 30 min).
 */
trait CreatesCabinetSchedule
{
    protected Doctor $doctor;

    protected Patient $patient;

    protected User $secretary;

    protected function createSchedule(): void
    {
        $this->travelTo(CarbonImmutable::create(2026, 11, 2, 7));

        $this->doctor = Doctor::factory()->withUser(['first_name' => 'Salma', 'last_name' => 'Ben Ali'])->create();

        foreach ([DayOfWeek::Monday, DayOfWeek::Tuesday, DayOfWeek::Wednesday, DayOfWeek::Thursday, DayOfWeek::Friday] as $day) {
            Availability::factory()->for($this->doctor)->on($day, '08:00', '12:00')->create();
        }

        $this->patient = Patient::factory()->withUser(['first_name' => 'Hedi', 'last_name' => 'Mansouri'])->withMedicalRecord()->create(['gender' => 'M']);
        $this->secretary = User::factory()->secretary()->create(['first_name' => 'Nadia', 'last_name' => 'Jlassi']);
    }

    /**
     * Jeudi 5 novembre 2026 à l'heure donnée.
     */
    protected function thursday(string $time): CarbonImmutable
    {
        return CarbonImmutable::parse("2026-11-05 {$time}");
    }
}
