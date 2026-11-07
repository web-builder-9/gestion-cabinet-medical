<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RG-12 : un créneau ne porte qu'un seul rendez-vous actif.
 * RG-13 : un patient ne peut pas avoir deux rendez-vous actifs qui se chevauchent.
 */
class AppointmentOverlapTest extends TestCase
{
    use RefreshDatabase;

    private Doctor $doctor;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctor = Doctor::factory()->create();
        $this->patient = Patient::factory()->create();
    }

    public function test_a_slot_overlapping_an_existing_appointment_is_detected(): void
    {
        $this->book('2026-11-02 10:00');

        $this->assertTrue($this->doctorBusy('2026-11-02 10:15', '2026-11-02 10:45'));
        $this->assertTrue($this->doctorBusy('2026-11-02 09:45', '2026-11-02 10:15'));
        $this->assertTrue($this->doctorBusy('2026-11-02 09:00', '2026-11-02 12:00'));
        $this->assertTrue($this->doctorBusy('2026-11-02 10:00', '2026-11-02 10:30'));
    }

    public function test_adjacent_slots_are_not_in_conflict(): void
    {
        $this->book('2026-11-02 10:00');

        $this->assertFalse($this->doctorBusy('2026-11-02 10:30', '2026-11-02 11:00'));
        $this->assertFalse($this->doctorBusy('2026-11-02 09:30', '2026-11-02 10:00'));
    }

    public function test_only_pending_and_confirmed_appointments_occupy_a_slot(): void
    {
        foreach (['cancelled', 'completed', 'noShow'] as $state) {
            Appointment::factory()->at(CarbonImmutable::parse('2026-11-02 10:00'))->{$state}()
                ->create(['doctor_id' => $this->doctor->id]);
        }

        $this->assertFalse($this->doctorBusy('2026-11-02 10:00', '2026-11-02 10:30'));

        $this->book('2026-11-02 10:00', 'pending');
        $this->assertTrue($this->doctorBusy('2026-11-02 10:00', '2026-11-02 10:30'));
    }

    public function test_another_doctor_can_use_the_same_slot(): void
    {
        $this->book('2026-11-02 10:00');
        $otherDoctor = Doctor::factory()->create();

        $this->assertFalse(Appointment::overlapping($otherDoctor->id, CarbonImmutable::parse('2026-11-02 10:00'), CarbonImmutable::parse('2026-11-02 10:30'))->exists());
    }

    public function test_a_patient_cannot_be_in_two_places_at_once(): void
    {
        $this->book('2026-11-02 10:00');

        $other = Appointment::factory()->at(CarbonImmutable::parse('2026-11-02 10:15'))->confirmed()->make([
            'patient_id' => $this->patient->id,
            'doctor_id' => Doctor::factory()->create()->id,
        ]);

        $this->assertTrue(Appointment::overlappingForPatient($this->patient->id, $other->starts_at, $other->ends_at)->exists());
        $this->assertTrue($other->overlaps());
    }

    public function test_an_appointment_does_not_conflict_with_itself_when_moved(): void
    {
        $appointment = $this->book('2026-11-02 10:00');

        $this->assertFalse($appointment->overlaps());
        $this->assertFalse(Appointment::overlapping(
            $this->doctor->id, $appointment->starts_at, $appointment->ends_at, $appointment->id
        )->exists());
    }

    public function test_a_double_booking_is_reported_as_a_conflict(): void
    {
        $this->book('2026-11-02 10:00');
        $second = Appointment::factory()->at(CarbonImmutable::parse('2026-11-02 10:15'))->confirmed()
            ->create(['doctor_id' => $this->doctor->id]);

        $this->assertTrue($second->overlaps());
    }

    private function book(string $start, string $state = 'confirmed'): Appointment
    {
        return Appointment::factory()
            ->at(CarbonImmutable::parse($start))
            ->{$state}()
            ->create(['doctor_id' => $this->doctor->id, 'patient_id' => $this->patient->id]);
    }

    private function doctorBusy(string $start, string $end): bool
    {
        return Appointment::overlapping($this->doctor->id, CarbonImmutable::parse($start), CarbonImmutable::parse($end))->exists();
    }
}
