<?php

namespace Tests\Feature\Patients;

use App\Enums\BloodType;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le dossier médical contient des données de santé : seul le médecin
 * peut le voir et le modifier.
 */
class MedicalRecordTest extends TestCase
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
        $this->patient = Patient::factory()->create();

        MedicalRecord::factory()->create([
            'patient_id' => $this->patient->id,
            'blood_type' => BloodType::ONegative,
            'allergies' => 'Pénicilline',
            'medical_history' => 'Asthme',
            'current_treatments' => null,
        ]);
    }

    public function test_the_doctor_sees_the_medical_record_on_the_patient_page(): void
    {
        $this->actingAs($this->doctor)
            ->get("/patients/{$this->patient->id}")
            ->assertOk()
            ->assertSee('Dossier médical')
            ->assertSee('O-')
            ->assertSee('Pénicilline')
            ->assertSee('Asthme')
            ->assertSee(route('patients.medical-record.edit', $this->patient));
    }

    public function test_the_secretary_does_not_see_any_medical_data(): void
    {
        $this->actingAs($this->secretary)
            ->get("/patients/{$this->patient->id}")
            ->assertOk()
            ->assertDontSee('Dossier médical')
            ->assertDontSee('Pénicilline')
            ->assertDontSee('Asthme');
    }

    public function test_the_secretary_cannot_open_or_update_the_record(): void
    {
        $this->actingAs($this->secretary)
            ->get("/patients/{$this->patient->id}/dossier/edit")
            ->assertForbidden();

        $this->actingAs($this->secretary)
            ->put("/patients/{$this->patient->id}/dossier", ['allergies' => 'Modifié'])
            ->assertForbidden();

        $this->assertSame('Pénicilline', $this->patient->medicalRecord->fresh()->allergies);
    }

    public function test_the_doctor_can_update_the_record(): void
    {
        $this->actingAs($this->doctor)
            ->get("/patients/{$this->patient->id}/dossier/edit")
            ->assertOk()
            ->assertSee('Pénicilline');

        $this->actingAs($this->doctor)
            ->put("/patients/{$this->patient->id}/dossier", [
                'blood_type' => 'A+',
                'allergies' => 'Pénicilline, arachides',
                'medical_history' => 'Asthme',
                'current_treatments' => 'Salbutamol en cas de crise',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('patients.show', $this->patient));

        $record = $this->patient->medicalRecord->fresh();
        $this->assertSame(BloodType::APositive, $record->blood_type);
        $this->assertSame('Pénicilline, arachides', $record->allergies);
        $this->assertSame('Salbutamol en cas de crise', $record->current_treatments);
    }

    public function test_an_unknown_blood_type_is_rejected(): void
    {
        $this->actingAs($this->doctor)
            ->put("/patients/{$this->patient->id}/dossier", ['blood_type' => 'Z+'])
            ->assertSessionHasErrors(['blood_type' => 'La valeur choisie pour groupe sanguin n\'est pas valide.']);
    }

    public function test_a_missing_record_is_created_on_first_save(): void
    {
        $patientWithoutRecord = Patient::factory()->create();

        $this->actingAs($this->doctor)
            ->put("/patients/{$patientWithoutRecord->id}/dossier", ['allergies' => 'Lactose'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Lactose', $patientWithoutRecord->medicalRecord()->value('allergies'));
    }
}
