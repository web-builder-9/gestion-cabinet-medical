<?php

namespace Tests\Feature\Appointments;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Unavailability;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\CreatesCabinetSchedule;
use Tests\TestCase;

/**
 * Prise de rendez-vous par le secrétariat (BF-13, maquette 10) : RG-11 à RG-15.
 */
class BookingTest extends TestCase
{
    use CreatesCabinetSchedule;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchedule();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validData(array $overrides = []): array
    {
        return array_merge([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'date' => '2026-11-05',
            'time' => '10:00',
            'reason' => 'Contrôle de tension',
            'notes' => 'Apporter les derniers résultats.',
        ], $overrides);
    }

    private function book(array $overrides = [], ?User $as = null): TestResponse
    {
        return $this->actingAs($as ?? $this->secretary)->post('/rendez-vous', $this->validData($overrides));
    }

    public function test_the_secretary_books_a_free_slot_and_it_is_confirmed(): void
    {
        $this->book()->assertSessionHasNoErrors();

        $appointment = Appointment::sole();
        $this->assertSame(AppointmentStatus::Confirmed, $appointment->status); // RG-15
        $this->assertTrue($appointment->starts_at->eq($this->thursday('10:00')));
        $this->assertTrue($appointment->ends_at->eq($this->thursday('10:30')));
        $this->assertSame('Contrôle de tension', $appointment->reason);

        // RG-23 : la création est historisée avec son auteur.
        $change = $appointment->statusChanges()->sole();
        $this->assertNull($change->old_status);
        $this->assertTrue($change->author->is($this->secretary));
    }

    public function test_the_booking_redirects_to_the_appointment_page(): void
    {
        $this->book()
            ->assertRedirect(route('appointments.show', Appointment::sole()))
            ->assertSessionHas('status', 'Rendez-vous enregistré : jeudi 5 novembre à 10:00 avec Dr Salma Ben Ali.');
    }

    public function test_a_slot_holds_a_single_active_appointment(): void
    {
        $karim = Patient::factory()->withUser(['first_name' => 'Karim', 'last_name' => 'Trabelsi'])->create();
        Appointment::factory()->for($karim)->for($this->doctor)->at($this->thursday('10:00'))->confirmed()->create();

        $this->book()->assertSessionHasErrors([
            'time' => 'Ce créneau est déjà pris. Karim Trabelsi a un rendez-vous confirmé de 10:00 à 10:30 avec Dr Salma Ben Ali. Un créneau ne porte qu\'un seul rendez-vous actif.',
        ]);

        $this->assertSame(1, Appointment::count());
    }

    public function test_a_cancelled_appointment_frees_its_slot(): void
    {
        Appointment::factory()->for($this->doctor)->at($this->thursday('10:00'))->cancelled()->create();

        $this->book()->assertSessionHasNoErrors();
    }

    public function test_a_patient_cannot_have_two_overlapping_appointments(): void
    {
        $other = Appointment::factory()->for($this->patient)->at($this->thursday('10:00'))->pending()->create();

        $this->book()->assertSessionHasErrors([
            'time' => "Hedi Mansouri a déjà un rendez-vous de 10:00 à 10:30 avec {$other->doctor->display_name} : un patient ne peut pas avoir deux rendez-vous qui se chevauchent.",
        ]);
    }

    public function test_only_slots_of_the_availabilities_can_be_booked(): void
    {
        // 10:15 n'est pas un début de créneau ; 14:00 est hors des disponibilités ; samedi aussi.
        $this->book(['time' => '10:15'])->assertSessionHasErrors('time');
        $this->book(['time' => '14:00'])->assertSessionHasErrors('time');
        $this->book(['date' => '2026-11-07', 'time' => '09:00'])->assertSessionHasErrors('time');

        $this->assertSame(0, Appointment::count());
    }

    public function test_an_unavailability_blocks_its_slots(): void
    {
        Unavailability::factory()->for($this->doctor)->create(['starts_at' => $this->thursday('08:00'), 'ends_at' => $this->thursday('12:00')]);

        $this->book()->assertSessionHasErrors(['time' => 'Dr Salma Ben Ali est indisponible sur ce créneau.']);
    }

