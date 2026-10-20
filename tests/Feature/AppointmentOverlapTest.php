<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Règle métier critique : un médecin ne peut pas avoir deux rendez-vous
 * actifs qui se chevauchent.
 */
class AppointmentOverlapTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctor = User::factory()->doctor()->create();
    }

    public function test_a_slot_overlapping_an_existing_appointment_is_detected(): void
    {
        $this->book('2026-11-02 10:00');

        $this->assertTrue($this->overlaps('2026-11-02 10:15', '2026-11-02 10:45'));
        $this->assertTrue($this->overlaps('2026-11-02 09:45', '2026-11-02 10:15'));
        $this->assertTrue($this->overlaps('2026-11-02 09:00', '2026-11-02 12:00'));
        $this->assertTrue($this->overlaps('2026-11-02 10:00', '2026-11-02 10:30'));
    }

    public function test_adjacent_slots_are_not_in_conflict(): void
    {
        $this->book('2026-11-02 10:00');

        $this->assertFalse($this->overlaps('2026-11-02 10:30', '2026-11-02 11:00'));
        $this->assertFalse($this->overlaps('2026-11-02 09:30', '2026-11-02 10:00'));
    }

    public function test_cancelled_appointments_free_the_slot(): void
    {
        Appointment::factory()
            ->at(CarbonImmutable::parse('2026-11-02 10:00'))
            ->cancelled()
            ->create(['doctor_id' => $this->doctor->id]);

        $this->assertFalse($this->overlaps('2026-11-02 10:00', '2026-11-02 10:30'));
    }

    public function test_another_doctor_can_use_the_same_slot(): void
    {
        $this->book('2026-11-02 10:00');
        $otherDoctor = User::factory()->doctor()->create();

        $this->assertFalse($this->overlaps('2026-11-02 10:00', '2026-11-02 10:30', $otherDoctor));
    }

    public function test_an_appointment_does_not_conflict_with_itself_when_edited(): void
    {
        $appointment = $this->book('2026-11-02 10:00');

        $this->assertFalse($appointment->hasConflict());
        $this->assertFalse($this->overlaps('2026-11-02 10:00', '2026-11-02 10:30', ignoreId: $appointment->id));
    }

    public function test_a_double_booking_is_reported_as_a_conflict(): void
    {
        $this->book('2026-11-02 10:00');
        $second = $this->book('2026-11-02 10:15');

        $this->assertTrue($second->hasConflict());
    }

    private function book(string $start, int $minutes = 30): Appointment
    {
        return Appointment::factory()
            ->at(CarbonImmutable::parse($start), $minutes)
            ->scheduled()
            ->create(['doctor_id' => $this->doctor->id]);
    }

    private function overlaps(string $start, string $end, ?User $doctor = null, ?int $ignoreId = null): bool
    {
        return Appointment::query()
            ->overlapping(
                ($doctor ?? $this->doctor)->id,
                CarbonImmutable::parse($start),
                CarbonImmutable::parse($end),
                $ignoreId,
            )
            ->exists();
    }
}
