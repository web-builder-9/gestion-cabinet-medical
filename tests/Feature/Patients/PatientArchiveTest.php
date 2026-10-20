<?php

namespace Tests\Feature\Patients;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Suppression douce : un patient supprimé est archivé (deleted_at rempli),
 * jamais effacé. Seul le médecin peut archiver et restaurer.
 */
class PatientArchiveTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    private User $secretary;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctor = User::factory()->doctor()->create();
        $this->secretary = User::factory()->secretary()->create();
        $this->patient = Patient::factory()->withMedicalRecord()->create([
            'first_name' => 'Amira',
            'last_name' => 'Trabelsi',
        ]);
    }

    public function test_the_doctor_can_archive_a_patient(): void
    {
        $appointment = Appointment::factory()->create(['patient_id' => $this->patient->id]);

        $this->actingAs($this->doctor)
            ->delete("/patients/{$this->patient->id}")
            ->assertRedirect('/patients')
            ->assertSessionHas('status');

        $this->assertSoftDeleted($this->patient);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id]);
        $this->assertDatabaseHas('medical_records', ['patient_id' => $this->patient->id]);

        $this->actingAs($this->doctor)->get('/patients')->assertDontSee('Trabelsi Amira');
        $this->actingAs($this->doctor)->get('/patients/archives')->assertOk()->assertSee('Trabelsi Amira');
        $this->actingAs($this->doctor)->get("/patients/{$this->patient->id}")->assertNotFound();
    }

    public function test_the_secretary_cannot_archive_a_patient(): void
    {
        $this->actingAs($this->secretary)
            ->delete("/patients/{$this->patient->id}")
            ->assertForbidden();

        $this->assertNotSoftDeleted($this->patient);
    }

    public function test_the_delete_button_is_shown_to_the_doctor_only(): void
    {
        $this->actingAs($this->doctor)
            ->get("/patients/{$this->patient->id}")
            ->assertSee(route('patients.destroy', $this->patient));

        $this->actingAs($this->secretary)
            ->get("/patients/{$this->patient->id}")
            ->assertDontSee('Supprimer');
    }

    public function test_the_doctor_can_restore_an_archived_patient(): void
    {
        $this->patient->delete();

        $this->actingAs($this->doctor)
            ->patch("/patients/{$this->patient->id}/restore")
            ->assertRedirect(route('patients.show', $this->patient));

        $this->assertNotSoftDeleted($this->patient);
        $this->actingAs($this->doctor)->get('/patients')->assertSee('Trabelsi Amira');
    }

    public function test_the_secretary_cannot_see_or_restore_archives(): void
    {
        $this->patient->delete();

        $this->actingAs($this->secretary)->get('/patients/archives')->assertForbidden();
        $this->actingAs($this->secretary)->patch("/patients/{$this->patient->id}/restore")->assertForbidden();

        $this->assertSoftDeleted($this->patient);
    }

    public function test_the_archives_link_is_shown_to_the_doctor_only(): void
    {
        $this->actingAs($this->doctor)->get('/patients')->assertSee(route('patients.archived'));
        $this->actingAs($this->secretary)->get('/patients')->assertDontSee(route('patients.archived'));
    }

    public function test_the_archive_page_is_not_mistaken_for_a_patient_page(): void
    {
        $this->actingAs($this->doctor)
            ->get('/patients/archives')
            ->assertOk()
            ->assertSee('Patients archivés');
    }
}
