<?php

namespace Tests\Feature\Patients;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_staff_sees_the_identity_and_contact_details(): void
    {
        $patient = Patient::factory()->create([
            'first_name' => 'Amira',
            'last_name' => 'Trabelsi',
            'birth_date' => '1990-05-14',
            'phone' => '22 123 456',
            'cin' => '01234567',
        ]);

        $this->actingAs(User::factory()->secretary()->create())
            ->get("/patients/{$patient->id}")
            ->assertOk()
            ->assertSee('Amira Trabelsi')
            ->assertSee('14 mai 1990')
            ->assertSee('22 123 456')
            ->assertSee('01234567');
    }

    public function test_upcoming_and_past_appointments_are_listed(): void
    {
        $patient = Patient::factory()->create();
        $doctor = User::factory()->doctor()->create(['name' => 'Dr Ben Salah']);

        Appointment::factory()
            ->at(CarbonImmutable::now()->addDays(3)->setTime(10, 30))
            ->confirmed()
            ->create(['patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'reason' => 'Contrôle annuel']);

        Appointment::factory()
            ->at(CarbonImmutable::now()->subMonth()->setTime(9, 0))
            ->completed()
            ->create(['patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'reason' => 'Fièvre']);

        $this->actingAs($doctor)
            ->get("/patients/{$patient->id}")
            ->assertOk()
            ->assertSeeInOrder(['Rendez-vous à venir', 'Contrôle annuel', 'Confirmé', 'Derniers rendez-vous', 'Fièvre', 'Terminé'])
            ->assertSee('à 10:30')
            ->assertSee('Dr Ben Salah');
    }

    public function test_a_patient_without_appointments_shows_empty_messages(): void
    {
        $patient = Patient::factory()->create();

        $this->actingAs(User::factory()->doctor()->create())
            ->get("/patients/{$patient->id}")
            ->assertSee('Aucun rendez-vous à venir.')
            ->assertSee('Aucun rendez-vous passé.');
    }

    public function test_the_list_links_to_the_patient_page(): void
    {
        $patient = Patient::factory()->create();

        $this->actingAs(User::factory()->secretary()->create())
            ->get('/patients')
            ->assertSee(route('patients.show', $patient));
    }

    public function test_after_creation_the_user_lands_on_the_patient_page(): void
    {
        $this->actingAs(User::factory()->secretary()->create())
            ->post('/patients', [
                'first_name' => 'Karim',
                'last_name' => 'Gharbi',
                'birth_date' => '1985-01-01',
                'gender' => 'M',
                'phone' => '55666777',
            ])
            ->assertRedirect(route('patients.show', Patient::firstOrFail()));
    }

    public function test_patient_accounts_cannot_open_patient_pages_yet(): void
    {
        $user = User::factory()->patient()->create();
        $ownRecord = Patient::factory()->for($user)->create();

        // La PatientPolicy l'autoriserait, mais l'espace patient n'est pas encore ouvert :
        // le middleware de rôle bloque toute la zone /patients.
        $this->actingAs($user)->get("/patients/{$ownRecord->id}")->assertForbidden();
    }

    public function test_an_unknown_patient_gives_a_404(): void
    {
        $this->actingAs(User::factory()->doctor()->create())->get('/patients/999')->assertNotFound();
    }
}
