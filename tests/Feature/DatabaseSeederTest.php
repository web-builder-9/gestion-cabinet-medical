<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\Patient;
use Database\Seeders\AppointmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_is_seeded_without_double_booking(): void
    {
        $this->seed();

        $this->assertDatabaseHas('users', ['email' => 'medecin@cabinet.test', 'role' => 'doctor']);
        $this->assertDatabaseHas('users', ['email' => 'secretaire@cabinet.test', 'role' => 'secretary']);
        $this->assertDatabaseHas('users', ['email' => 'patient@cabinet.test', 'role' => 'patient']);

        $this->assertSame(41, Patient::count());
        $this->assertSame(Patient::count(), MedicalRecord::count());
        $this->assertSame(AppointmentSeeder::TODAY_COUNT, Appointment::onDate(today())->count());

        foreach (Appointment::all() as $appointment) {
            $this->assertFalse($appointment->hasConflict(), "Conflit sur le rendez-vous #{$appointment->id}");
        }
    }
}
