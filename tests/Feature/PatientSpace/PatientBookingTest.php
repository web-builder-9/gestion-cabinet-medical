<?php

namespace Tests\Feature\PatientSpace;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCabinetSchedule;
use Tests\TestCase;

/**
 * Prise de rendez-vous en ligne (maquette 5, BF-07, BF-08), modification et
 * annulation par le patient (BF-09) : RG-12 à RG-16.
 */
class PatientBookingTest extends TestCase
{
    use CreatesCabinetSchedule;
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchedule();
        $this->user = $this->patient->user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function request(array $overrides = []): array
    {
        return array_merge(['doctor_id' => $this->doctor->id, 'date' => '2026-11-05', 'time' => '10:30', 'reason' => 'Contrôle après bronchite'], $overrides);
    }

    public function test_step_one_lists_the_doctors(): void
    {
        $this->actingAs($this->user)
            ->get('/prendre-rendez-vous')
            ->assertOk()
            ->assertSee('Dr Salma Ben Ali')
            ->assertSee('Prochain créneau');
    }

    public function test_step_two_shows_the_week_and_the_slots_of_the_day(): void
    {
        Appointment::factory()->for($this->doctor)->at($this->thursday('10:00'))->confirmed()->create();

        $this->actingAs($this->user)
            ->get('/prendre-rendez-vous?medecin='.$this->doctor->id.'&jour=2026-11-05')
            ->assertOk()
            ->assertSee($this->doctor->specialty->name.' · créneaux de 30 min')
            ->assertSeeInOrder(['Lun', '2', 'Mar', '3', 'Mer', '4', 'Jeu', '5'])
            ->assertSee('Matin')
            ->assertSee('value="10:30"', false)
            ->assertDontSee('value="10:00"', false) // déjà pris : barré
            ->assertSee('Votre rendez-vous sera « En attente » jusqu\'à sa confirmation par le secrétariat.', false);
    }

    public function test_an_online_request_is_created_pending(): void
    {
        $this->actingAs($this->user)
            ->post('/prendre-rendez-vous', $this->request())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('status', fn (string $status) => str_starts_with($status, 'Demande envoyée pour jeudi 5 novembre à 10:30.'));

        $appointment = Appointment::sole();
        $this->assertTrue($appointment->patient->is($this->patient));
        $this->assertSame(AppointmentStatus::Pending, $appointment->status); // RG-15
        $this->assertTrue($appointment->statusChanges()->sole()->author->is($this->user));
    }

    public function test_online_requests_follow_the_booking_rules(): void
    {
        Appointment::factory()->for($this->doctor)->at($this->thursday('10:30'))->pending()->create();

        $this->actingAs($this->user)->post('/prendre-rendez-vous', $this->request())->assertSessionHasErrors('time'); // RG-12
        $this->actingAs($this->user)->post('/prendre-rendez-vous', $this->request(['time' => '10:45']))->assertSessionHasErrors('time');
        $this->actingAs($this->user)->post('/prendre-rendez-vous', $this->request(['date' => '2027-01-07']))->assertSessionHasErrors('time'); // RG-14

        $this->assertSame(1, Appointment::count());
    }

    public function test_the_patient_modifies_their_appointment_and_it_waits_for_confirmation_again(): void
    {
        $appointment = Appointment::factory()->for($this->patient)->for($this->doctor)->at($this->thursday('10:30'))->confirmed()->create();

        $this->actingAs($this->user)
            ->get("/mes-rendez-vous/{$appointment->id}/modifier")
            ->assertOk()
            ->assertSee('Modifier mon rendez-vous');

        $this->actingAs($this->user)
            ->put("/mes-rendez-vous/{$appointment->id}", ['date' => '2026-11-06', 'time' => '09:00', 'reason' => 'Contrôle'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $appointment->refresh();
        $this->assertSame('2026-11-06 09:00', $appointment->starts_at->format('Y-m-d H:i'));
        $this->assertSame(AppointmentStatus::Pending, $appointment->status); // RG-16
    }

    public function test_the_patient_cancels_their_appointment(): void
    {
        $appointment = Appointment::factory()->for($this->patient)->for($this->doctor)->at($this->thursday('10:30'))->pending()->create();

        $this->actingAs($this->user)->patch("/mes-rendez-vous/{$appointment->id}/annuler")->assertRedirect(route('dashboard'));

        $this->assertSame(AppointmentStatus::Cancelled, $appointment->fresh()->status);
    }

    public function test_less_than_24_hours_before_only_the_secretariat_can_change_it(): void
    {
        $appointment = Appointment::factory()->for($this->patient)->for($this->doctor)->at($this->thursday('10:30'))->confirmed()->create();
        $this->travelTo($this->thursday('11:00')->subDay()); // mercredi 11:00 : moins de 24 h avant 10:30

        $this->actingAs($this->user)->patch("/mes-rendez-vous/{$appointment->id}/annuler")->assertForbidden();
        $this->actingAs($this->user)->get("/mes-rendez-vous/{$appointment->id}/modifier")->assertForbidden();

        $this->actingAs($this->user)
            ->get('/dashboard')
            ->assertSee('Moins de 24 h avant : pour modifier ou annuler, appelez le cabinet.')
            ->assertDontSee('/annuler');

        $this->assertSame(AppointmentStatus::Confirmed, $appointment->fresh()->status);
    }

    public function test_a_patient_cannot_touch_another_patients_appointment(): void
    {
        $other = Appointment::factory()->for(Patient::factory())->for($this->doctor)->at($this->thursday('11:00'))->pending()->create();

        $this->actingAs($this->user)->patch("/mes-rendez-vous/{$other->id}/annuler")->assertForbidden();
        $this->actingAs($this->user)->put("/mes-rendez-vous/{$other->id}", ['date' => '2026-11-06', 'time' => '09:00'])->assertForbidden();

        $this->assertSame(AppointmentStatus::Pending, $other->fresh()->status);
    }

    public function test_staff_cannot_use_the_patient_booking(): void
    {
        $this->actingAs($this->secretary)->get('/prendre-rendez-vous')->assertForbidden();
        $this->actingAs($this->secretary)->post('/prendre-rendez-vous', $this->request())->assertForbidden();
    }
}
