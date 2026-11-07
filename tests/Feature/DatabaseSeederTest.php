<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\Role;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\PatientSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Les données de démonstration respectent elles-mêmes les règles de gestion.
 */
class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_demo_accounts_exist_with_their_role(): void
    {
        $this->assertSame(Role::Admin, User::where('email', 'admin@cabinet.test')->value('role'));
        $this->assertSame(Role::Secretary, User::where('email', 'secretaire@cabinet.test')->value('role'));
        $this->assertSame(Role::Doctor, User::where('email', 'medecin@cabinet.test')->value('role'));
        $this->assertSame(Role::Patient, User::where('email', 'patient@cabinet.test')->value('role'));

        $this->assertSame(3, Doctor::count());
        $this->assertSame(PatientSeeder::RANDOM_COUNT + 1, Patient::count());
        $this->assertNotNull(Setting::query()->first());
        $this->assertFalse(User::where('email', 'h.mabrouk@cabinet.test')->value('is_active'));
    }

    public function test_every_doctor_has_a_specialty_and_availabilities(): void
    {
        foreach (Doctor::with('specialty')->withCount('availabilities')->get() as $doctor) {
            $this->assertNotNull($doctor->specialty);
            $this->assertGreaterThan(0, $doctor->availabilities_count);
        }
    }

    public function test_no_slot_and_no_patient_is_double_booked(): void
    {
        $this->assertGreaterThan(100, Appointment::count());

        foreach (Appointment::active()->get() as $appointment) {
            $this->assertFalse($appointment->overlaps(), "Conflit sur le rendez-vous #{$appointment->id}");
        }
    }

    public function test_completed_appointments_have_a_consultation_and_a_history(): void
    {
        $this->assertSame(
            Appointment::where('status', AppointmentStatus::Completed)->count(),
            Consultation::count()
        );
        $this->assertSame(0, Appointment::doesntHave('statusChanges')->count());
        $this->assertSame(0, Appointment::where('status', AppointmentStatus::Completed)->doesntHave('consultation')->count());
    }

    public function test_the_demo_patient_has_upcoming_and_past_appointments(): void
    {
        $amira = User::where('email', 'patient@cabinet.test')->firstOrFail()->patient;

        $this->assertSame(2, $amira->appointments()->upcoming()->count());
        $this->assertSame(1, $amira->consultations()->count());
        $this->assertSame('Pénicilline', $amira->medicalRecord->allergies);
    }
}
