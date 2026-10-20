<?php

namespace Tests\Feature;

use App\Enums\BloodType;
use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_name_and_age_are_computed(): void
    {
        $patient = Patient::factory()->create([
            'first_name' => 'Amira',
            'last_name' => 'Trabelsi',
            'birth_date' => now()->subYears(30)->subDay()->toDateString(),
        ]);

        $this->assertSame('Amira Trabelsi', $patient->full_name);
        $this->assertSame(30, (int) $patient->age);
    }

    public function test_patients_can_be_searched_by_name_cin_or_phone(): void
    {
        $amira = Patient::factory()->create([
            'first_name' => 'Amira', 'last_name' => 'Trabelsi', 'cin' => '01234567', 'phone' => '22 333 444',
        ]);
        $karim = Patient::factory()->create([
            'first_name' => 'Karim', 'last_name' => 'Gharbi', 'cin' => '09876543', 'phone' => '55 666 777',
        ]);

        $this->assertEquals([$amira->id], Patient::search('trabel')->pluck('id')->all());
        $this->assertEquals([$karim->id], Patient::search('0987')->pluck('id')->all());
        $this->assertEquals([$karim->id], Patient::search('55 666')->pluck('id')->all());
        $this->assertCount(2, Patient::search('')->get());
    }

    public function test_a_patient_has_a_medical_record(): void
    {
        $patient = Patient::factory()->withMedicalRecord()->create();

        $this->assertNotNull($patient->medicalRecord);
        $this->assertInstanceOf(BloodType::class, $patient->medicalRecord->blood_type);
    }

    public function test_deleting_a_patient_keeps_the_history(): void
    {
        $patient = Patient::factory()->withMedicalRecord()->create();
        $appointment = Appointment::factory()->create(['patient_id' => $patient->id]);

        $patient->delete();

        $this->assertSoftDeleted($patient);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id]);
        $this->assertDatabaseHas('medical_records', ['patient_id' => $patient->id]);
    }
}
