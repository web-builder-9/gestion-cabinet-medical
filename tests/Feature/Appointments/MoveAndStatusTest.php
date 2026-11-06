<?php

namespace Tests\Feature\Appointments;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCabinetSchedule;
use Tests\TestCase;

/**
 * Déplacer, confirmer, annuler, marquer absent (BF-14, BF-16) : RG-17, RG-22, RG-23.
 */
class MoveAndStatusTest extends TestCase
{
    use CreatesCabinetSchedule;
    use RefreshDatabase;

    private Appointment $appointment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchedule();
        $this->appointment = Appointment::factory()->for($this->patient)->for($this->doctor)
            ->at($this->thursday('10:00'))->pending()->create(['reason' => 'Toux persistante']);
    }

    public function test_the_secretary_moves_an_appointment_to_a_free_slot(): void
    {
        $this->actingAs($this->secretary)
            ->get("/rendez-vous/{$this->appointment->id}/deplacer")
            ->assertOk()
            ->assertSee('Déplacer le rendez-vous')
            ->assertSee('Créneau actuel');

        $this->actingAs($this->secretary)
            ->put("/rendez-vous/{$this->appointment->id}", [
                'doctor_id' => $this->doctor->id, 'date' => '2026-11-06', 'time' => '08:30', 'reason' => 'Toux persistante',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('appointments.show', $this->appointment));

        $this->appointment->refresh();
        $this->assertTrue($this->appointment->starts_at->eq(now()->setDate(2026, 11, 6)->setTime(8, 30)));
        // L'état ne change pas : une demande déplacée reste à confirmer.
        $this->assertSame(AppointmentStatus::Pending, $this->appointment->status);
    }

    public function test_an_appointment_can_keep_its_own_slot_when_edited(): void
    {
        $this->actingAs($this->secretary)
            ->put("/rendez-vous/{$this->appointment->id}", [
                'doctor_id' => $this->doctor->id, 'date' => '2026-11-05', 'time' => '10:00', 'reason' => 'Bilan',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Bilan', $this->appointment->fresh()->reason);
    }

    public function test_an_appointment_cannot_be_moved_onto_a_taken_slot(): void
    {
        Appointment::factory()->for($this->doctor)->at($this->thursday('11:00'))->confirmed()->create();

        $this->actingAs($this->secretary)
            ->put("/rendez-vous/{$this->appointment->id}", ['doctor_id' => $this->doctor->id, 'date' => '2026-11-05', 'time' => '11:00'])
            ->assertSessionHasErrors('time');

        $this->assertTrue($this->appointment->fresh()->starts_at->eq($this->thursday('10:00')));
    }

    public function test_the_patient_of_an_appointment_cannot_be_changed(): void
    {
        $this->actingAs($this->secretary)
            ->put("/rendez-vous/{$this->appointment->id}", [
                'patient_id' => 999, 'doctor_id' => $this->doctor->id, 'date' => '2026-11-05', 'time' => '10:00',
            ])
            ->assertSessionHasErrors('patient_id');
    }

    public function test_confirming_a_request_is_recorded_in_the_history(): void
    {
        $this->actingAs($this->secretary)
            ->patch("/rendez-vous/{$this->appointment->id}/confirmer")
            ->assertSessionHas('status');

        $this->assertSame(AppointmentStatus::Confirmed, $this->appointment->fresh()->status);

        $change = $this->appointment->statusChanges->last();
        $this->assertSame(AppointmentStatus::Pending, $change->old_status);
        $this->assertSame(AppointmentStatus::Confirmed, $change->new_status);
        $this->assertTrue($change->author->is($this->secretary));

        $this->actingAs($this->secretary)
            ->get("/rendez-vous/{$this->appointment->id}")
            ->assertSee('En attente → <strong class="font-semibold">Confirmé</strong>', false)
            ->assertSee('Nadia Jlassi (secrétaire)');
    }

    public function test_cancelling_frees_the_slot_and_keeps_the_appointment(): void
    {
        $this->actingAs($this->secretary)->patch("/rendez-vous/{$this->appointment->id}/annuler");

        $this->assertSame(AppointmentStatus::Cancelled, $this->appointment->fresh()->status);
        $this->assertModelExists($this->appointment); // RG-22

        // Le créneau est de nouveau libre.
        $this->actingAs($this->secretary)
            ->post('/rendez-vous', [
                'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id, 'date' => '2026-11-05', 'time' => '10:00',
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_a_final_state_cannot_change(): void
    {
        $this->appointment->changeStatus(AppointmentStatus::Cancelled);

        $this->actingAs($this->secretary)->patch("/rendez-vous/{$this->appointment->id}/confirmer")->assertForbidden();
        $this->actingAs($this->secretary)->get("/rendez-vous/{$this->appointment->id}/deplacer")->assertForbidden();
        $this->assertSame(AppointmentStatus::Cancelled, $this->appointment->fresh()->status);
    }

    public function test_a_patient_is_marked_absent_only_once_the_time_has_passed(): void
    {
        $this->appointment->changeStatus(AppointmentStatus::Confirmed);

        $this->actingAs($this->secretary)->patch("/rendez-vous/{$this->appointment->id}/absent")->assertForbidden();

        $this->travelTo($this->thursday('10:20'));
        $this->actingAs($this->secretary)->patch("/rendez-vous/{$this->appointment->id}/absent")->assertSessionHas('status');

        $this->assertSame(AppointmentStatus::NoShow, $this->appointment->fresh()->status);
    }

    public function test_the_admin_and_patients_cannot_change_appointments(): void
    {
        foreach ([User::factory()->admin()->create(), $this->patient->user] as $user) {
            $this->actingAs($user)->patch("/rendez-vous/{$this->appointment->id}/confirmer")->assertForbidden();
            $this->actingAs($user)->patch("/rendez-vous/{$this->appointment->id}/annuler")->assertForbidden();
            $this->actingAs($user)->get("/rendez-vous/{$this->appointment->id}")->assertForbidden();
        }

        $this->assertSame(AppointmentStatus::Pending, $this->appointment->fresh()->status);
    }
}
