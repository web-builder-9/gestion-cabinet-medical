<?php

namespace Tests\Feature;

use App\Enums\BloodType;
use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Modèle Patient : spécialisation de l'utilisateur (héritage par table liée en 1-1).
 */
class PatientTest extends TestCase
{
    use RefreshDatabase;

    public function test_identity_comes_from_the_user_account(): void
    {
        $patient = Patient::factory()->withUser(['first_name' => 'Amira', 'last_name' => 'Trabelsi'])->create([
            'birth_date' => now()->subYears(30)->subDay()->toDateString(),
        ]);

        $this->assertSame('Amira Trabelsi', $patient->full_name);
        $this->assertSame(30, (int) $patient->age);
        $this->assertTrue($patient->user->isPatient());
        $this->assertTrue($patient->user->patient->is($patient));
    }

    public function test_patients_can_be_searched_by_name_cin_or_phone(): void
    {
        $amira = Patient::factory()->withUser(['first_name' => 'Amira', 'last_name' => 'Trabelsi', 'phone' => '22 333 444'])
            ->create(['cin' => '01234567']);
        $karim = Patient::factory()->withUser(['first_name' => 'Karim', 'last_name' => 'Gharbi', 'phone' => '55 666 777'])
            ->create(['cin' => '09876543']);

        $this->assertEquals([$amira->id], Patient::search('trabel')->pluck('id')->all());
        $this->assertEquals([$karim->id], Patient::search('karim')->pluck('id')->all());
        $this->assertEquals([$karim->id], Patient::search('0987')->pluck('id')->all());
        $this->assertEquals([$karim->id], Patient::search('55 666')->pluck('id')->all());
        $this->assertCount(2, Patient::search('')->get());
    }

    public function test_patients_are_sorted_by_last_then_first_name(): void
    {
        Patient::factory()->withUser(['first_name' => 'Zied', 'last_name' => 'Zouari'])->create();
        Patient::factory()->withUser(['first_name' => 'Ines', 'last_name' => 'Ayari'])->create();
        Patient::factory()->withUser(['first_name' => 'Ali', 'last_name' => 'Ayari'])->create();

        $this->assertSame(
            ['Ali Ayari', 'Ines Ayari', 'Zied Zouari'],
            Patient::with('user')->orderByName()->get()->map->full_name->all()
        );
    }

    public function test_a_patient_has_a_medical_record(): void
    {
        $patient = Patient::factory()->withMedicalRecord()->create();

        $this->assertNotNull($patient->medicalRecord);
        $this->assertInstanceOf(BloodType::class, $patient->medicalRecord->blood_type);
    }

    public function test_archiving_a_patient_keeps_the_history(): void
    {
        $patient = Patient::factory()->withMedicalRecord()->create();
        $appointment = Appointment::factory()->create(['patient_id' => $patient->id]);

        $patient->delete();

        $this->assertSoftDeleted($patient);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id]);
        $this->assertDatabaseHas('medical_records', ['patient_id' => $patient->id]);
        // Le rendez-vous garde son patient, même archivé.
        $this->assertTrue($appointment->fresh()->patient->is($patient));
    }
}