    public function test_slots_must_be_in_the_future_and_within_the_booking_horizon(): void
    {
        $this->travelTo($this->thursday('10:10'));
        $this->book()->assertSessionHasErrors(['time' => 'Ce créneau est déjà passé : un rendez-vous se prend sur un créneau futur.']);

        // RG-14 : au plus 60 jours à l'avance (jeudi 7 janvier 2027 = 63 jours).
        $this->book(['date' => '2027-01-07'])->assertSessionHasErrors(['time' => 'Un rendez-vous se prend au plus 60 jours à l\'avance.']);
    }

    public function test_required_fields_and_archived_patients(): void
    {
        $this->book(['patient_id' => '', 'time' => ''])
            ->assertSessionHasErrors(['patient_id' => 'Choisissez le patient.', 'time' => 'Choisissez un créneau.']);

        $this->patient->delete(); // archivé
        $this->book()->assertSessionHasErrors('patient_id');
    }

    public function test_an_inactive_doctor_cannot_receive_appointments(): void
    {
        $this->doctor->user->forceFill(['is_active' => false])->save();

        $this->book()->assertSessionHasErrors(['doctor_id' => 'Ce médecin n\'est plus en activité.']);
    }

    public function test_the_form_shows_the_free_slots_and_the_day_of_the_doctor(): void
    {
        Appointment::factory()->for($this->doctor)->for(Patient::factory()->withUser(['first_name' => 'Karim', 'last_name' => 'Trabelsi']))
            ->at($this->thursday('10:00'))->confirmed()->create();

        $this->actingAs($this->secretary)
            ->get('/rendez-vous/nouveau?patient='.$this->patient->id.'&medecin='.$this->doctor->id.'&date=2026-11-05')
            ->assertOk()
            ->assertSee('Hedi Mansouri')
            ->assertSee('<option value="09:30">09:30 – 10:00</option>', false)
            ->assertDontSee('<option value="10:00">', false)
            ->assertSee('K. Trabelsi · confirmé')
            ->assertSee('Confirmé (pris par le secrétariat)');
    }

    public function test_the_secretary_searches_the_patient_from_the_form(): void
    {
        $this->actingAs($this->secretary)
            ->get('/rendez-vous/nouveau?q=Mansouri')
            ->assertOk()
            ->assertSee('Hedi Mansouri')
            ->assertSee('Choisir');
    }

    public function test_nearby_free_slots_are_suggested_after_a_refusal(): void
    {
        foreach (['09:30', '10:00', '10:30'] as $time) {
            Appointment::factory()->for($this->doctor)->at($this->thursday($time))->confirmed()->create();
        }

        // Après le refus, le formulaire revient avec l'erreur et trois créneaux libres proches de 10:00.
        $this->actingAs($this->secretary)
            ->from('/rendez-vous/nouveau?patient='.$this->patient->id.'&medecin='.$this->doctor->id.'&date=2026-11-05')
            ->followingRedirects()
            ->post('/rendez-vous', $this->validData())
            ->assertSee('Ce créneau est déjà pris.')
            ->assertSeeInOrder(['Créneaux libres proches', '08:30', '09:00', '11:00']);
    }

    public function test_the_doctor_can_book_but_not_the_admin_or_a_patient(): void
    {
        $this->book(as: $this->doctor->user)->assertSessionHasNoErrors();

        foreach ([User::factory()->admin()->create(), User::factory()->patient()->create()] as $user) {
            $this->book(['time' => '11:00'], $user)->assertForbidden();
            $this->actingAs($user)->get('/rendez-vous/nouveau')->assertForbidden();
        }

        $this->assertSame(1, Appointment::count());
    }

    public function test_a_new_patient_can_be_booked_right_after_creation(): void
    {
        $this->actingAs($this->secretary)
            ->post('/patients', [
                'first_name' => 'Karim', 'last_name' => 'Trabelsi', 'birth_date' => '1968-02-28', 'gender' => 'M',
                'phone' => '22117845', 'data_consent' => '1', 'next' => 'appointment',
            ])
            ->assertRedirect(route('appointments.create', ['patient' => Patient::latest('id')->first()->id]));
    }
}
